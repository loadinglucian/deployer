# Running Commands

The `server:run` command executes arbitrary shell commands on a server with real-time output streaming:

```bash
deployer server:run
```

You'll be prompted for the server and command. Output is streamed in real-time as the command executes on the remote server.

| Option      | Description        |
| ----------- | ------------------ |
| `--server`  | Server name        |
| `--command` | Command to execute |

This is useful for quick administrative tasks without opening a full SSH session.
