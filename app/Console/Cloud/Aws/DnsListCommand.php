<?php

declare(strict_types=1);

namespace DeployerPHP\Console\Cloud\Aws;

use DeployerPHP\Contracts\BaseCommand;
use DeployerPHP\Exceptions\ValidationException;
use DeployerPHP\Traits\AwsDnsTrait;
use DeployerPHP\Traits\AwsTrait;
use DeployerPHP\Traits\DnsCommandTrait;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'aws:dns:list',
    description: 'List DNS records for an AWS Route53 hosted zone'
)]
class DnsListCommand extends BaseCommand
{
    use AwsDnsTrait;
    use AwsTrait;
    use DnsCommandTrait;

    // ----
    // Configuration
    // ----

    protected function configure(): void
    {
        parent::configure();

        $this
            ->addOption('zone', null, InputOption::VALUE_REQUIRED, 'Hosted zone ID or domain name')
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

        // Normalize records for display (AWS uses is_alias flag)
        $normalizedRecords = array_map(fn ($r) => [
            'type' => $r['type'],
            'name' => $r['name'],
            'value' => $r['value'],
            'ttl' => $r['is_alias'] ? 'ALIAS' : $r['ttl'],
        ], $records);

        $this->displayDnsRecords($normalizedRecords);

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
}
