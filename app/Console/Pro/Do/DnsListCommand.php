<?php

declare(strict_types=1);

namespace DeployerPHP\Console\Pro\Do;

use DeployerPHP\Contracts\ProCommand;
use DeployerPHP\Exceptions\ValidationException;
use DeployerPHP\Traits\DoDnsTrait;
use DeployerPHP\Traits\DoTrait;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'pro:do:dns:list|do:dns:list|pro:digitalocean:dns:list|digitalocean:dns:list',
    description: 'List DNS records for a DigitalOcean domain'
)]
class DnsListCommand extends ProCommand
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
            ->addOption('type', null, InputOption::VALUE_REQUIRED, 'Filter by record type (A, AAAA, CNAME)');
    }

    // ----
    // Execution
    // ----

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        parent::execute($input, $output);

        $this->h1('List DNS Records');

        //
        // Initialize DigitalOcean API
        // ----

        if (Command::FAILURE === $this->initializeDoAPI()) {
            return Command::FAILURE;
        }

        //
        // Gather input
        // ----

        try {
            $domains = $this->io->promptSpin(
                fn () => $this->do->domain->getDomains(),
                'Fetching domains...'
            );

            if (0 === count($domains)) {
                $this->info('No domains found in your DigitalOcean account');

                return Command::SUCCESS;
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

            /** @var string|null $typeFilter */
            $typeFilter = $input->getOption('type');

            if (null !== $typeFilter) {
                $error = $this->validateDoRecordTypeInput($typeFilter);
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
        // Fetch and display records
        // ----

        try {
            $records = $this->io->promptSpin(
                fn () => $this->do->dns->listRecords($domain, $typeFilter),
                'Fetching DNS records...'
            );
        } catch (\RuntimeException $e) {
            $this->nay($e->getMessage());

            return Command::FAILURE;
        }

        if (0 === count($records)) {
            $message = null === $typeFilter
                ? "No DNS records found for '{$domain}'"
                : "No {$typeFilter} records found for '{$domain}'";
            $this->info($message);

            return Command::SUCCESS;
        }

        $this->displayRecords($records);

        //
        // Show command replay
        // ----

        $replayOptions = ['domain' => $domain];
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
     * Display DNS records in a formatted table.
     *
     * @param array<int, array{id: int, type: string, name: string, data: string|null, ttl: int}> $records
     */
    protected function displayRecords(array $records): void
    {
        $rows = [];

        foreach ($records as $record) {
            $rows[] = [
                'Type' => $record['type'],
                'Name' => $record['name'],
                'Value' => $this->truncateValue($record['data'] ?? ''),
                'TTL' => (string) $record['ttl'],
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
        if (strlen($value) > 50) {
            return substr($value, 0, 47) . '...';
        }

        return $value;
    }
}
