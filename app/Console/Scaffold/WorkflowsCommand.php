<?php

declare(strict_types=1);

namespace DeployerPHP\Console\Scaffold;

use DeployerPHP\Contracts\BaseCommand;
use DeployerPHP\Traits\ScaffoldsTrait;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'scaffold:workflows',
    description: 'Scaffold GitHub workflow files for automated preview site deployments'
)]
class WorkflowsCommand extends BaseCommand
{
    use ScaffoldsTrait;

    // ----
    // Configuration
    // ----

    protected function configure(): void
    {
        parent::configure();
        $this->configureScaffoldOptions();
    }

    // ----
    // Execution
    // ----

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        parent::execute($input, $output);

        $this->h1('Scaffold GitHub Workflows');

        return $this->scaffoldFiles('workflows');
    }

    // ----
    // Hook Overrides
    // ----

    /**
     * Build target path for GitHub workflows directory.
     *
     * @param array<string, mixed> $context
     */
    protected function buildTargetPath(string $destinationDir, string $type, array $context): string
    {
        return $this->fs->joinPaths($destinationDir, '.github', 'workflows');
    }
}
