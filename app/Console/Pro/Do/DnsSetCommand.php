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
            ->addOption('zone', null, InputOption::VALUE_REQUIRED, 'Zone (domain name)')
            ->addOption('type', null, InputOption::VALUE_REQUIRED, 'Record type (A, AAAA, CNAME)')
            ->addOption('name', null, InputOption::VALUE_REQUIRED, 'Record name (use "@" for root)')
            ->addOption('value', null, InputOption::VALUE_REQUIRED, 'Record value')
            ->addOption('ttl', null, InputOption::VALUE_REQUIRED, 'TTL in seconds (default: 300)');
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
            $this->resolveDoDomain($deets['zone']);
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
                    $deets['zone'],
                    $deets['type'],
                    $deets['name'],
                    $deets['value'],
                    $deets['ttl']
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

        $this->commandReplay([
            'zone' => $deets['zone'],
            'type' => $deets['type'],
            'name' => $deets['name'],
            'value' => $deets['value'],
            'ttl' => $deets['ttl'],
        ]);

        return Command::SUCCESS;
    }

    // ----
    // Helpers
    // ----

    /**
     * Gather record details from user input or CLI options.
     *
     * @return array{zone: string, type: string, name: string, value: string, ttl: int}|int
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

            /** @var string $zone */
            $zone = $this->io->getValidatedOptionOrPrompt(
                'zone',
                fn ($validate) => $this->io->promptSelect(
                    label: 'Select zone:',
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
                    default: '300',
                    validate: $validate
                ),
                fn ($v) => $this->validateDoTtlInput($v)
            );

            $ttl = (int) $ttlRaw;

            return [
                'zone' => $zone,
                'type' => $type,
                'name' => $name,
                'value' => $value,
                'ttl' => $ttl,
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
            default => '',
        };
    }
}
