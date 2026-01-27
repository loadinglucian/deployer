# SSH Access

The `server:ssh` command opens an interactive SSH session to a server:

```bash
deployer server:ssh
```

You'll be prompted to select a server from your inventory, then dropped into a terminal session on the remote server. Use `exit` to return to your local machine.

| Option     | Description |
| ---------- | ----------- |
| `--server` | Server name |
