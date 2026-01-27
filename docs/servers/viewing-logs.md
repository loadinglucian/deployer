# Viewing Logs

The `server:logs` command provides a unified interface for viewing all logs on a server:

```bash
deployer server:logs
```

When run interactively, you'll see a multiselect prompt with all available log sources. You can select multiple sources at once to view logs from different services in a single command.

Available log sources include:

- **System logs** - General system logs via journalctl
- **Service logs** - Nginx, SSH, PHP-FPM (per version), MySQL, MariaDB, PostgreSQL, Redis, Valkey, Memcached
- **Site access logs** - Per-site Nginx access logs
- **Cron script logs** - Output from individual cron scripts
- **Supervisor program logs** - Output from supervisor programs

| Option          | Description                            | Default    |
| --------------- | -------------------------------------- | ---------- |
| `--server`      | Server name                            | (prompted) |
| `--site`        | Filter logs to a specific site         | (none)     |
| `--service, -s` | Service(s) to view (comma-separated)   | (prompted) |
| `--lines, -n`   | Number of lines to retrieve per source | 50         |
