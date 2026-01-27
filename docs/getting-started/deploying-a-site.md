# Deploying a Site

Before you can deploy, you'll need deployment hooks in your project repository. DeployerPHP uses a hook-based deployment system that gives you full control over the build and release process.

## Scaffold Deployment Hooks

From your project directory, run the `scaffold:hooks` command to generate the deployment hooks:

```shell
deployer scaffold:hooks
```

This creates three hook scripts in your project's `.deployer/hooks/` directory:

- **1-building.sh** - Runs after cloning. Installs Composer dependencies and builds frontend assets
- **2-releasing.sh** - Runs before activation. Handles migrations, shared storage, and framework-specific optimizations
- **3-finishing.sh** - Runs after the new release is live. Use for cleanup or notifications

> [!NOTE]
> The generated hooks include sensible defaults for modern PHP frameworks such as Laravel, Symfony, and CodeIgniter. Review and customize them for your specific application needs, then commit them to your repository.

## Deploy Your Site

Once your hooks are committed and pushed, run the `site:deploy` command:

```shell
deployer site:deploy
```

DeployerPHP will prompt you for:

- **Site** - Select the site to deploy
- **Git repository URL** - Your repository's SSH URL (auto-detected from your local git remote)
- **Branch** - The branch to deploy (auto-detected from your current branch)
- **Confirmation** - Final confirmation before deployment

The deployment process will:

1. Clone your repository to a new release directory
2. Run `1-building.sh` to install dependencies and build assets
3. Link shared resources (`.env`, `storage/`, etc.) into the release
4. Run `2-releasing.sh` to prepare the release (migrations, caching)
5. Activate the new release by updating the `current` symlink
6. Run `3-finishing.sh` for any post-deployment tasks
7. Reload PHP-FPM to pick up the new code
8. Clean up old releases (keeps the last 5 by default)

> [!NOTE]
> DeployerPHP uses a release-based deployment strategy. Each deployment creates a new release directory, and a `current` symlink points to the active release. This allows for instant rollbacks and zero-downtime deployments.

## Upload Shared Files

After your first deployment, you'll need to upload environment files and other shared data that shouldn't be in version control. Without a `.env` file, most PHP applications won't start.

**Prepare your local shared directory:**

Create a `.deployer/shared/` directory in your project and add your environment file:

```shell
mkdir -p .deployer/shared
cp .env.production .deployer/shared/.env
```

> [!TIP]
> Add `.deployer/shared/` to your `.gitignore` to avoid committing sensitive files.

**Push shared files to the server:**

```shell
deployer site:shared:push
```

DeployerPHP will prompt you for the local file path and the remote filename. The file is uploaded to the server's `shared/` directory, which persists across deployments and is automatically symlinked into each release.

**Pull shared files from the server:**

If you need to download the current `.env` or other shared files from the server (useful when syncing environments or debugging), use:

```shell
deployer site:shared:pull
```

For more details on shared file management, see [Shared Files](/docs/sites/shared-files) in the Site Management documentation.
