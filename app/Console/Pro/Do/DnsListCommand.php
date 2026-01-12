<?php

declare(strict_types=1);

namespace DeployerPHP\Console\Pro\Do;

use DeployerPHP\Contracts\ProCommand;
use DeployerPHP\Exceptions\ValidationException;
use DeployerPHP\Traits\DnsCommandTrait;
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
    use DnsCommandTrait;
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
                fn () => $this->do->dns->listRecords($zone, $typeFilter),
                'Fetching DNS records...'
            );
        } catch (\RuntimeException $e) {
            $this->nay($e->getMessage());

            return Command::FAILURE;
        }

        if (0 === count($records)) {
            $message = null === $typeFilter
                ? "No DNS records found for '{$zone}'"
                : "No {$typeFilter} records found for '{$zone}'";
            $this->info($message);

            return Command::SUCCESS;
        }

        // Normalize records for display (DO uses 'data' instead of 'value')
        $normalizedRecords = array_map(fn ($r) => [
            'type' => $r['type'],
            'name' => $r['name'],
            'value' => $r['data'] ?? '',
            'ttl' => $r['ttl'],
        ], $records);

        $this->displayDnsRecords($normalizedRecords);

        //
        // Show command replay
        // ----

        $replayOptions = ['zone' => $zone];
        if (null !== $typeFilter) {
            $replayOptions['type'] = $typeFilter;
        }

        $this->commandReplay($replayOptions);

        return Command::SUCCESS;
    }
}
