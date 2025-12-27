# Site Management

- [Introduction](#introduction)
- [Creating a Site](#creating-a-site)
- [Deploying a Site](#deploying-a-site)
    - [Deployment Hooks](#deployment-hooks)
    - [Release Management](#release-management)
- [Enabling HTTPS](#enabling-https)
- [Shared Files](#shared-files)
    - [Pushing Files](#pushing-files)
    - [Pulling Files](#pulling-files)
- [Viewing Logs](#viewing-logs)
- [SSH Access](#ssh-access)
- [Rollbacks](#rollbacks)
- [Deleting a Site](#deleting-a-site)
- [Cron Jobs](#cron-jobs)
    - [Creating Cron Jobs](#creating-cron-jobs)
    - [Syncing Cron Jobs](#syncing-cron-jobs)
    - [Viewing Cron Logs](#viewing-cron-logs)
    - [Deleting Cron Jobs](#deleting-cron-jobs)
- [Supervisor Processes](#supervisor-processes)
    - [Creating Processes](#creating-processes)
    - [Managing Processes](#managing-processes)
    - [Syncing Processes](#syncing-processes)
    - [Viewing Supervisor Logs](#viewing-supervisor-logs)
    - [Deleting Processes](#deleting-processes)
- [Scaffolding](#scaffolding)
    - [Scaffolding Hooks](#scaffolding-hooks)
    - [Scaffolding Crons](#scaffolding-crons)
    - [Scaffolding Supervisors](#scaffolding-supervisors)

<a name="introduction"></a>

## Introduction

Sites are applications deployed to your servers. DeployerPHP manages the complete lifecycle from creation through deployment, including automation like cron jobs and background processes.

Sites are stored in your local inventory and linked to a server. Each site has its own Nginx configuration, PHP-FPM pool, and directory structure.

<a name="creating-a-site"></a>

## Creating a Site

The `site:create` command sets up a new site on a server:

```bash
deployer site:create
```

You'll be prompted for:

| Option          | Description                     |
| --------------- | ------------------------------- |
| `--server`      | Server from your inventory      |
| `--domain`      | Site domain (e.g., example.com) |
| `--php-version` | PHP version for this site       |
| `--www`         | WWW handling mode               |

WWW handling options:

- **none** - Only serve the exact domain
- **www** - Redirect non-www to www
- **non-www** - Redirect www to non-www

For automation:

```bash
deployer site:create \
    --server=production \
    --domain=example.com \
    --php-version=8.3 \
    --www=non-www
```

This creates the directory structure at `/home/deployer/sites/example.com/`:

```
example.com/
├── current -> releases/20240115120000
├── releases/
│   └── 20240115120000/
├── shared/
│   └── .env
└── .dep/
```

<a name="deploying-a-site"></a>

## Deploying a Site

The `site:deploy` command deploys your application from a Git repository:

```bash
deployer site:deploy
```

Options:

| Option     | Description        |
| ---------- | ------------------ |
| `--site`   | Site domain        |
| `--repo`   | Git repository URL |
| `--branch` | Branch to deploy   |

Example:

```bash
deployer site:deploy \
    --site=example.com \
    --repo=git@github.com:user/app.git \
    --branch=main
```

<a name="deployment-hooks"></a>

### Deployment Hooks

DeployerPHP runs deployment hooks from your repository's `.deployer/hooks/` directory:

| Hook             | Purpose                            | Example Tasks                                 |
| ---------------- | ---------------------------------- | --------------------------------------------- |
| `1-building.sh`  | Install dependencies, build assets | composer install, npm run build               |
| `2-releasing.sh` | Prepare the release                | php artisan migrate, php artisan config:cache |
| `3-finishing.sh` | Post-release tasks                 | php artisan queue:restart                     |

Example `1-building.sh` for Laravel:

```bash
#!/bin/bash
set -e

composer install --no-dev --optimize-autoloader
bun install
bun run build
```

Example `2-releasing.sh`:

```bash
#!/bin/bash
set -e

php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

> [!NOTE]
> Hooks run in the release directory with the `deployer` user. Use `set -e` to stop deployment on errors.

<a name="release-management"></a>

### Release Management

Each deployment creates a new release directory with a timestamp. The `current` symlink points to the active release. By default, DeployerPHP keeps the 5 most recent releases.

The `shared/` directory contains files that persist across releases (like `.env`). These are symlinked into each release.

<a name="enabling-https"></a>

## Enabling HTTPS

The `site:https` command installs an SSL certificate using Certbot:

```bash
deployer site:https --site=example.com
```

This:

1. Installs Certbot if not present
2. Obtains a Let's Encrypt certificate
3. Configures Nginx for HTTPS
4. Sets up automatic certificate renewal

> [!NOTE]
> Your domain's DNS must point to your server before running this command.

<a name="shared-files"></a>

## Shared Files

Shared files persist across deployments. Common examples include `.env` files, user uploads, and configuration files.

<a name="pushing-files"></a>

### Pushing Files

The `site:shared:push` command uploads files to the shared directory:

```bash
deployer site:shared:push --site=example.com
```

You'll be prompted to select files from your local directory. Selected files are uploaded to `/home/deployer/sites/example.com/shared/`.

<a name="pulling-files"></a>

### Pulling Files

The `site:shared:pull` command downloads files from the shared directory:

```bash
deployer site:shared:pull --site=example.com
```

This is useful for backing up configuration or syncing environment files.

<a name="viewing-logs"></a>

## Viewing Logs

The `site:logs` command displays site-specific logs:

```bash
deployer site:logs --site=example.com --lines=100
```

This shows:

- Nginx access logs for the domain
- Nginx error logs
- Cron job output
- Supervisor process output

<a name="ssh-access"></a>

## SSH Access

The `site:ssh` command opens an SSH session in the site's directory:

```bash
deployer site:ssh --site=example.com
```

You'll be logged in as the `deployer` user in `/home/deployer/sites/example.com/current/`.

<a name="rollbacks"></a>

## Rollbacks

DeployerPHP follows a forward-only deployment philosophy:

```bash
deployer site:rollback --site=example.com
```

Rather than reverting to a previous release, this command provides guidance on fixing issues and redeploying. The reasoning is:

- Rollbacks can leave databases in inconsistent states
- Forward-only encourages proper testing before deployment
- Quick fixes and redeployments are often faster than rollbacks

If you need to revert code, revert in Git and redeploy.

<a name="deleting-a-site"></a>

## Deleting a Site

The `site:delete` command removes a site:

```bash
deployer site:delete --site=example.com
```

Safety features:

1. **Type-to-confirm** - You must type the domain to confirm
2. **Double confirmation** - An additional Yes/No prompt

Options:

| Option             | Description                      |
| ------------------ | -------------------------------- |
| `--site`           | Site domain to delete            |
| `--force`          | Skip type-to-confirm             |
| `--yes`            | Skip Yes/No confirmation         |
| `--inventory-only` | Only remove from local inventory |

> [!WARNING]
> This permanently deletes all site files, releases, and shared data from the server.

<a name="cron-jobs"></a>

## Cron Jobs

Cron jobs run scheduled tasks for your site. DeployerPHP manages cron definitions in your repository and syncs them to the server.

<a name="creating-cron-jobs"></a>

### Creating Cron Jobs

The `cron:create` command adds a cron job to a site:

```bash
deployer cron:create --site=example.com
```

You'll be prompted for:

- **Name** - Identifier for the cron job
- **Schedule** - Cron expression (e.g., `* * * * *` for every minute)
- **Command** - The command to run
- **User** - User to run as (default: deployer)

For Laravel scheduled tasks:

```bash
deployer cron:create \
    --site=example.com \
    --name=scheduler \
    --schedule="* * * * *" \
    --command="php artisan schedule:run"
```

<a name="syncing-cron-jobs"></a>

### Syncing Cron Jobs

The `cron:sync` command syncs cron definitions from your repository to the server:

```bash
deployer cron:sync --site=example.com
```

Define crons in `.deployer/crons.yml`:

```yaml
scheduler:
    schedule: '* * * * *'
    command: 'php artisan schedule:run'

cleanup:
    schedule: '0 3 * * *'
    command: 'php artisan cleanup:old-records'
```

<a name="viewing-cron-logs"></a>

### Viewing Cron Logs

```bash
deployer cron:logs --site=example.com --lines=100
```

<a name="deleting-cron-jobs"></a>

### Deleting Cron Jobs

```bash
deployer cron:delete --site=example.com --name=cleanup
```

<a name="supervisor-processes"></a>

## Supervisor Processes

Supervisor manages long-running processes like queue workers, WebSocket servers, or custom daemons.

<a name="creating-processes"></a>

### Creating Processes

The `supervisor:create` command adds a supervised process:

```bash
deployer supervisor:create --site=example.com
```

You'll be prompted for:

- **Name** - Process identifier
- **Command** - The command to run
- **Processes** - Number of parallel processes
- **User** - User to run as

For Laravel queue workers:

```bash
deployer supervisor:create \
    --site=example.com \
    --name=queue-worker \
    --command="php artisan queue:work --sleep=3 --tries=3" \
    --processes=2
```

<a name="managing-processes"></a>

### Managing Processes

```bash
# Start a process
deployer supervisor:start --site=example.com --name=queue-worker

# Stop a process
deployer supervisor:stop --site=example.com --name=queue-worker

# Restart a process (useful after deployments)
deployer supervisor:restart --site=example.com --name=queue-worker
```

<a name="syncing-processes"></a>

### Syncing Processes

The `supervisor:sync` command syncs process definitions from your repository:

```bash
deployer supervisor:sync --site=example.com
```

Define supervisors in `.deployer/supervisors.yml`:

```yaml
queue-worker:
    command: 'php artisan queue:work --sleep=3 --tries=3'
    processes: 2

websocket:
    command: 'php artisan websockets:serve'
    processes: 1
```

<a name="viewing-supervisor-logs"></a>

### Viewing Supervisor Logs

```bash
deployer supervisor:logs --site=example.com --name=queue-worker --lines=100
```

<a name="deleting-processes"></a>

### Deleting Processes

```bash
deployer supervisor:delete --site=example.com --name=queue-worker
```

<a name="scaffolding"></a>

## Scaffolding

Scaffolding commands generate the `.deployer/` directory structure in your project.

<a name="scaffolding-hooks"></a>

### Scaffolding Hooks

```bash
deployer scaffold:hooks
```

This creates:

```
.deployer/
└── hooks/
    ├── 1-building.sh
    ├── 2-releasing.sh
    └── 3-finishing.sh
```

Templates are pre-filled for common PHP/Laravel workflows.

<a name="scaffolding-crons"></a>

### Scaffolding Crons

```bash
deployer scaffold:crons
```

This creates `.deployer/crons.yml` with example cron definitions.

<a name="scaffolding-supervisors"></a>

### Scaffolding Supervisors

```bash
deployer scaffold:supervisors
```

This creates `.deployer/supervisors.yml` with example supervisor definitions.
