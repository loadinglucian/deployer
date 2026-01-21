# Server Management

- [Introduction](#introduction)
- [Adding a Server](#adding-a-server)
- [Installing a Server](#installing-a-server)
- [Server Information](#server-information)
- [Firewall Configuration](#firewall-configuration)
- [Running Commands](#running-commands)
- [SSH Access](#ssh-access)
- [Viewing Logs](#viewing-logs)
- [Deleting a Server](#deleting-a-server)

<a name="introduction"></a>

## Introduction

DeployerPHP maintains a local inventory of your servers. Before you can deploy sites or install services, you need to add servers to this inventory and install the base packages.

All server commands follow the pattern `server:<action>` and can be run interactively or with command-line options for automation.

> [!NOTE]
> Commands below run interactively. For automation, see [Command Replay](/docs/automation#command-replay).

<a name="adding-a-server"></a>

## Adding a Server

The `server:add` command adds a new server to your local inventory:

```bash
deployer server:add
```

You'll be prompted for connection details:

| Option               | Description          | Default    |
| -------------------- | -------------------- | ---------- |
| `--name`             | Server name          | (prompted) |
| `--host`             | Host/IP address      | (prompted) |
| `--port`             | SSH port             | 22         |
| `--private-key-path` | SSH private key path | (prompted) |
| `--username`         | SSH username         | root       |

When adding a server, DeployerPHP connects and gathers information about the server's distribution, hardware, and running services.

<a name="installing-a-server"></a>

## Installing a Server

The `server:install` command prepares a fresh server for hosting PHP applications:

```bash
deployer server:install
```

This installs and configures:

- **Base packages** - git, curl, unzip, and essential utilities
- **System timezone** - Ensures consistent timestamps across services
- **Nginx** - Web server with optimized configuration
- **PHP** - Your selected version with extensions
- **Bun** - JavaScript runtime for building assets
- **Deployer user** - Dedicated user for deployments with SSH key

Options:

| Option                  | Description                                                      |
| ----------------------- | ---------------------------------------------------------------- |
| `--server`              | Server name from inventory                                       |
| `--timezone`            | System timezone (e.g., America/New_York, UTC)                    |
| `--php-version`         | PHP version to install (e.g., 8.3)                               |
| `--php-extensions`      | Comma-separated list of extensions                               |
| `--php-default`         | Set as default PHP version (use `--no-php-default` to skip)      |
| `--generate-deploy-key` | Generate deploy key on server                                    |
| `--custom-deploy-key`   | Path to custom deploy key (expects `.pub` file at same location) |

When running interactively, you'll be prompted to select from common timezones (UTC, major US/EU/Asia cities) or choose "Other" to see the full list of available timezones on the server.

> [!NOTE]
> After installation, the command displays the server's public key. Add this key to your Git provider to enable access to your repositories.

<a name="server-information"></a>

## Server Information

The `server:info` command displays comprehensive information about a server:

```bash
deployer server:info
```

This shows:

- **Distribution** - OS name (Ubuntu, Debian)
- **User** - Permission level (root, sudo, or insufficient)
- **Hardware** - CPU cores, RAM, disk type
- **Services** - Listening ports with process names
- **Firewall** - UFW status and open ports
- **Nginx** - Version, active connections, and total requests
- **PHP** - Installed versions with extensions (default version marked)
- **PHP-FPM** - Per-version stats including pool, process counts, queue, and warnings
- **Sites** - Configured domains with HTTPS status and PHP version

<a name="firewall-configuration"></a>

## Firewall Configuration

The `server:firewall` command configures UFW firewall rules:

```bash
deployer server:firewall
```

DeployerPHP detects which services are listening on ports and lets you select which to allow through the firewall. HTTP (80) and HTTPS (443) are pre-selected by default, along with any ports already allowed in UFW.

You'll be prompted to:

- **Select server** - Choose which server to configure
- **Select ports** - Multi-select from detected listening services
- **Confirm changes** - Review and confirm the firewall configuration

Options:

| Option     | Description                                        |
| ---------- | -------------------------------------------------- |
| `--server` | Server name from inventory                         |
| `--allow`  | Comma-separated ports to allow (e.g., 80,443,3306) |
| `--yes`    | Skip confirmation prompt                           |

> [!NOTE]
> SSH access is always preserved regardless of your selections. The `--allow` option only accepts ports that have services actively listening on them.

<a name="running-commands"></a>

## Running Commands

The `server:run` command executes arbitrary shell commands on a server with real-time output streaming:

```bash
deployer server:run
```

You'll be prompted for the server and command. Output is streamed in real-time as the command executes on the remote server.

| Option      | Description        |
| ----------- | ------------------ |
| `--server`  | Server name        |
| `--command` | Command to execute |

This is useful for quick administrative tasks without opening a full SSH session.

<a name="ssh-access"></a>

## SSH Access

The `server:ssh` command opens an interactive SSH session to a server:

```bash
deployer server:ssh
```

You'll be prompted to select a server from your inventory, then dropped into a terminal session on the remote server. Use `exit` to return to your local machine.

| Option     | Description |
| ---------- | ----------- |
| `--server` | Server name |

<a name="viewing-logs"></a>

## Viewing Logs

The `server:logs` command provides a unified interface for viewing all logs on a server:

```bash
deployer server:logs
```

When run interactively, you'll see a multiselect prompt with all available log sources. You can select multiple sources at once to view logs from different services in a single command.

Available log sources include:

- **System logs** - General system logs via journalctl
- **Service logs** - Nginx, SSH, PHP-FPM (per version), MySQL, MariaDB, PostgreSQL, Redis, Valkey, Memcached
- **Site access logs** - Per-site Nginx access logs
- **Cron script logs** - Output from individual cron scripts
- **Supervisor program logs** - Output from supervisor programs

| Option          | Description                            | Default    |
| --------------- | -------------------------------------- | ---------- |
| `--server`      | Server name                            | (prompted) |
| `--site`        | Filter logs to a specific site         | (none)     |
| `--service, -s` | Service(s) to view (comma-separated)   | (prompted) |
| `--lines, -n`   | Number of lines to retrieve per source | 50         |

<a name="deleting-a-server"></a>

## Deleting a Server

The `server:delete` command removes a server from your inventory:

```bash
deployer server:delete
```

This command includes safety features:

1. **Type-to-confirm** - You must type the server name to confirm
2. **Double confirmation** - An additional Yes/No prompt

Options:

| Option             | Description                      | Default    |
| ------------------ | -------------------------------- | ---------- |
| `--server`         | Server name to delete            | (prompted) |
| `--force`          | Skip type-to-confirm prompt      | false      |
| `--yes`            | Skip Yes/No confirmation         | false      |
| `--inventory-only` | Only remove from local inventory | false      |

> [!WARNING]
> If the server was provisioned through DeployerPHP's AWS or DigitalOcean integration, this command will also destroy the cloud resources unless you use `--inventory-only`. For AWS servers, any associated Elastic IP is also released.

When deleting a server, DeployerPHP also removes any associated sites from your inventory. The sites are removed from the inventory only - remote files remain on the server until the cloud instance is destroyed.

For servers provisioned externally, only the inventory entry is removed. The actual server remains running.
