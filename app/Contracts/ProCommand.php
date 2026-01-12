<?php

declare(strict_types=1);

namespace DeployerPHP\Contracts;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Base command for Pro features with subscription banner.
 *
 * All Pro commands should extend this class instead of BaseCommand
 * to automatically display the Pro features banner.
 */
abstract class ProCommand extends BaseCommand
{
    // ----
    // Execution
    // ----

    /**
     * Display Pro banner after base execution.
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        parent::execute($input, $output);

        $this->proBanner();

        return Command::SUCCESS;
    }

    // ----
    // IO Helpers
    // ----

    /**
     * Display the Pro features banner.
     */
    protected function proBanner(): void
    {
        $this->out([
            '',
            'This is a Pro command!',
            '',
            'Pro commands offer convenience features and integrations with',
            'third-party cloud providers.',
            '',
            'Pro commands are free for now but a modest subscription may be',
            'introduced in the future to support development. Core features',
            'will always be free.',
        ]);
    }
}
