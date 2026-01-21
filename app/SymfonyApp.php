<?php

declare(strict_types=1);

namespace DeployerPHP;

use DeployerPHP\Services\CommandDiscoveryService;
use DeployerPHP\Services\VersionService;
use Symfony\Component\Console\Application as SymfonyApplication;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * The Symfony application entry point.
 */
final class SymfonyApp extends SymfonyApplication
{
    private SymfonyStyle $io;

    public function __construct(
        private readonly Container $container,
        private readonly VersionService $versionService,
        private readonly CommandDiscoveryService $commandDiscovery,
    ) {
        $name = 'DeployerPHP';
        $version = $this->versionService->getVersion();
        parent::__construct($name, $version);

        $this->registerCommands();

        $this->setDefaultCommand('list');
    }

    //
    // Public
    // ----

    /**
     * Override default input definition to remove unwanted options.
     */
    protected function getDefaultInputDefinition(): InputDefinition
    {
        return new InputDefinition([
            new InputArgument('command', InputArgument::OPTIONAL, 'The command to execute'),
            new InputOption('--help', '-h', InputOption::VALUE_NONE, 'Display help for the given command. When no command is given display help for the list command'),
            new InputOption('--quiet', '-q', InputOption::VALUE_NONE, 'Do not output any message (except errors)'),
            new InputOption('--version', '-V', InputOption::VALUE_NONE, 'Display this application version'),
            new InputOption('--ansi', '', InputOption::VALUE_NEGATABLE, 'Force (or disable --no-ansi) ANSI output', null),
        ]);
    }

    /**
     * Override to hide default Symfony application name/version display.
     */
    public function getHelp(): string
    {
        return '';
    }

    /**
     * The main execution method in Symfony Console applications.
     */
    public function doRun(InputInterface $input, OutputInterface $output): int
    {
        $this->io = new SymfonyStyle($input, $output);

        $this->displayBanner();

        // If --version is requested, skip the rest (banner includes version)
        if ($input->hasParameterOption(['--version', '-V'], true)) {
            return Command::SUCCESS;
        }

        return parent::doRun($input, $output);
    }

    //
    // Private
    // ----

    /**
     * Display retro BBS-style ASCII art banner.
     */
    private function displayBanner(): void
    {
        // Skip banner in quiet mode
        if ($this->io->isQuiet()) {
            return;
        }

        $version = $this->getVersion();

        $this->io->writeln([
            '',
            '<fg=cyan>▒ ≡</> <fg=cyan;options=bold>DeployerPHP</> <fg=cyan>━━━━━━━━━━━━━━━━</><fg=bright-blue>━━━━━━━━━━━━━━━</><fg=magenta>━━━━━━━━━━━━━━━</><fg=gray>━━━━━━━━━━━━━━━━</>',
            '<fg=gray>▒ </>',
            '<fg=gray>▒ Ver: '.$version.'</>',
        ]);
    }

    /**
     * Register commands with auto-wired dependencies.
     */
    private function registerCommands(): void
    {
        $commandClasses = $this->commandDiscovery->discover();

        foreach ($commandClasses as $commandClass) {
            /** @var Command $commandInstance */
            $commandInstance = $this->container->build($commandClass);
            $this->addCommand($commandInstance);
        }
    }
}
