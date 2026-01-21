# Services

- [Introduction](#introduction)
- [MySQL](#mysql)
- [MariaDB](#mariadb)
- [PostgreSQL](#postgresql)
- [Redis](#redis)
- [Memcached](#memcached)
- [Valkey](#valkey)
- [Nginx](#nginx)
- [PHP-FPM](#php-fpm)

<a name="introduction"></a>

## Introduction

DeployerPHP can install and manage various services on your servers. Each service follows a consistent command pattern:

- `<service>:install` - Install the service
- `<service>:start` - Start the service
- `<service>:stop` - Stop the service
- `<service>:restart` - Restart the service

All commands accept a `--server` option to specify the target server, or will prompt you to select one interactively.

> [!NOTE]
> Commands below run interactively. For automation, see [Command Replay](/docs/automation#command-replay).

To view logs for any service, use the unified `server:logs` command. See the [Viewing Logs](/docs/servers#viewing-logs) section for details.

<a name="mysql"></a>

## MySQL

MySQL is a popular open-source relational database.

### Installing MySQL

```bash
deployer mysql:install
```

During installation, DeployerPHP will prompt you for:

- **Server** - The target server (or use `--server` option)
- **Credential output** - How to receive the generated credentials

The installation process:

1. Installs the MySQL server package
2. Generates a secure root password
3. Creates a `deployer` database user with its own password
4. Creates a `deployer` database

| Option                  | Description                                 |
| ----------------------- | ------------------------------------------- |
| `--server`              | Server name                                 |
| `--display-credentials` | Display credentials on screen               |
| `--save-credentials`    | Save credentials to file (0600 permissions) |

> [!WARNING]
> Credentials are generated only once during installation. If MySQL is already installed, credentials will not be displayed again.

If saving to a file fails, DeployerPHP will automatically fall back to displaying the credentials on screen so you don't lose them.

### Managing MySQL

```bash
deployer mysql:start
deployer mysql:stop
deployer mysql:restart
```

To view MySQL logs, use `server:logs` and select the mysqld service.

<a name="mariadb"></a>

## MariaDB

MariaDB is a community-developed fork of MySQL with enhanced features.

### Installing MariaDB

```bash
deployer mariadb:install
```

During installation, DeployerPHP will prompt you for:

- **Server** - The target server (or use `--server` option)
- **Credential output** - How to receive the generated credentials

The installation process:

1. Installs the MariaDB server package
2. Generates a secure root password
3. Creates a `deployer` database user with its own password
4. Creates a `deployer` database

| Option                  | Description                                 |
| ----------------------- | ------------------------------------------- |
| `--server`              | Server name                                 |
| `--display-credentials` | Display credentials on screen               |
| `--save-credentials`    | Save credentials to file (0600 permissions) |

> [!WARNING]
> Credentials are displayed only once after installation. Choose to display them on screen or save to a file with secure permissions (0600).

If saving to a file fails, DeployerPHP will automatically fall back to displaying the credentials on screen so you don't lose them.

### Managing MariaDB

```bash
deployer mariadb:start
deployer mariadb:stop
deployer mariadb:restart
```

To view MariaDB logs, use `server:logs` and select the mariadb service.

> [!NOTE]
> MySQL and MariaDB are mutually exclusive. Install only one on each server.

<a name="postgresql"></a>

## PostgreSQL

PostgreSQL is a powerful, open-source object-relational database system.

### Installing PostgreSQL

```bash
deployer postgresql:install
```

Like MySQL/MariaDB, this creates credentials for the `deployer` user and a `deployer` database.

| Option                  | Description                                 |
| ----------------------- | ------------------------------------------- |
| `--server`              | Server name                                 |
| `--display-credentials` | Display credentials on screen               |
| `--save-credentials`    | Save credentials to file (0600 permissions) |

> [!WARNING]
> Credentials are displayed only once after installation. Choose to display them on screen or save to a file with secure permissions (0600).

### Managing PostgreSQL

```bash
deployer postgresql:start
deployer postgresql:stop
deployer postgresql:restart
```

To view PostgreSQL logs, use `server:logs` and select the postgres service.

<a name="redis"></a>

## Redis

Redis is an in-memory data structure store, commonly used for caching and queues.

### Installing Redis

```bash
deployer redis:install
```

During installation, DeployerPHP:

1. Installs the Redis server package
2. Generates a secure password for authentication
3. Configures Redis to bind to localhost only

| Option                  | Description                                 |
| ----------------------- | ------------------------------------------- |
| `--server`              | Server name                                 |
| `--display-credentials` | Display credentials on screen               |
| `--save-credentials`    | Save credentials to file (0600 permissions) |

> [!WARNING]
> Credentials are displayed only once after installation. Choose to display them on screen or save to a file with secure permissions (0600).

### Managing Redis

```bash
deployer redis:start
deployer redis:stop
deployer redis:restart
```

To view Redis logs, use `server:logs` and select the redis-server service.

<a name="memcached"></a>

## Memcached

Memcached is a distributed memory caching system.

### Installing Memcached

```bash
deployer memcached:install
```

### Managing Memcached

```bash
deployer memcached:start
deployer memcached:stop
deployer memcached:restart
```

To view Memcached logs, use `server:logs` and select the memcached service.

<a name="valkey"></a>

## Valkey

Valkey is an open-source fork of Redis, fully compatible with Redis clients and commands.

### Installing Valkey

```bash
deployer valkey:install
```

During installation, DeployerPHP:

1. Installs the Valkey server package
2. Generates a secure password for authentication
3. Configures Valkey to bind to localhost only

| Option                  | Description                                 |
| ----------------------- | ------------------------------------------- |
| `--server`              | Server name                                 |
| `--display-credentials` | Display credentials on screen               |
| `--save-credentials`    | Save credentials to file (0600 permissions) |

> [!WARNING]
> Credentials are displayed only once after installation. Choose to display them on screen or save to a file with secure permissions (0600).

### Managing Valkey

```bash
deployer valkey:start
deployer valkey:stop
deployer valkey:restart
```

To view Valkey logs, use `server:logs` and select the valkey-server service.

> [!NOTE]
> Valkey and Redis are mutually exclusive. Install only one on each server.

<a name="nginx"></a>

## Nginx

Nginx is installed automatically during `server:install`. These commands control the running service.

### Managing Nginx

```bash
deployer nginx:start
deployer nginx:stop
deployer nginx:restart
```

To view Nginx service logs, use `server:logs` and select the nginx service. For site-specific access logs, select the site domain from the log sources.

> [!NOTE]
> Site-specific Nginx configurations are managed automatically by `site:create` and `site:delete`.

<a name="php-fpm"></a>

## PHP-FPM

PHP-FPM is installed during `server:install` for each PHP version you select. These commands control PHP-FPM for all installed versions.

### Managing PHP-FPM

```bash
deployer php:start
deployer php:stop
deployer php:restart
```

| Option      | Description                                   |
| ----------- | --------------------------------------------- |
| `--server`  | Server name                                   |
| `--version` | PHP version to target (omit for all versions) |

To view PHP-FPM logs, use `server:logs` and select the PHP-FPM service for your version.

### Installing Additional PHP Versions

To install additional PHP versions on an existing server, run `server:install` again:

```bash
deployer server:install
```

This adds the new PHP version alongside existing versions without affecting running sites.
