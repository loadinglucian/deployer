# Zero to Deploy

This guide will help you deploy your first application using DeployerPHP. By the end, you'll have a fully configured Nginx server with Let's Encrypt HTTPS support, multiple versions of PHP running in parallel, Bun as a JavaScript runtime, and your PHP application running on your domain.

It may seem overwhelming, but you only need to run a few simple commands and respond to a couple of interactive prompts. DeployerPHP will set everything up for you.

## Step 1: Add A Server

Before we can deploy anything we'll need a fresh new server to deploy to. You can use any physical server, VPS, or cloud instance as long as you can connect to it via SSH and it is running a version of `Ubuntu LTS >= 24.04` or `Debian >= 12` as specified by the [Requirements](/docs/getting-started/introduction#requirements).

Run the `server:add` command to add a new server to your inventory:

```shell
deployer server:add
```

> [!NOTE]
> The inventory is a `deployer.yml` file that DeployerPHP initializes in your current working directory. This file is used to track your inventory of servers and sites that you add or create. Commands reference it, so you don't have to input your server and site details each time you run a command.

The command will ask for your server details, including the host/IP, SSH port, username, key, and a name for your new server. It will try connecting to the server and then confirm adding your server to the inventory:

```DeployerPHP nocopy
▒ ≡ DeployerPHP ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
▒
.
.
.
▒ Name: web1
▒ Host: 123.456.789.123
▒ Port: 22
▒ User: root
▒ Key:  /home/lucian/.ssh/id_ed25519
▒ ───
▒ ✓ Server added to inventory
▒ • Run server:info to view server information
▒ • Or run server:install to install your new server

Non-interactive command replay:
───────────────────────────────────────────────────────────────────────────
$> deployer server:add  \
  --name='web1' \
  --host='123.456.789.123' \
  --port='22' \
  --username='root' \
  --private-key-path='/home/lucian/.ssh/id_ed25519'
```

> [!TIP]
> Every DeployerPHP command provides a non-interactive command replay at the end. This replay displays the exact command along with all your interactive prompt responses filled in, making it easy to copy and use in scripts or CI pipelines.

## Step 2: Install The Server

With your new server in the inventory, run the `server:install` command to install and configure everything necessary to deploy and host your PHP applications:

```shell
deployer server:install
```

This installs and configures:

- **Base packages** - git, curl, unzip, and essential utilities
- **System timezone** - Ensures consistent timestamps across services
- **Nginx** - Web server with optimized configuration
- **PHP** - Your selected version with extensions
- **Bun** - JavaScript runtime for building assets
- **Deployer user** - Dedicated user for deployments with SSH key

```DeployerPHP nocopy
▒ ≡ DeployerPHP ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
▒
.
.
.
▒ ───
▒ ✓ Server installation completed successfully
▒ • Run site:create to create a new site
▒ • View server and service info with server:info
▒ • Add the following public key to your Git provider (GitHub, GitLab, etc.) to enable deployments:

<the-key-will-be-displayed-here>

↑ IMPORTANT: Add this public key to your Git provider to enable access to your repositories.
.
.
.
```

> [!IMPORTANT]
> After installation, the command displays the server's public key. Add this key to your Git provider to enable access to your repositories.

### Multiple PHP Versions

You can run the `server:install` command at any time to install additional PHP versions or different extensions.

The `server:install` command is additive; it will never uninstall anything. Every other version or extension you installed previously will remain unchanged, so don't worry about loosing anything.

> [!NOTE]
> When you have multiple PHP versions installed, the `server:install` command will always prompt you to select the default PHP version you want to use for your server CLI.

For more information please read [Managing PHP](#).

### Installing Databases

Installing your preferred database, cache, or key-value store is as easy as running one of the dedicated install commands:

| Command              | Description                                       |
| -------------------- | ------------------------------------------------- |
| `mysql:install`      | Install MySQL database server                     |
| `mariadb:install`    | Install MariaDB database server                   |
| `postgresql:install` | Install PostgreSQL database server                |
| `redis:install`      | Install Redis key-value store                     |
| `valkey:install`     | Install Valkey key-value store (Redis-compatible) |
| `memcached:install`  | Install Memcached caching server                  |

For more information please read [Managing Databases](#).

## Step 3: Create a Site

The `site:create` command sets up a new site on a server:

```shell
deployer site:create
```

You'll be prompted for:

| Option          | Description                                            |
| --------------- | ------------------------------------------------------ |
| `--server`      | Server from your inventory                             |
| `--domain`      | Site domain (e.g., example.com)                        |
| `--php-version` | PHP version for this site                              |
| `--www-mode`    | WWW handling mode                                      |
| `--web-root`    | Public directory relative to project (default: public) |

WWW handling options:

- **redirect-to-root** - Redirect www to non-www
- **redirect-to-www** - Redirect non-www to www

> [!NOTE]
> The `--web-root` option specifies where Nginx should serve files from. Use `public` for Laravel/Symfony, `web` for Craft CMS, or `/` for WordPress and other applications that serve from the project root.

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

## Step 4: Deploy a Site

The `site:deploy` command deploys your application from a Git repository:

```shell
deployer site:deploy
```

Options:

| Option            | Description                             |
| ----------------- | --------------------------------------- |
| `--domain`        | Site domain                             |
| `--repo`          | Git repository URL                      |
| `--branch`        | Branch to deploy                        |
| `--keep-releases` | Number of releases to keep (default: 5) |
| `--yes`, `-y`     | Skip confirmation prompt                |

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

### Release Management

Each deployment creates a new release directory with a timestamp. The `current` symlink points to the active release. By default, DeployerPHP keeps the 5 most recent releases.

The `shared/` directory contains files that persist across releases (like `.env`). These are symlinked into each release.

## Step 5: Enable HTTPS

The `site:https` command installs an SSL certificate using Certbot:

```shell
deployer site:https
```

This:

1. Installs Certbot if not present
2. Obtains a Let's Encrypt certificate
3. Configures Nginx for HTTPS
4. Sets up automatic certificate renewal

> [!NOTE]
> Your domain's DNS must point to your server before running this command.
