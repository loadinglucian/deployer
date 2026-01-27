# MySQL

MySQL is a popular open-source relational database.

## Installing MySQL

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

## Managing MySQL

```bash
deployer mysql:start
deployer mysql:stop
deployer mysql:restart
```

To view MySQL logs, use `server:logs` and select the mysqld service.
