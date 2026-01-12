<?php

declare(strict_types=1);

namespace DeployerPHP\Console\Pro\Cloudflare;

use DeployerPHP\Contracts\ProCommand;
use DeployerPHP\Exceptions\ValidationException;
use DeployerPHP\Services\Cloudflare\CloudflareDnsService;
use DeployerPHP\Traits\CloudflareTrait;
use DeployerPHP\Traits\DnsCommandTrait;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'pro:cf:dns:delete|cf:dns:delete|pro:cloudflare:dns:delete|cloudflare:dns:delete',
    description: 'Delete a DNS record from Cloudflare'
)]
class DnsDeleteCommand extends ProCommand
{
    use CloudflareTrait;
    use DnsCommandTrait;

    // ----
    // Configuration
    // ----

    protected function configure(): void
    {
        parent::configure();

        $this
            ->addOption('zone', null, InputOption::VALUE_REQUIRED, 'Zone (domain name)')
            ->addOption('type', null, InputOption::VALUE_REQUIRED, 'Record type (A, AAAA, CNAME)')
            ->addOption('name', null, InputOption::VALUE_REQUIRED, 'Record name (use "@" for root domain)')
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
        // Initialize Cloudflare API
        // ----

        if (Command::FAILURE === $this->initializeCloudflareAPI()) {
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
        // Find the record
        // ----

        try {
            $zoneId = $this->resolveZoneId($deets['zone']);

            // Get zone name for record normalization
            $zoneName = $this->io->promptSpin(
                fn () => $this->cloudflare->zone->getZoneName($zoneId),
                'Fetching zone details...'
            );

            $fullName = $this->normalizeRecordName($deets['name'], $zoneName);

            /** @var array{id: string, type: string, name: string, content: string, ttl: int, proxied: bool}|null $record */
            $record = $this->io->promptSpin(
                fn () => $this->cloudflare->dns->findRecord($zoneId, $deets['type'], $fullName),
                'Finding DNS record...'
            );

            if (null === $record) {
                $this->nay("No {$deets['type']} record found for '{$deets['name']}' in zone '{$zoneName}'");

                return Command::FAILURE;
            }

            // Show record details
            $this->displayDeets([
                'Type' => $record['type'],
                'Name' => $record['name'],
                'Value' => $record['content'],
                'TTL' => 1 === $record['ttl'] ? 'auto' : "{$record['ttl']}s",
                'Proxied' => $record['proxied'] ? 'Yes' : 'No',
            ]);

            $this->out('───');
            $this->io->write("\n");

            //
            // Confirm deletion (two-tier confirmation)
            // ----

            /** @var bool $forceSkip */
            $forceSkip = $input->getOption('force');

            $confirmed = $this->confirmDnsDeletion($fullName, $forceSkip);

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

            $this->io->promptSpin(
                fn () => $this->cloudflare->dns->deleteRecord($zoneId, $record['id']),
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
            'zone' => $deets['zone'],
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
                fn () => $this->cloudflare->zone->getZones(),
                'Fetching zones...'
            );

            if (0 === count($zones)) {
                $this->info('No zones found in your Cloudflare account');

                return Command::FAILURE;
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

            $typeOptions = array_combine(
                CloudflareDnsService::RECORD_TYPES,
                CloudflareDnsService::RECORD_TYPES
            );

            /** @var string $type */
            $type = $this->io->getValidatedOptionOrPrompt(
                'type',
                fn ($validate) => $this->io->promptSelect(
                    label: 'Record type:',
                    options: $typeOptions,
                    validate: $validate
                ),
                fn ($value) => $this->validateRecordTypeInput($value)
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
                fn ($value) => $this->validateRecordNameInput($value)
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
