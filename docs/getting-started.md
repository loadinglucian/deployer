# Getting Started

- [Installation](#installation)
- [Requirements](#requirements)
- [Your First Deployment](#your-first-deployment)
    - [Add Your Server](#add-your-server)
    - [Install Your Server](#install-your-server)
    - [Create Your Site](#create-your-site)
    - [Deploy Your Site](#deploy-your-site)

<a name="installation"></a>
## Installation

DeployerPHP is installed via Composer. You can install it globally or as a project dependency:

```bash
# Global installation (recommended)
composer global require loadinglucian/deployer-php

# Or as a project dependency
composer require loadinglucian/deployer-php
```

After global installation, make sure Composer's global bin directory is in your system's PATH. You can then run DeployerPHP from anywhere:

```bash
deployer list
```

If installed as a project dependency, run it via the vendor bin:

```bash
./vendor/bin/deployer list
```

<a name="requirements"></a>
## Requirements

DeployerPHP requires the following:

- PHP 8.2 or higher
- The `pcntl` PHP extension (for SSH sessions)
- An SSH key pair for server authentication

Your target servers should be running a supported Linux distribution:

- Ubuntu 22.04 LTS or newer
- Debian 11 or newer

<a name="your-first-deployment"></a>
## Your First Deployment

This guide walks you through deploying your first PHP application with DeployerPHP. The typical workflow is:

1. Add a server to your inventory
2. Install the server with PHP and required services
3. Create a site on the server
4. Deploy your application

<a name="add-your-server"></a>
### Add Your Server

First, add your server to DeployerPHP's inventory. You'll need SSH access to the server:

```bash
deployer server:add
```

DeployerPHP will prompt you for:

- **Server name** - A friendly name for your server (e.g., "production", "web1")
- **Host** - The IP address or hostname of your server
- **Port** - SSH port (default: 22)
- **Username** - SSH username (default: root)
- **Private key path** - Path to your SSH private key

Once connected, DeployerPHP gathers information about your server's capabilities and stores it in your local inventory.

<a name="install-your-server"></a>
### Install Your Server

Next, install the base packages and PHP on your server:

```bash
deployer server:install
```

This command will:

- Install essential packages (git, curl, unzip, etc.)
- Install Nginx as the web server
- Prompt you to select a PHP version and extensions
- Install Bun (JavaScript runtime for asset building)
- Create a `deployer` user for deployments
- Generate an SSH deploy key for Git access

> [!NOTE]
> After installation, add the displayed deploy key to your Git provider (GitHub, GitLab, Bitbucket) to allow the server to pull your repositories.

<a name="create-your-site"></a>
### Create Your Site

With your server installed, create a site:

```bash
deployer site:create
```

You'll be prompted for:

- **Server** - Select from your inventory
- **Domain** - Your site's domain (e.g., "example.com")
- **PHP version** - Select from installed versions
- **WWW handling** - How to handle www subdomain

DeployerPHP creates the Nginx configuration and directory structure for your site.

<a name="deploy-your-site"></a>
### Deploy Your Site

Finally, deploy your application:

```bash
deployer site:deploy
```

You'll be prompted for:

- **Site** - Select from your sites
- **Repository** - Git repository URL
- **Branch** - Branch to deploy (default: main)

DeployerPHP uses deployment hooks to customize the build process. Create a `.deployer/hooks/` directory in your repository with these scripts:

- `1-building.sh` - Install dependencies, build assets
- `2-releasing.sh` - Run migrations, clear caches
- `3-finishing.sh` - Restart queues, cleanup

Example `1-building.sh` for a Laravel application:

```bash
#!/bin/bash
composer install --no-dev --optimize-autoloader
bun install
bun run build
```

That's it! Your application is now deployed and accessible at your domain.
