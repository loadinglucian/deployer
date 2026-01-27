# Firewall Configuration

The `server:firewall` command configures UFW firewall rules:

```bash
deployer server:firewall
```

DeployerPHP detects which services are listening on ports and lets you select which to allow through the firewall. HTTP (80) and HTTPS (443) are pre-selected by default, along with any ports already allowed in UFW.

You'll be prompted to:

- **Select server** - Choose which server to configure
- **Select ports** - Multi-select from detected listening services
- **Confirm changes** - Review and confirm the firewall configuration

Options:

| Option     | Description                                        |
| ---------- | -------------------------------------------------- |
| `--server` | Server name from inventory                         |
| `--allow`  | Comma-separated ports to allow (e.g., 80,443,3306) |
| `--yes`    | Skip confirmation prompt                           |

> [!NOTE]
> SSH access is always preserved regardless of your selections. The `--allow` option only accepts ports that have services actively listening on them.
