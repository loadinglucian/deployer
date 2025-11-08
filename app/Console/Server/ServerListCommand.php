<?php

declare(strict_types=1);

namespace Bigpixelrocket\DeployerPHP\Console\Server;

use Bigpixelrocket\DeployerPHP\Contracts\BaseCommand;
use Bigpixelrocket\DeployerPHP\Traits\ServersTrait;
use Bigpixelrocket\DeployerPHP\Traits\SitesTrait;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'server:list',
    description: 'List servers in the inventory'
)]
class ServerListCommand extends BaseCommand
{
    use ServersTrait;
    use SitesTrait;

    // ----
    // Execution
    // ----

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        parent::execute($input, $output);

        $this->heading('List Servers');

        //
        // Get all servers
        // ----

        $allServers = $this->ensureServersAvailable();

        if (is_int($allServers)) {
            return $allServers;
        }

        //
        // Display servers
        // ----

        foreach ($allServers as $count => $server) {
            $this->displayServerDeets($server);

            if ($count < count($allServers) - 1) {
                $this->io->writeln([
                        '  ───',
                        '',
                    ]);
            }
        }

        return Command::SUCCESS;
    }

}
