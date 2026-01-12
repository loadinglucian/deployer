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
    name: 'pro:cloudflare:dns:set|cf:dns:set',
    description: 'Create or update a DNS record in Cloudflare (upsert)'
)]
final class DnsSetCommand extends ProCommand
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
            ->addOption('value', null, InputOption::VALUE_REQUIRED, 'Record value (IP, hostname, text)')
            ->addOption('ttl', null, InputOption::VALUE_REQUIRED, 'TTL in seconds (1 = auto when proxied)')
            ->addOption('proxied', null, InputOption::VALUE_NEGATABLE, 'Enable Cloudflare proxy (orange cloud)')
            ->addOption('priority', null, InputOption::VALUE_REQUIRED, 'MX/SRV priority (0-65535)');
    }

    // ----
    // Execution
    // ----

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        parent::execute($input, $output);

        $this->h1('Set Cloudflare DNS Record');

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
                    $deets['proxied'],
                    $deets['priority']
                ),
                'Setting DNS record...'
            );

            $actionVerb = 'created' === $result['action'] ? 'Created' : 'Updated';
            $this->yay("{$actionVerb} {$deets['type']} record: {$fullName} -> {$deets['value']}");
        } catch (\RuntimeException $e) {
            $this->nay($e->getMessage());

            return Command::FAILURE;
        }

        //
        // Show command replay
        // ----

        $replayOptions = [
            'zone' => $deets['zone'],
            'type' => $deets['type'],
            'name' => $deets['name'],
            'value' => $deets['value'],
            'ttl' => $deets['ttl'],
        ];

        // Only include proxied for types that support it
        if (in_array($deets['type'], CloudflareDnsService::PROXIABLE_TYPES, true)) {
            $replayOptions['proxied'] = $deets['proxied'];
        }

        // Only include priority for MX records
        if (null !== $deets['priority']) {
            $replayOptions['priority'] = $deets['priority'];
        }

        $this->commandReplay($replayOptions);

        return Command::SUCCESS;
    }

    // ----
    // Helpers
    // ----

    /**
     * Gather DNS record details from user input or CLI options.
     *
     * @return array{zone: string, type: string, name: string, value: string, ttl: int, proxied: bool, priority: int|null}|int
     */
    private function gatherRecordDeets(): array|int
    {
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
                    hint: 'e.g., @ for root, www for subdomain',
                    validate: $validate
                ),
                fn ($value) => $this->validateRecordNameInput($value)
            );

            $valueHint = $this->getValueHintForType($type);

            /** @var string $value */
            $value = $this->io->getValidatedOptionOrPrompt(
                'value',
                fn ($validate) => $this->io->promptText(
                    label: 'Record value:',
                    placeholder: $this->getValuePlaceholderForType($type),
                    required: true,
                    hint: $valueHint,
                    validate: $validate
                ),
                fn ($v) => $this->validateRecordValueInput($v)
            );

            /** @var string $ttlInput */
            $ttlInput = $this->io->getValidatedOptionOrPrompt(
                'ttl',
                fn ($validate) => $this->io->promptText(
                    label: 'TTL (seconds):',
                    default: '1',
                    required: true,
                    hint: '1 = auto (recommended when proxied)',
                    validate: $validate
                ),
                fn ($v) => $this->validateTtlInput($v)
            );

            $ttl = (int) $ttlInput;

            // Proxied (only for A, AAAA, CNAME)
            $proxied = false;
            if (in_array($type, CloudflareDnsService::PROXIABLE_TYPES, true)) {
                $proxied = $this->io->getBooleanOptionOrPrompt(
                    'proxied',
                    fn () => $this->io->promptConfirm(
                        label: 'Enable Cloudflare proxy (orange cloud)?',
                        default: true,
                        hint: 'Hides origin IP, enables CDN and DDoS protection'
                    )
                );
            }

            // Priority (only for MX, SRV)
            $priority = null;
            if (in_array($type, ['MX', 'SRV'], true)) {
                /** @var string $priorityInput */
                $priorityInput = $this->io->getValidatedOptionOrPrompt(
                    'priority',
                    fn ($validate) => $this->io->promptText(
                        label: 'Priority:',
                        default: '10',
                        required: true,
                        hint: 'Lower values = higher priority',
                        validate: $validate
                    ),
                    fn ($v) => $this->validatePriorityInput($v)
                );

                $priority = (int) $priorityInput;
            }
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
            'priority' => $priority,
        ];
    }

    /**
     * Get hint text for value field based on record type.
     */
    private function getValueHintForType(string $type): string
    {
        return match ($type) {
            'A' => 'IPv4 address (e.g., 192.0.2.1)',
            'AAAA' => 'IPv6 address (e.g., 2001:db8::1)',
            'CNAME' => 'Target hostname (e.g., example.com)',
            'MX' => 'Mail server hostname (e.g., mail.example.com)',
            'TXT' => 'Text content (e.g., v=spf1 include:_spf.google.com ~all)',
            'NS' => 'Nameserver hostname',
            'SRV' => 'Format: weight port target (e.g., 5 5060 sipserver.example.com)',
            'CAA' => 'Format: flags tag value (e.g., 0 issue "letsencrypt.org")',
            default => 'Record value',
        };
    }

    /**
     * Get placeholder text for value field based on record type.
     */
    private function getValuePlaceholderForType(string $type): string
    {
        return match ($type) {
            'A' => '192.0.2.1',
            'AAAA' => '2001:db8::1',
            'CNAME' => 'target.example.com',
            'MX' => 'mail.example.com',
            'TXT' => 'v=spf1 ...',
            'NS' => 'ns1.example.com',
            default => '',
        };
    }
}
