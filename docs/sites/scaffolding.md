# Scaffolding

Scaffolding commands generate the `.deployer/` directory structure in your project.

All scaffold commands accept these common options:

| Option          | Description                                  |
| --------------- | -------------------------------------------- |
| `--destination` | Project root directory (defaults to current) |
| `--force`, `-f` | Overwrite existing files                     |

Without `--force`, existing files are skipped. With `--force`, existing files are overwritten.

## Scaffolding Hooks

```bash
deployer scaffold:hooks
```

This creates:

```
.deployer/
└── hooks/
    ├── 1-building.sh
    ├── 2-releasing.sh
    └── 3-finishing.sh
```

Templates are pre-filled for common PHP/Laravel workflows.

## Scaffolding Crons

```bash
deployer scaffold:crons
```

This creates `.deployer/crons/` with example cron scripts:

```
.deployer/
└── crons/
    ├── messenger.sh
    └── scheduler.sh
```

## Scaffolding Supervisors

```bash
deployer scaffold:supervisors
```

You'll be prompted for:

- **Destination directory** - Project root where `.deployer/supervisors/` will be created (defaults to current directory)

This creates `.deployer/supervisors/` with example supervisor scripts:

```
.deployer/
└── supervisors/
    ├── messenger.sh
    └── queue-worker.sh
```

## Scaffolding AI Rules

```bash
deployer scaffold:ai
```

This command scaffolds AI agent skills for DeployerPHP into your project. You'll be prompted for:

- **Destination directory** - Project root (defaults to current directory)
- **Agent** - Which AI agent to target (claude, cursor, or codex)

The command auto-detects existing AI agent directories (`.claude/`, `.cursor/`, `.codex/`). If one exists, it uses that automatically. If multiple exist, you'll be prompted to choose.

| Option          | Description                      |
| --------------- | -------------------------------- |
| `--destination` | Project root directory           |
| `--agent`       | AI agent (claude, cursor, codex) |
| `--force`, `-f` | Overwrite existing files         |
