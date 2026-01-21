<?php

declare(strict_types=1);

namespace DeployerPHP\Console\Cloud\Cf;

use DeployerPHP\Contracts\BaseCommand;
use DeployerPHP\Exceptions\ValidationException;
use DeployerPHP\Traits\CfTrait;
use DeployerPHP\Traits\DnsCommandTrait;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'cf:dns:list|cloudflare:dns:list',
    description: 'List DNS records in a Cloudflare zone'
)]
class DnsListCommand extends BaseCommand
{
    use CfTrait;
    use DnsCommandTrait;

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
        // Initialize Cloudflare API
        // ----

        if (Command::FAILURE === $this->initializeCloudflareAPI()) {
            return Command::FAILURE;
        }

        //
        // Gather input
        // ----

        try {
            $zones = $this->io->promptSpin(
                fn () => $this->cf->zone->getZones(),
                'Fetching zones...'
            );

            if (0 === count($zones)) {
                $this->info('No zones found in your Cloudflare account');

                return Command::SUCCESS;
            }

            /** @var string $zone */
            $zone = $this->io->getValidatedOptionOrPrompt(
                'zone',
                fn ($validate) => $this->io->promptSelect(
                    label: 'Select zone:',
                    options: $zones,
                    validate: $validate
                ),
                fn ($value) => $this->validateZoneInput($value)
            );
        } catch (ValidationException $e) {
            $this->nay($e->getMessage());

            return Command::FAILURE;
        }

        /** @var string|null $typeFilter */
        $typeFilter = $input->getOption('type');

        if (null !== $typeFilter) {
            $error = $this->validateRecordTypeInput($typeFilter);
            if (null !== $error) {
                $this->nay($error);

                return Command::FAILURE;
            }
            $typeFilter = strtoupper($typeFilter);
        }

        //
        // Fetch and display records
        // ----

        try {
            $zoneId = $this->resolveZoneId($zone);

            /** @var array<int, array{id: string, type: string, name: string, content: string, ttl: int, proxied: bool}> $records */
            $records = $this->io->promptSpin(
                fn () => $this->cf->dns->listRecords($zoneId, $typeFilter),
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

            $this->commandReplay([
                'zone' => $zone,
                'type' => $typeFilter,
            ]);

            return Command::SUCCESS;
        }

        // Normalize records for display (CF uses 'content' instead of 'value', has 'proxied')
        $normalizedRecords = array_map(fn ($r) => [
            'type' => $r['type'],
            'name' => $r['name'],
            'value' => $r['content'],
            'ttl' => 1 === $r['ttl'] ? 'auto' : $r['ttl'],
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
