<?php

declare(strict_types=1);

namespace DeployerPHP\Console\Cloud\Aws;

use DeployerPHP\Contracts\BaseCommand;
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
    name: 'aws:dns:delete',
    description: 'Delete a DNS record from an AWS Route53 hosted zone'
)]
class DnsDeleteCommand extends BaseCommand
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
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Skip typing the record name to confirm')
            ->addOption('yes', 'y', InputOption::VALUE_NONE, 'Skip Yes/No confirmation prompt');
    }

    // ----
    // Execution
    // ----

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        parent::execute($input, $output);

        $this->h1('Delete DNS Record');

        //
        // Initialize AWS API
        // ----

        if (Command::FAILURE === $this->initializeAwsAPI()) {
            return Command::FAILURE;
        }

        //
        // Gather input
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
        // Find existing record
        // ----

        try {
            $record = $this->io->promptSpin(
                fn () => $this->aws->route53Dns->findRecord($zoneId, $deets['type'], $fullName),
                'Finding DNS record...'
            );
        } catch (\RuntimeException $e) {
            $this->nay($e->getMessage());

            return Command::FAILURE;
        }

        if (null === $record) {
            $this->nay("No {$deets['type']} record found for '{$deets['name']}' in zone '{$zoneName}'");

            return Command::FAILURE;
        }

        // Check if it's an alias record (cannot delete normally)
        if ($record['is_alias']) {
            $this->nay('Alias records must be deleted using the AWS Console or by creating the same alias with different settings');

            return Command::FAILURE;
        }

        //
        // Display record details
        // ----

        $this->displayDeets([
            'Type' => $record['type'],
            'Name' => $record['name'],
            'Value' => $record['value'],
            'TTL' => (string) $record['ttl'],
        ]);

        $this->out('───');
        $this->io->write("\n");

        //
        // Confirm deletion
        // ----

        /** @var bool $forceSkip */
        $forceSkip = $input->getOption('force');

        $confirmed = $this->confirmDnsDeletion($deets['name'], $forceSkip);

        if (null === $confirmed) {
            return Command::FAILURE;
        }

        if (!$confirmed) {
            $this->warn('Cancelled deleting DNS record');

            return Command::SUCCESS;
        }

        //
        // Delete record
        // ----

        try {
            $this->io->promptSpin(
                fn () => $this->aws->route53Dns->deleteRecord(
                    $zoneId,
                    $deets['type'],
                    $fullName,
                    $record['value'],
                    $record['ttl']
                ),
                'Deleting DNS record...'
            );

            $this->yay('DNS record deleted successfully');
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
            'force' => true,
            'yes' => true,
        ]);

        return Command::SUCCESS;
    }

    // ----
    // Helpers
    // ----

    /**
     * Gather record details from user input or CLI options.
     *
     * @return array{zone: string, type: string, name: string}|int
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

            return [
                'zone' => $zone,
                'type' => $type,
                'name' => $name,
            ];
        } catch (ValidationException $e) {
            $this->nay($e->getMessage());

            return Command::FAILURE;
        }
    }
}
