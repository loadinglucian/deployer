# MariaDB

MariaDB is a community-developed fork of MySQL with enhanced features.

## Installing MariaDB

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

## Managing MariaDB

```bash
deployer mariadb:start
deployer mariadb:stop
deployer mariadb:restart
```

To view MariaDB logs, use `server:logs` and select the mariadb service.

> [!NOTE]
> MySQL and MariaDB are mutually exclusive. Install only one on each server.
