<?php

declare(strict_types=1);

namespace DeployerPHP\Console\Pro\Do;

use DeployerPHP\Contracts\ProCommand;
use DeployerPHP\Exceptions\ValidationException;
use DeployerPHP\Services\Do\DoDnsService;
use DeployerPHP\Traits\DoDnsTrait;
use DeployerPHP\Traits\DoTrait;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'pro:do:dns:set|do:dns:set|pro:digitalocean:dns:set|digitalocean:dns:set',
    description: 'Create or update a DNS record for a DigitalOcean domain'
)]
class DnsSetCommand extends ProCommand
{
    use DoDnsTrait;
    use DoTrait;

    // ----
    // Configuration
    // ----

    protected function configure(): void
    {
        parent::configure();

        $this
            ->addOption('domain', null, InputOption::VALUE_REQUIRED, 'Domain name')
            ->addOption('type', null, InputOption::VALUE_REQUIRED, 'Record type (A, AAAA, CNAME, MX, TXT, NS, SRV, CAA)')
            ->addOption('name', null, InputOption::VALUE_REQUIRED, 'Record name (use "@" for root)')
            ->addOption('value', null, InputOption::VALUE_REQUIRED, 'Record value')
            ->addOption('ttl', null, InputOption::VALUE_REQUIRED, 'TTL in seconds (default: 1800)')
            ->addOption('priority', null, InputOption::VALUE_REQUIRED, 'Priority for MX/SRV records')
            ->addOption('port', null, InputOption::VALUE_REQUIRED, 'Port for SRV records')
            ->addOption('weight', null, InputOption::VALUE_REQUIRED, 'Weight for SRV records')
            ->addOption('flags', null, InputOption::VALUE_REQUIRED, 'Flags for CAA records (0-255)')
            ->addOption('tag', null, InputOption::VALUE_REQUIRED, 'Tag for CAA records (issue, issuewild, iodef)');
    }

    // ----
    // Execution
    // ----

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        parent::execute($input, $output);

        $this->h1('Set DNS Record');

        //
        // Initialize DigitalOcean API
        // ----

        if (Command::FAILURE === $this->initializeDoAPI()) {
            return Command::FAILURE;
        }

        //
        // Gather record details
        // ----

        $deets = $this->gatherRecordDeets($input);

        if (is_int($deets)) {
            return Command::FAILURE;
        }

        //
        // Validate domain
        // ----

        try {
            $this->resolveDoDomain($deets['domain']);
        } catch (\RuntimeException $e) {
            $this->nay($e->getMessage());

            return Command::FAILURE;
        }

        //
        // Create or update record
        // ----

        try {
            $result = $this->io->promptSpin(
                fn () => $this->do->dns->setRecord(
                    $deets['domain'],
                    $deets['type'],
                    $deets['name'],
                    $deets['value'],
                    $deets['ttl'],
                    $deets['priority'],
                    $deets['port'],
                    $deets['weight'],
                    $deets['flags'],
                    $deets['tag']
                ),
                'Setting DNS record...'
            );

            $action = 'created' === $result['action'] ? 'created' : 'updated';
            $this->yay("DNS record {$action} successfully (ID: {$result['id']})");
        } catch (\RuntimeException $e) {
            $this->nay($e->getMessage());

            return Command::FAILURE;
        }

        //
        // Show command replay
        // ----

        $replayOptions = [
            'domain' => $deets['domain'],
            'type' => $deets['type'],
            'name' => $deets['name'],
            'value' => $deets['value'],
            'ttl' => $deets['ttl'],
        ];

        if (null !== $deets['priority']) {
            $replayOptions['priority'] = $deets['priority'];
        }
        if (null !== $deets['port']) {
            $replayOptions['port'] = $deets['port'];
        }
        if (null !== $deets['weight']) {
            $replayOptions['weight'] = $deets['weight'];
        }
        if (null !== $deets['flags']) {
            $replayOptions['flags'] = $deets['flags'];
        }
        if (null !== $deets['tag']) {
            $replayOptions['tag'] = $deets['tag'];
        }

