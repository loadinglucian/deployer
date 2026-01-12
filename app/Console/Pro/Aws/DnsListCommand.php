<?php

declare(strict_types=1);

namespace DeployerPHP\Console\Pro\Aws;

use DeployerPHP\Contracts\ProCommand;
use DeployerPHP\Exceptions\ValidationException;
use DeployerPHP\Traits\AwsDnsTrait;
use DeployerPHP\Traits\AwsTrait;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'pro:aws:dns:list|aws:dns:list',
    description: 'List DNS records for an AWS Route53 hosted zone'
)]
class DnsListCommand extends ProCommand
{
    use AwsDnsTrait;
    use AwsTrait;

    // ----
    // Configuration
    // ----

    protected function configure(): void
    {
        parent::configure();

        $this
            ->addOption('zone', null, InputOption::VALUE_REQUIRED, 'Hosted zone ID or domain name')
            ->addOption('type', null, InputOption::VALUE_REQUIRED, 'Filter by record type (A, AAAA, CNAME, MX, TXT, NS, SRV, CAA, PTR)');
    }

    // ----
    // Execution
    // ----

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        parent::execute($input, $output);

        $this->h1('List DNS Records');

        //
        // Initialize AWS API
        // ----

        if (Command::FAILURE === $this->initializeAwsAPI()) {
            return Command::FAILURE;
        }

        //
        // Gather input
        // ----

        try {
            $zones = $this->io->promptSpin(
                fn () => $this->aws->route53Zone->getHostedZones(),
                'Fetching hosted zones...'
            );

            if (0 === count($zones)) {
                $this->info('No hosted zones found in your AWS account');

                return Command::SUCCESS;
            }

            /** @var string $zoneInput */
            $zoneInput = $this->io->getValidatedOptionOrPrompt(
                'zone',
                fn ($validate) => $this->io->promptSelect(
                    label: 'Select hosted zone:',
                    options: $zones,
                    validate: $validate
                ),
                fn ($value) => $this->validateAwsHostedZoneInput($value)
            );

            /** @var string|null $typeFilter */
            $typeFilter = $input->getOption('type');

            if (null !== $typeFilter) {
                $error = $this->validateAwsRecordTypeInput($typeFilter);
                if (null !== $error) {
                    $this->nay($error);

                    return Command::FAILURE;
                }
                $typeFilter = strtoupper($typeFilter);
            }
        } catch (ValidationException $e) {
            $this->nay($e->getMessage());

            return Command::FAILURE;
        }

        //
        // Resolve zone and fetch records
        // ----

        try {
            $zoneId = $this->resolveAwsHostedZoneId($zoneInput);
            $zoneName = $this->aws->route53Zone->getHostedZoneName($zoneId);

            $records = $this->io->promptSpin(
                fn () => $this->aws->route53Dns->listRecords($zoneId, $typeFilter),
                'Fetching DNS records...'
            );
        } catch (\RuntimeException $e) {
            $this->nay($e->getMessage());

            return Command::FAILURE;
        }

        if (0 === count($records)) {
            $message = null === $typeFilter
                ? "No DNS records found for '{$zoneName}'"
                : "No {$typeFilter} records found for '{$zoneName}'";
            $this->info($message);

            return Command::SUCCESS;
        }

        $this->displayRecords($records);

        //
        // Show command replay
        // ----

        $replayOptions = ['zone' => $zoneName];
        if (null !== $typeFilter) {
            $replayOptions['type'] = $typeFilter;
        }

        $this->commandReplay($replayOptions);

        return Command::SUCCESS;
    }

    // ----
    // Helpers
    // ----

    /**
     * Display DNS records in a formatted list.
     *
     * @param array<int, array{type: string, name: string, value: string, ttl: int, is_alias: bool}> $records
     */
    protected function displayRecords(array $records): void
    {
        $rows = [];

        foreach ($records as $record) {
            $rows[] = [
                'Type' => $record['type'],
                'Name' => $record['name'],
                'Value' => $this->truncateValue($record['value']),
                'TTL' => $record['is_alias'] ? 'ALIAS' : (string) $record['ttl'],
            ];
        }

        // Display as key-value pairs grouped by record
        foreach ($rows as $index => $row) {
            if ($index > 0) {
                $this->out('');
            }
            $this->displayDeets($row);
        }
    }

    /**
     * Truncate long values for display.
     */
    protected function truncateValue(string $value): string
    {
        if (strlen($value) > 60) {
            return substr($value, 0, 57) . '...';
        }

        return $value;
    }
}
