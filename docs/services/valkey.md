# Valkey

Valkey is an open-source fork of Redis, fully compatible with Redis clients and commands.

## Installing Valkey

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

## Managing Valkey

```bash
deployer valkey:start
deployer valkey:stop
deployer valkey:restart
```

To view Valkey logs, use `server:logs` and select the valkey-server service.

> [!NOTE]
> Valkey and Redis are mutually exclusive. Install only one on each server.
