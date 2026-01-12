<?php

declare(strict_types=1);

namespace DeployerPHP\Console\Pro\Cloudflare;

use DeployerPHP\Contracts\ProCommand;
use DeployerPHP\Exceptions\ValidationException;
use DeployerPHP\Services\Cloudflare\CloudflareDnsService;
use DeployerPHP\Traits\CloudflareTrait;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'pro:cloudflare:dns:delete|cf:dns:delete',
    description: 'Delete a DNS record from Cloudflare'
)]
final class DnsDeleteCommand extends ProCommand
{
    use CloudflareTrait;

    // ----
    // Configuration
    // ----

    protected function configure(): void
    {
        parent::configure();

        $this
            ->addOption('zone', null, InputOption::VALUE_REQUIRED, 'Zone name (domain) or zone ID')
            ->addOption('type', null, InputOption::VALUE_REQUIRED, 'Record type (A, AAAA, CNAME, MX, TXT, etc.)')
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

        $this->h1('Delete Cloudflare DNS Record');

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
            /** @var string $zone */
            $zone = $this->io->getValidatedOptionOrPrompt(
                'zone',
                fn ($validate) => $this->io->promptText(
                    label: 'Zone (domain name or zone ID):',
                    placeholder: 'example.com',
                    required: true,
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
                    default: 'A',
                    validate: $validate
                ),
                fn ($value) => $this->validateRecordTypeInput($value)
            );

            $type = strtoupper($type);

            /** @var string $name */
            $name = $this->io->getValidatedOptionOrPrompt(
                'name',
                fn ($validate) => $this->io->promptText(
                    label: 'Record name (use "@" for root):',
                    placeholder: '@',
                    required: true,
                    validate: $validate
                ),
                fn ($value) => $this->validateRecordNameInput($value)
            );
        } catch (ValidationException $e) {
            $this->nay($e->getMessage());

            return Command::FAILURE;
        }

        //
        // Find the record
        // ----

        try {
            $zoneId = $this->resolveZoneId($zone);

            // Get zone name for record normalization
            $zoneName = $this->io->promptSpin(
                fn () => $this->cloudflare->zone->getZoneName($zoneId),
                'Fetching zone details...'
            );

            $fullName = $this->normalizeRecordName($name, $zoneName);

            /** @var array{id: string, type: string, name: string, content: string, ttl: int, proxied: bool, priority?: int}|null $record */
            $record = $this->io->promptSpin(
                fn () => $this->cloudflare->dns->findRecord($zoneId, $type, $fullName),
                'Finding DNS record...'
            );

            if (null === $record) {
                $this->nay("Record not found: {$type} {$fullName}");

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

            $confirmed = $this->confirmDeletion($fullName, $forceSkip);

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

            $this->yay("Deleted {$type} record: {$fullName}");
        } catch (\RuntimeException $e) {
            $this->nay($e->getMessage());

            return Command::FAILURE;
        }

        //
        // Show command replay
        // ----

        $this->commandReplay([
            'zone' => $zone,
            'type' => $type,
            'name' => $name,
            'force' => true,
            'yes' => true,
        ]);

        return Command::SUCCESS;
    }

    // ----
    // Helpers
    // ----

    /**
     * Confirm DNS record deletion with type-to-confirm and yes/no prompt.
     *
     * @param string $recordName The full record name to confirm
     * @param bool   $forceSkip  Skip the type-to-confirm step
     *
     * @return bool|null True if confirmed, false if cancelled, null if validation failed
     */
    private function confirmDeletion(string $recordName, bool $forceSkip): ?bool
    {
        if (!$forceSkip) {
            $typedName = $this->io->promptText(
                label: "Type the record name '{$recordName}' to confirm deletion:",
                required: true
            );

            if ($typedName !== $recordName) {
                $this->nay('Record name does not match. Deletion cancelled.');

                return null;
            }
        }

        return $this->io->getBooleanOptionOrPrompt(
            'yes',
            fn (): bool => $this->io->promptConfirm(
                label: 'Are you absolutely sure?',
                default: false
            )
        );
    }
}
