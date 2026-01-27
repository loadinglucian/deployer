# Adding a Server

First, add your new server to the inventory by running the `server:add` command:

```shell
deployer server:add
```

> [!NOTE]
> DeployerPHP will initialize an empty inventory in your current working directory. The inventory is a simple `deployer.yml` file that DeployerPHP uses to keep track of your servers and sites. Typically, you should run DeployerPHP from your project directory. If a .env file exists in your current working directory, DeployerPHP will also use that.

DeployerPHP will prompt you for:

- **Server name** - A friendly name for your server (e.g., "production", "web1")
- **Host** - The IP address or hostname of your server
- **Port** - SSH port (default: 22)
- **Username** - SSH username (default: root)
- **Private key path** - Path to the SSH private key used to connect to the server

Once completed, DeployerPHP confirms the connection and adds the server to your inventory. You can then run `server:info` to view server details or `server:install` to set up the server.

> [!NOTE]
> DeployerPHP supports automation and CI/CD integration. After each command, a non-interactive replay is displayed with all selected options. See [Automation](/docs/automation/command-replay) for details on command replay and quiet mode.

> [!NOTE]
> You can use the `aws:provision` or `do:provision` commands to automatically provision and add a new EC2 instance or droplet to your inventory. It's super convenient if you want to spin up servers on the fly in your automation pipelines.

## Delete A Server From Inventory

To delete a server from the inventory, run the `server:delete` command:

```shell
deployer server:delete
```

DeployerPHP will prompt you to select a server, type its name to confirm, and give final confirmation before deletion.

> [!WARNING]
> You are responsible for making sure the server is no longer running or incurring costs with your hosting provider.

> [!NOTE]
> If you used the `aws:provision` or `do:provision` commands to provision the server, the `server:delete` command will automatically destroy the cloud instance for you.
