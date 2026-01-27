# Supervisor Processes

Supervisor manages long-running processes like queue workers, WebSocket servers, or custom daemons. DeployerPHP manages supervisor scripts in your repository's `.deployer/supervisors/` directory.

## Creating Processes

The `supervisor:create` command adds a supervised process:

```bash
deployer supervisor:create
```

Options:

| Option           | Description                                |
| ---------------- | ------------------------------------------ |
| `--domain`       | Site domain                                |
| `--program`      | Process name identifier                    |
| `--script`       | Script in `.deployer/supervisors/`         |
| `--autostart`    | Start on supervisord start (default: true) |
| `--autorestart`  | Restart on exit (default: true)            |
| `--stopwaitsecs` | Seconds to wait for stop (default: 3600)   |
| `--numprocs`     | Number of process instances (default: 1)   |

> [!NOTE]
> Run `scaffold:supervisors` to create example supervisor scripts in your repository.

## Managing Processes

The supervisor service commands operate at the server level, controlling the supervisord daemon:

```bash
deployer supervisor:start
deployer supervisor:stop
deployer supervisor:restart
```

Options:

| Option     | Description                |
| ---------- | -------------------------- |
| `--server` | Server from your inventory |

These commands start, stop, or restart the supervisord service. Restarting is useful after deployments to pick up new process configurations.

## Syncing Processes

The `supervisor:sync` command syncs process definitions from inventory to the server:

```bash
deployer supervisor:sync
```

To view supervisor logs, use `server:logs --server=production --service=all-supervisors` or select individual programs from the log sources.

## Deleting Processes

```bash
deployer supervisor:delete
```

Options:

| Option          | Description                             |
| --------------- | --------------------------------------- |
| `--domain`      | Site domain                             |
| `--program`     | Supervisor program to delete            |
| `--force`, `-f` | Skip typing the program name to confirm |
| `--yes`, `-y`   | Skip Yes/No confirmation                |