        $this->commandReplay($replayOptions);

        return Command::SUCCESS;
    }

    // ----
    // Helpers
    // ----

    /**
     * Gather record details from user input or CLI options.
     *
     * @return array{domain: string, type: string, name: string, value: string, ttl: int, priority: int|null, port: int|null, weight: int|null, flags: int|null, tag: string|null}|int
     */
    protected function gatherRecordDeets(InputInterface $input): array|int
    {
        try {
            $domains = $this->io->promptSpin(
                fn () => $this->do->domain->getDomains(),
                'Fetching domains...'
            );

            if (0 === count($domains)) {
                $this->info('No domains found in your DigitalOcean account');

                return Command::FAILURE;
            }

            /** @var string $domain */
            $domain = $this->io->getValidatedOptionOrPrompt(
                'domain',
                fn ($validate) => $this->io->promptSelect(
                    label: 'Select domain:',
                    options: $domains,
                    validate: $validate
                ),
                fn ($value) => $this->validateDoDomainInput($value)
            );

            $typeOptions = array_combine(DoDnsService::RECORD_TYPES, DoDnsService::RECORD_TYPES);

            /** @var string $type */
            $type = $this->io->getValidatedOptionOrPrompt(
                'type',
                fn ($validate) => $this->io->promptSelect(
                    label: 'Record type:',
                    options: $typeOptions,
                    validate: $validate
                ),
                fn ($value) => $this->validateDoRecordTypeInput($value)
            );

            $type = strtoupper($type);

            /** @var string $name */
            $name = $this->io->getValidatedOptionOrPrompt(
                'name',
                fn ($validate) => $this->io->promptText(
                    label: 'Record name:',
                    placeholder: '@',
                    hint: 'Use "@" for root domain',
                    validate: $validate
                ),
                fn ($value) => $this->validateDoRecordNameInput($value)
            );

            /** @var string $value */
            $value = $this->io->getValidatedOptionOrPrompt(
                'value',
                fn ($validate) => $this->io->promptText(
                    label: 'Record value:',
                    placeholder: $this->getValuePlaceholder($type),
                    validate: $validate
                ),
                fn ($v) => $this->validateDoRecordValueInput($v)
            );

            /** @var string|int $ttlRaw */
            $ttlRaw = $this->io->getValidatedOptionOrPrompt(
                'ttl',
                fn ($validate) => $this->io->promptText(
                    label: 'TTL (seconds):',
                    default: '1800',
                    validate: $validate
                ),
                fn ($v) => $this->validateDoTtlInput($v)
            );

            $ttl = (int) $ttlRaw;

            // Optional fields based on record type
            $priority = $this->gatherPriorityIfNeeded($type, $input);
            $port = $this->gatherPortIfNeeded($type, $input);
            $weight = $this->gatherWeightIfNeeded($type, $input);
            [$flags, $tag] = $this->gatherCaaFieldsIfNeeded($type, $input);

            return [
                'domain' => $domain,
                'type' => $type,
                'name' => $name,
                'value' => $value,
                'ttl' => $ttl,
                'priority' => $priority,
                'port' => $port,
                'weight' => $weight,
                'flags' => $flags,
                'tag' => $tag,
            ];
        } catch (ValidationException $e) {
            $this->nay($e->getMessage());

            return Command::FAILURE;
        }
    }

    /**
     * Get placeholder text for record value based on type.
     */
    protected function getValuePlaceholder(string $type): string
    {
        return match ($type) {
            'A' => '192.0.2.1',
            'AAAA' => '2001:db8::1',
            'CNAME' => 'target.example.com',
            'MX' => 'mail.example.com',
            'TXT' => 'v=spf1 include:_spf.example.com ~all',
            'NS' => 'ns1.example.com',
            'SRV' => 'target.example.com',
            'CAA' => 'letsencrypt.org',
            default => '',
        };
    }

    /**
     * Gather priority for MX/SRV records.
     */
    protected function gatherPriorityIfNeeded(string $type, InputInterface $input): ?int
    {
        if (!in_array($type, ['MX', 'SRV'], true)) {
            return null;
        }

        /** @var string|null $priorityOption */
        $priorityOption = $input->getOption('priority');

        if (null !== $priorityOption && '' !== $priorityOption) {
            $error = $this->validateDoPriorityInput($priorityOption);
            if (null !== $error) {
                throw new ValidationException($error);
            }

            return (int) $priorityOption;
        }

        /** @var string $priority */
        $priority = $this->io->promptText(
            label: 'Priority:',
            default: '10',
            hint: 'Lower values = higher priority',
            validate: fn ($v) => $this->validateDoPriorityInput($v)
        );

        return (int) $priority;
    }

    /**
     * Gather port for SRV records.
     */
    protected function gatherPortIfNeeded(string $type, InputInterface $input): ?int
    {
        if ('SRV' !== $type) {
            return null;
        }

        /** @var string|null $portOption */
        $portOption = $input->getOption('port');

        if (null !== $portOption && '' !== $portOption) {
            $error = $this->validateDoPortInput($portOption);
            if (null !== $error) {
                throw new ValidationException($error);
            }

            return (int) $portOption;
        }

        /** @var string $port */
        $port = $this->io->promptText(
            label: 'Port:',
            placeholder: '443',
            validate: fn ($v) => $this->validateDoPortInput($v)
        );

        return (int) $port;
    }

    /**
     * Gather weight for SRV records.
     */
    protected function gatherWeightIfNeeded(string $type, InputInterface $input): ?int
    {
        if ('SRV' !== $type) {
            return null;
        }

        /** @var string|null $weightOption */
        $weightOption = $input->getOption('weight');

        if (null !== $weightOption && '' !== $weightOption) {
            $error = $this->validateDoWeightInput($weightOption);
            if (null !== $error) {
                throw new ValidationException($error);
            }

            return (int) $weightOption;
        }

        /** @var string $weight */
        $weight = $this->io->promptText(
            label: 'Weight:',
            default: '100',
            validate: fn ($v) => $this->validateDoWeightInput($v)
        );

        return (int) $weight;
    }

    /**
     * Gather flags and tag for CAA records.
     *
     * @return array{0: int|null, 1: string|null}
     */
    protected function gatherCaaFieldsIfNeeded(string $type, InputInterface $input): array
    {
        if ('CAA' !== $type) {
            return [null, null];
        }

        /** @var string|null $flagsOption */
        $flagsOption = $input->getOption('flags');
        /** @var string|null $tagOption */
        $tagOption = $input->getOption('tag');

        if (null !== $flagsOption && '' !== $flagsOption) {
            $error = $this->validateDoFlagsInput($flagsOption);
            if (null !== $error) {
                throw new ValidationException($error);
            }
            $flags = (int) $flagsOption;
        } else {
            /** @var string $flagsStr */
            $flagsStr = $this->io->promptText(
                label: 'Flags:',
                default: '0',
                hint: '0 = non-critical, 128 = critical',
                validate: fn ($v) => $this->validateDoFlagsInput($v)
            );
            $flags = (int) $flagsStr;
        }

        if (null !== $tagOption) {
            $error = $this->validateDoTagInput($tagOption);
            if (null !== $error) {
                throw new ValidationException($error);
            }
            $tag = strtolower($tagOption);
        } else {
            $tagOptions = [
                'issue' => 'issue - Authorize CA for domain',
                'issuewild' => 'issuewild - Authorize CA for wildcard',
                'iodef' => 'iodef - Report policy violations',
            ];

            /** @var string $tag */
            $tag = $this->io->promptSelect(
                label: 'CAA tag:',
                options: $tagOptions
            );
        }

        return [$flags, $tag];
    }
}
