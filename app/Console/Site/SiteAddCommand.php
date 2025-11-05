<?php

declare(strict_types=1);

namespace Bigpixelrocket\DeployerPHP\Console\Site;

use Bigpixelrocket\DeployerPHP\Contracts\BaseCommand;
use Bigpixelrocket\DeployerPHP\DTOs\ServerDTO;
use Bigpixelrocket\DeployerPHP\DTOs\SiteDTO;
use Bigpixelrocket\DeployerPHP\Traits\ServersTrait;
use Bigpixelrocket\DeployerPHP\Traits\SitesTrait;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'site:add', description: 'Add a new site to the inventory')]
class SiteAddCommand extends BaseCommand
{
    use ServersTrait;
    use SitesTrait;

    // -------------------------------------------------------------------------------
    //
    // Configuration
    //
    // -------------------------------------------------------------------------------

    protected function configure(): void
    {
        parent::configure();

        $this
            ->addOption('domain', null, InputOption::VALUE_REQUIRED, 'Domain name')
            ->addOption('source', null, InputOption::VALUE_REQUIRED, 'Site source: git or local')
            ->addOption('repo', null, InputOption::VALUE_REQUIRED, 'Git repository URL (for git sites)')
            ->addOption('branch', null, InputOption::VALUE_REQUIRED, 'Git branch name (for git sites)')
            ->addOption('server', null, InputOption::VALUE_REQUIRED, 'Server name');
    }

    // -------------------------------------------------------------------------------
    //
    // Execution
    //
    // -------------------------------------------------------------------------------

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        parent::execute($input, $output);

        $this->heading('Add New Site');

        //
        // Gather site details
        // -------------------------------------------------------------------------------

        $deets = $this->gatherSiteDeets();

        if ($deets === null) {
            return Command::FAILURE;
        }

        [
            'domain' => $domain,
            'siteSource' => $siteSource,
            'repo' => $repo,
            'branch' => $branch,
            'server' => $server,
        ] = $deets;

        //
        // Display site details
        // -------------------------------------------------------------------------------

        $site = new SiteDTO(
            domain: $domain,
            repo: $repo,
            branch: $branch,
            servers: [$server->name]
        );

        $this->displaySiteDeets($site);

        //
        // Save to inventory
        // -------------------------------------------------------------------------------

        try {
            $this->sites->create($site);
        } catch (\RuntimeException $e) {
            $this->nay($e->getMessage());

            return Command::FAILURE;
        }

        $this->yay('Site added to inventory');

        //
        // Show command replay
        // -------------------------------------------------------------------------------

        $hintOptions = [
            'domain' => $domain,
            'source' => $siteSource,
            'server' => $server->name,
        ];

        if ($siteSource !== 'local') {
            $hintOptions['repo'] = $repo;
            $hintOptions['branch'] = $branch;
        }

        $this->showCommandReplay('site:add', $hintOptions);

        return Command::SUCCESS;
    }

    // -------------------------------------------------------------------------------
    //
    // Helpers
    //
    // -------------------------------------------------------------------------------

    /**
     * Gather site details from user input or CLI options.
     *
     * @return array{domain: string, siteSource: string, repo: ?string, branch: ?string, server: ServerDTO}|null
     */
    protected function gatherSiteDeets(): ?array
    {
        //
        // Select server
        // -------------------------------------------------------------------------------

        $server = $this->selectServer();

        if (is_int($server)) {
            return null;
        }

        //
        // Gather site details
        // -------------------------------------------------------------------------------

        /** @var string|null $domain */
        $domain = $this->io->getValidatedOptionOrPrompt(
            'domain',
            fn ($validate) => $this->io->promptText(
                label: 'Domain name:',
                placeholder: 'example.com',
                required: true,
                validate: $validate
            ),
            fn ($value) => $this->validateSiteDomain($value)
        );

        if ($domain === null) {
            return null;
        }

        //
        // Select site source
        // -------------------------------------------------------------------------------

        /** @var string $siteSource */
        $siteSource = $this->io->getOptionOrPrompt(
            'source',
            fn (): string => (string) $this->io->promptSelect(
                label: 'Deploy from:',
                options: ['git' => 'Git Repository', 'local' => 'Local files'],
                default: 'git'
            )
        );

        $isLocal = $siteSource === 'local';

        //
        // Gather git-specific details
        // -------------------------------------------------------------------------------

        $repo = null;
        $branch = null;

        if (!$isLocal) {
            $defaultRepo = $this->git->detectRemoteUrl() ?? '';

            /** @var string|null $repo */
            $repo = $this->io->getValidatedOptionOrPrompt(
                'repo',
                fn ($validate) => $this->io->promptText(
                    label: 'Git repository URL:',
                    placeholder: 'git@github.com:user/repo.git',
                    default: $defaultRepo,
                    required: true,
                    validate: $validate
                ),
                fn ($value) => $this->validateSiteRepo($value)
            );

            if ($repo === null) {
                return null;
            }

            $defaultBranch = $this->git->detectCurrentBranch() ?? 'main';

            /** @var string|null $branch */
            $branch = $this->io->getValidatedOptionOrPrompt(
                'branch',
                fn ($validate) => $this->io->promptText(
                    label: 'Git branch:',
                    placeholder: $defaultBranch,
                    default: $defaultBranch,
                    required: true,
                    validate: $validate
                ),
                fn ($value) => $this->validateSiteBranch($value)
            );

            if ($branch === null) {
                return null;
            }
        }

        return [
            'domain' => $domain,
            'siteSource' => $siteSource,
            'repo' => $repo,
            'branch' => $branch,
            'server' => $server,
        ];
    }
}
