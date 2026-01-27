# Redis

Redis is an in-memory data structure store, commonly used for caching and queues.

## Installing Redis

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

## Managing Redis

```bash
deployer redis:start
deployer redis:stop
deployer redis:restart
```

To view Redis logs, use `server:logs` and select the redis-server service.
