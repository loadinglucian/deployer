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
- `<service>:logs` - View service logs

All commands accept a `--server` option to specify the target server, or will prompt you to select one interactively.

<a name="mysql"></a>

## MySQL

MySQL is a popular open-source relational database.

### Installing MySQL

```bash
deployer mysql:install --server=production
```

During installation, DeployerPHP:

1. Installs the MySQL server package
2. Generates a secure root password
3. Creates a `deployer` database user with its own password
4. Creates a `deployer` database

> [!WARNING]
> Credentials are displayed only once after installation. Choose to display them on screen or save to a file with secure permissions (0600).

### Managing MySQL

```bash
# Start the service
deployer mysql:start --server=production

# Stop the service
deployer mysql:stop --server=production

# Restart the service
deployer mysql:restart --server=production

# View logs
deployer mysql:logs --server=production --lines=100
```

<a name="mariadb"></a>

## MariaDB

MariaDB is a community-developed fork of MySQL with enhanced features.

### Installing MariaDB

```bash
deployer mariadb:install --server=production
```

The installation process is identical to MySQL, creating a root password, `deployer` user, and `deployer` database.

### Managing MariaDB

```bash
deployer mariadb:start --server=production
deployer mariadb:stop --server=production
deployer mariadb:restart --server=production
deployer mariadb:logs --server=production --lines=100
```

> [!NOTE]
> MySQL and MariaDB are mutually exclusive. Install only one on each server.

<a name="postgresql"></a>

## PostgreSQL

PostgreSQL is a powerful, open-source object-relational database system.

### Installing PostgreSQL

```bash
deployer postgresql:install --server=production
```

Like MySQL/MariaDB, this creates credentials for the `deployer` user and a `deployer` database.

### Managing PostgreSQL

```bash
deployer postgresql:start --server=production
deployer postgresql:stop --server=production
deployer postgresql:restart --server=production
deployer postgresql:logs --server=production --lines=100
```

<a name="redis"></a>

## Redis

Redis is an in-memory data structure store, commonly used for caching and queues.

### Installing Redis

```bash
deployer redis:install --server=production
```

Redis is installed with a secure default configuration, binding to localhost only.

### Managing Redis

```bash
deployer redis:start --server=production
deployer redis:stop --server=production
deployer redis:restart --server=production
deployer redis:logs --server=production --lines=100
```

<a name="memcached"></a>

## Memcached

Memcached is a distributed memory caching system.

### Installing Memcached

```bash
deployer memcached:install --server=production
```

### Managing Memcached

```bash
deployer memcached:start --server=production
deployer memcached:stop --server=production
deployer memcached:restart --server=production
deployer memcached:logs --server=production --lines=100
```

<a name="valkey"></a>

## Valkey

Valkey is an open-source fork of Redis, fully compatible with Redis clients and commands.

### Installing Valkey

```bash
deployer valkey:install --server=production
```

### Managing Valkey

```bash
deployer valkey:start --server=production
deployer valkey:stop --server=production
deployer valkey:restart --server=production
deployer valkey:logs --server=production --lines=100
```

> [!NOTE]
> Valkey and Redis are mutually exclusive. Install only one on each server.

<a name="nginx"></a>

## Nginx

Nginx is installed automatically during `server:install`. These commands control the running service.

### Managing Nginx

```bash
# Start Nginx
deployer nginx:start --server=production

# Stop Nginx
deployer nginx:stop --server=production

# Restart Nginx (use after configuration changes)
deployer nginx:restart --server=production

# View access and error logs
deployer nginx:logs --server=production --lines=100
```

> [!NOTE]
> Site-specific Nginx configurations are managed automatically by `site:create` and `site:delete`.

<a name="php-fpm"></a>

## PHP-FPM

PHP-FPM is installed during `server:install` for each PHP version you select. These commands control PHP-FPM for all installed versions.

### Managing PHP-FPM

```bash
# Start PHP-FPM (all versions)
deployer php:start --server=production

# Stop PHP-FPM
deployer php:stop --server=production

# Restart PHP-FPM (use after php.ini changes)
deployer php:restart --server=production

# View PHP-FPM logs
deployer php:logs --server=production --lines=100
```

You can also target a specific PHP version:

```bash
deployer php:restart --server=production --version=8.3
```

### Installing Additional PHP Versions

To install additional PHP versions on an existing server, run `server:install` again:

```bash
deployer server:install \
    --server=production \
    --php-version=8.4 \
    --php-extensions=redis,imagick
```

This adds the new PHP version alongside existing versions without affecting running sites.
