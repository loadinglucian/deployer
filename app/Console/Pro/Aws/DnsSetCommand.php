<?php

declare(strict_types=1);

namespace DeployerPHP\Console\Pro\Aws;

use DeployerPHP\Contracts\ProCommand;
use DeployerPHP\Exceptions\ValidationException;
use DeployerPHP\Services\Aws\AwsRoute53DnsService;
use DeployerPHP\Traits\AwsDnsTrait;
use DeployerPHP\Traits\AwsTrait;
use DeployerPHP\Traits\DnsCommandTrait;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'pro:aws:dns:set|aws:dns:set',
    description: 'Create or update a DNS record for an AWS Route53 hosted zone'
)]
class DnsSetCommand extends ProCommand
{
    use AwsDnsTrait;
    use AwsTrait;
    use DnsCommandTrait;

    // ----
    // Configuration
    // ----

    protected function configure(): void
    {
        parent::configure();

        $this
            ->addOption('zone', null, InputOption::VALUE_REQUIRED, 'Hosted zone ID or domain name')
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
        // Initialize AWS API
        // ----

        if (Command::FAILURE === $this->initializeAwsAPI()) {
            return Command::FAILURE;
        }

        //
        // Gather record details
        // ----

        $deets = $this->gatherRecordDeets();

        if (is_int($deets)) {
            return Command::FAILURE;
        }

        //
        // Resolve zone
        // ----

        try {
            $zoneId = $this->resolveAwsHostedZoneId($deets['zone']);
            $zoneName = $this->aws->route53Zone->getHostedZoneName($zoneId);
        } catch (\RuntimeException $e) {
            $this->nay($e->getMessage());

            return Command::FAILURE;
        }

        //
        // Normalize record name
        // ----

        $fullName = $this->normalizeAwsRecordName($deets['name'], $zoneName);

        //
        // Create or update record
        // ----

        try {
            $this->io->promptSpin(
                fn () => $this->aws->route53Dns->setRecord(
                    $zoneId,
                    $deets['type'],
                    $fullName,
                    $deets['value'],
                    $deets['ttl']
                ),
                'Setting DNS record...'
            );

            $this->yay("DNS record upserted successfully");
        } catch (\RuntimeException $e) {
            $this->nay($e->getMessage());

            return Command::FAILURE;
        }

        //
        // Show command replay
        // ----

        $this->commandReplay([
            'zone' => $zoneName,
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
    protected function gatherRecordDeets(): array|int
    {
        try {
            $zones = $this->io->promptSpin(
                fn () => $this->aws->route53Zone->getHostedZones(),
                'Fetching hosted zones...'
            );

            if (0 === count($zones)) {
                $this->info('No hosted zones found in your AWS account');

                return Command::FAILURE;
            }

            /** @var string $zone */
            $zone = $this->io->getValidatedOptionOrPrompt(
                'zone',
                fn ($validate) => $this->io->promptSelect(
                    label: 'Select hosted zone:',
                    options: $zones,
                    validate: $validate
                ),
                fn ($value) => $this->validateAwsHostedZoneInput($value)
            );

            $typeOptions = array_combine(AwsRoute53DnsService::RECORD_TYPES, AwsRoute53DnsService::RECORD_TYPES);

            /** @var string $type */
            $type = $this->io->getValidatedOptionOrPrompt(
                'type',
                fn ($validate) => $this->io->promptSelect(
                    label: 'Record type:',
                    options: $typeOptions,
                    validate: $validate
                ),
                fn ($value) => $this->validateAwsRecordTypeInput($value)
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
                fn ($value) => $this->validateAwsRecordNameInput($value)
            );

            /** @var string $value */
            $value = $this->io->getValidatedOptionOrPrompt(
                'value',
                fn ($validate) => $this->io->promptText(
                    label: 'Record value:',
                    placeholder: $this->getValuePlaceholder($type),
                    validate: $validate
                ),
                fn ($v) => $this->validateAwsRecordValueInput($v)
            );

            /** @var string|int $ttlRaw */
            $ttlRaw = $this->io->getValidatedOptionOrPrompt(
                'ttl',
                fn ($validate) => $this->io->promptText(
                    label: 'TTL (seconds):',
                    default: '300',
                    validate: $validate
                ),
                fn ($v) => $this->validateAwsTtlInput($v)
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

}
