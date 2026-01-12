<?php

declare(strict_types=1);

namespace DeployerPHP\Console\Pro\Cloudflare;

use DeployerPHP\Contracts\ProCommand;
use DeployerPHP\Exceptions\ValidationException;
use DeployerPHP\Traits\CloudflareTrait;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'pro:cloudflare:dns:list|cf:dns:list',
    description: 'List DNS records in a Cloudflare zone'
)]
final class DnsListCommand extends ProCommand
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
            ->addOption('type', null, InputOption::VALUE_REQUIRED, 'Filter by record type (A, AAAA, CNAME)');
    }

    // ----
    // Execution
    // ----

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        parent::execute($input, $output);

        $this->h1('List Cloudflare DNS Records');

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
        }

        //
        // Fetch and display records
        // ----

        try {
            $zoneId = $this->resolveZoneId($zone);

            /** @var array<int, array{id: string, type: string, name: string, content: string, ttl: int, proxied: bool}> $records */
            $records = $this->io->promptSpin(
                fn () => $this->cloudflare->dns->listRecords($zoneId, $typeFilter),
                'Fetching DNS records...'
            );
        } catch (\RuntimeException $e) {
            $this->nay($e->getMessage());

            return Command::FAILURE;
        }

        if (0 === count($records)) {
            $this->info('No DNS records found' . (null !== $typeFilter ? " for type {$typeFilter}" : ''));

            $this->commandReplay([
                'zone' => $zone,
                'type' => $typeFilter,
            ]);

            return Command::SUCCESS;
        }

        $this->info(count($records) . ' record(s) found');
        $this->out('');

        foreach ($records as $record) {
            $proxiedIcon = $record['proxied'] ? ' [proxied]' : '';
            $ttl = 1 === $record['ttl'] ? 'auto' : "{$record['ttl']}s";

            $this->out(sprintf(
                '%s %s -> %s (TTL: %s)%s',
                str_pad($record['type'], 6),
                $record['name'],
                $record['content'],
                $ttl,
                $proxiedIcon
            ));
        }

        //
        // Show command replay
        // ----

        $this->commandReplay([
            'zone' => $zone,
            'type' => $typeFilter,
        ]);

        return Command::SUCCESS;
    }
}
