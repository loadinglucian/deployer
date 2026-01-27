# Introduction

DeployerPHP can install and manage various services on your servers. Each service follows a consistent command pattern:

- `<service>:install` - Install the service
- `<service>:start` - Start the service
- `<service>:stop` - Stop the service
- `<service>:restart` - Restart the service

All commands accept a `--server` option to specify the target server, or will prompt you to select one interactively.

> [!NOTE]
> Commands below run interactively. For automation, see [Command Replay](/docs/automation/command-replay).

To view logs for any service, use the unified `server:logs` command. See the [Viewing Logs](/docs/servers/viewing-logs) section for details.
