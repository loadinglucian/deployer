# PostgreSQL

PostgreSQL is a powerful, open-source object-relational database system.

## Installing PostgreSQL

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

## Managing PostgreSQL

```bash
deployer postgresql:start
deployer postgresql:stop
deployer postgresql:restart
```

To view PostgreSQL logs, use `server:logs` and select the postgres service.
