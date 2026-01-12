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
    name: 'pro:cf:dns:set|cf:dns:set|pro:cloudflare:dns:set|cloudflare:dns:set',
    description: 'Create or update a DNS record in Cloudflare (upsert)'
)]
class DnsSetCommand extends ProCommand
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
            ->addOption('value', null, InputOption::VALUE_REQUIRED, 'Record value (IP, hostname)')
            ->addOption('ttl', null, InputOption::VALUE_REQUIRED, 'TTL in seconds (default: 300, or 1 for auto)')
            ->addOption('proxied', null, InputOption::VALUE_NEGATABLE, 'Enable Cloudflare proxy (orange cloud)');
    }

    // ----
    // Execution
    // ----

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        parent::execute($input, $output);

        $this->h1('Set DNS Record');

        //
        // Initialize Cloudflare API
        // ----

        if (Command::FAILURE === $this->initializeCloudflareAPI()) {
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
        // Create or update record
        // ----

        try {
            $zoneId = $this->resolveZoneId($deets['zone']);

            // Get zone name for record normalization
            $zoneName = $this->io->promptSpin(
                fn () => $this->cloudflare->zone->getZoneName($zoneId),
                'Fetching zone details...'
            );

            $fullName = $this->normalizeRecordName($deets['name'], $zoneName);

            /** @var array{action: 'created'|'updated', id: string} $result */
            $result = $this->io->promptSpin(
                fn () => $this->cloudflare->dns->setRecord(
                    $zoneId,
                    $deets['type'],
                    $fullName,
                    $deets['value'],
                    $deets['ttl'],
                    $deets['proxied']
                ),
                'Setting DNS record...'
            );

            $action = 'created' === $result['action'] ? 'created' : 'updated';
            $this->yay("DNS record {$action} successfully");
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
            'value' => $deets['value'],
            'ttl' => $deets['ttl'],
            'proxied' => $deets['proxied'],
        ]);

        return Command::SUCCESS;
    }

    // ----
    // Helpers
    // ----

    /**
     * Gather DNS record details from user input or CLI options.
     *
     * @return array{zone: string, type: string, name: string, value: string, ttl: int, proxied: bool}|int
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

            /** @var string $value */
            $value = $this->io->getValidatedOptionOrPrompt(
                'value',
                fn ($validate) => $this->io->promptText(
                    label: 'Record value:',
                    placeholder: $this->getValuePlaceholder($type),
                    validate: $validate
                ),
                fn ($v) => $this->validateRecordValueInput($v)
            );

            /** @var string $ttlInput */
            $ttlInput = $this->io->getValidatedOptionOrPrompt(
                'ttl',
                fn ($validate) => $this->io->promptText(
                    label: 'TTL (seconds):',
                    default: '300',
                    validate: $validate
                ),
                fn ($v) => $this->validateTtlInput($v)
            );

            $ttl = (int) $ttlInput;

            // Proxied - all supported types (A, AAAA, CNAME) support proxying
            $proxied = $this->io->getBooleanOptionOrPrompt(
                'proxied',
                fn () => $this->io->promptConfirm(
                    label: 'Enable Cloudflare proxy (orange cloud)?',
                    default: true,
                    hint: 'Hides origin IP, enables CDN and DDoS protection'
                )
            );
        } catch (ValidationException $e) {
            $this->nay($e->getMessage());

            return Command::FAILURE;
        }

        return [
            'zone' => $zone,
            'type' => $type,
            'name' => $name,
            'value' => $value,
            'ttl' => $ttl,
            'proxied' => $proxied,
        ];
    }
}
