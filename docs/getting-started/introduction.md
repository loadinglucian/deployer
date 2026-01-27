# Introduction

This is DeployerPHP, a set of command-line interface (CLI) tools for provisioning, installing, and deploying servers and sites using PHP. It serves as an open-source alternative to services like Laravel Forge and Ploi.

## Installation

DeployerPHP is built around Symfony Console and comes bundled as a Composer package so you can easily install and use it as part of your existing workflow:

```shell
# Install as a dev dependency
composer require --dev loadinglucian/deployer-php

# Add an alias for convenience
alias deployer="./vendor/bin/deployer"
```

> [!TIP]
> Add the alias to your shell profile (`~/.bashrc`, `~/.zshrc`) to make it permanent.

## Requirements

DeployerPHP has some basic requirements:

- At least PHP 8.2
- The `pcntl` PHP extension (if you want to use the `server:ssh` command)

Your target servers should run a supported Linux distribution:

- Ubuntu LTS (such as 24.04, 26.04, etc., no interim releases like 25.04)
- Debian 12 or newer

## Meet The Commands

Once installed, run the `list` command to see all the other available commands:

```shell
deployer list
```

DeployerPHP has a wide range of commands and capabilities. All the commands are grouped by a `namespaces:*` that represents what each group manages:

- **`server:*`**: Add, install, delete, and manage servers
- **`site:*`**: Create, deploy, delete, and manage sites
- **`cron:*`** and **`supervisor:*`**: Scheduled tasks and background processes
- **`nginx:*`** and **`php:*`**: Web server and PHP-FPM control
- **`mariadb:*`**, **`mysql:*`**, **`postgresql:*`**: Database services
- **`memcached:*`**, **`redis:*`**, **`valkey:*`**: Cache services
- **`scaffold:*`**: Generate cron, hook, supervisor, and AI rules config files
- **`aws:*`**, **`cf:*`**, **`do:*`**: Cloud provider integrations (AWS, Cloudflare, DigitalOcean)

Don't worry about what each of these does. For now, just focus on how everything is laid out and organized. We'll cover each of them in detail in other sections of the documentation.

Read [Zero to Deploy](/docs/getting-started/zero-to-deploy.md) next to get started with your first deployment!
