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
    name: 'pro:do:dns:delete|do:dns:delete|pro:digitalocean:dns:delete|digitalocean:dns:delete',
    description: 'Delete a DNS record from a DigitalOcean domain'
)]
class DnsDeleteCommand extends ProCommand
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
        // Initialize DigitalOcean API
        // ----

        if (Command::FAILURE === $this->initializeDoAPI()) {
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
        // Find existing record
        // ----

        try {
            $record = $this->io->promptSpin(
                fn () => $this->do->dns->findRecord($deets['domain'], $deets['type'], $deets['name']),
                'Finding DNS record...'
            );
        } catch (\RuntimeException $e) {
            $this->nay($e->getMessage());

            return Command::FAILURE;
        }

        if (null === $record) {
            $this->nay("No {$deets['type']} record found for '{$deets['name']}' in domain '{$deets['domain']}'");

            return Command::FAILURE;
        }

        //
        // Display record details
        // ----

        $this->displayDeets([
            'Type' => $record['type'],
            'Name' => $record['name'],
            'Value' => $record['data'],
            'TTL' => (string) $record['ttl'],
        ]);

        $this->out('───');
        $this->io->write("\n");

        //
        // Confirm deletion
        // ----

        /** @var bool $forceSkip */
        $forceSkip = $input->getOption('force');

        $confirmed = $this->confirmDeletion($deets['name'], $forceSkip);

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
                fn () => $this->do->dns->deleteRecord($deets['domain'], $record['id']),
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
            'domain' => $deets['domain'],
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
     * @return array{domain: string, type: string, name: string}|int
     */
    protected function gatherRecordDeets(): array|int
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

            return [
                'domain' => $domain,
                'type' => $type,
                'name' => $name,
            ];
        } catch (ValidationException $e) {
            $this->nay($e->getMessage());

            return Command::FAILURE;
        }
    }

    /**
     * Confirm record deletion with type-to-confirm and yes/no prompt.
     *
     * @return bool|null True if confirmed, false if cancelled, null if validation failed
     */
    protected function confirmDeletion(string $recordName, bool $forceSkip): ?bool
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
