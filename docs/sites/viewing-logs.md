# Viewing Logs

To view logs for a specific site, use the `server:logs` command with the `--site` option to filter log sources to that site's Nginx access logs, cron logs, and supervisor logs:

```bash
deployer server:logs --site=example.com
```

For full documentation, see [Viewing Logs](/docs/servers/viewing-logs) in the Server Management documentation.
