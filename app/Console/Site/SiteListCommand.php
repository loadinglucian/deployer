<?php

declare(strict_types=1);

namespace Bigpixelrocket\DeployerPHP\Console\Site;

use Bigpixelrocket\DeployerPHP\Contracts\BaseCommand;
use Bigpixelrocket\DeployerPHP\Traits\SitesTrait;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'site:list',
    description: 'List sites in the inventory'
)]
class SiteListCommand extends BaseCommand
{
    use SitesTrait;

    // -------------------------------------------------------------------------------
    //
    // Execution
    //
    // -------------------------------------------------------------------------------

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        parent::execute($input, $output);

        $this->heading('List Sites');

        //
        // Get all sites
        // -------------------------------------------------------------------------------

        $allSites = $this->ensureSitesAvailable();

        if (is_int($allSites)) {
            return $allSites;
        }

        //
        // Display sites
        // -------------------------------------------------------------------------------

        foreach ($allSites as $count => $site) {
            $this->displaySiteDeets($site);

            if ($count < count($allSites) - 1) {
                $this->io->writeln([
                        '  ───',
                        '',
                    ]);
            }
        }

        return Command::SUCCESS;
    }

}
