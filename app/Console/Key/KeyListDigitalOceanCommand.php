<?php

declare(strict_types=1);

namespace Bigpixelrocket\DeployerPHP\Console\Key;

use Bigpixelrocket\DeployerPHP\Contracts\BaseCommand;
use Bigpixelrocket\DeployerPHP\Traits\DigitalOceanTrait;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'key:list:digitalocean',
    description: 'List public SSH keys in DigitalOcean'
)]
class KeyListDigitalOceanCommand extends BaseCommand
{
    use DigitalOceanTrait;

    // ----
    // Execution
    // ----

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        parent::execute($input, $output);

        $this->heading('List Public SSH Keys in DigitalOcean');

        //
        // Retrieve DigitalOcean account data
        // ----

        if ($this->initializeDigitalOceanAPI() === Command::FAILURE) {
            return Command::FAILURE;
        }

        $keys = $this->ensureKeysAvailable();

        if (is_int($keys)) {
            return Command::FAILURE;
        }

        //
        // Display keys
        // ----

        foreach ($keys as $keyId => $description) {
            $this->io->writeln("  <fg=cyan>{$keyId}</> - {$description}");
        }

        $this->io->writeln('');

        return Command::SUCCESS;
    }
}
