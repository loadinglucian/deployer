# Cron Jobs

Cron jobs run scheduled tasks for your site. DeployerPHP manages cron scripts in your repository's `.deployer/crons/` directory and syncs them to the server.

## Creating Cron Jobs

The `cron:create` command adds a cron job to a site:

```bash
deployer cron:create
```

Options:

| Option       | Description                                    |
| ------------ | ---------------------------------------------- |
| `--domain`   | Site domain                                    |
| `--script`   | Cron script path within `.deployer/crons/`     |
| `--schedule` | Cron schedule expression (e.g., `*/5 * * * *`) |

You'll be prompted to select a script from `.deployer/crons/` and provide a schedule.

> [!NOTE]
> Run `scaffold:crons` to create example cron scripts in your repository.

## Syncing Cron Jobs

The `cron:sync` command syncs cron definitions from inventory to the server:

```bash
deployer cron:sync
```

To view cron logs, use `server:logs --server=production --service=all-crons` or select individual cron scripts from the log sources.

## Deleting Cron Jobs

```bash
deployer cron:delete
```

Options:

| Option          | Description                            |
| --------------- | -------------------------------------- |
| `--domain`      | Site domain                            |
| `--script`      | Cron script to delete                  |
| `--force`, `-f` | Skip typing the script name to confirm |
| `--yes`, `-y`   | Skip Yes/No confirmation               |
