# Deleting a Server

The `server:delete` command removes a server from your inventory:

```bash
deployer server:delete
```

This command includes safety features:

1. **Type-to-confirm** - You must type the server name to confirm
2. **Double confirmation** - An additional Yes/No prompt

Options:

| Option             | Description                      | Default    |
| ------------------ | -------------------------------- | ---------- |
| `--server`         | Server name to delete            | (prompted) |
| `--force`          | Skip type-to-confirm prompt      | false      |
| `--yes`            | Skip Yes/No confirmation         | false      |
| `--inventory-only` | Only remove from local inventory | false      |

> [!WARNING]
> If the server was provisioned through DeployerPHP's AWS or DigitalOcean integration, this command will also destroy the cloud resources unless you use `--inventory-only`. For AWS servers, any associated Elastic IP is also released.

When deleting a server, DeployerPHP also removes any associated sites from your inventory. The sites are removed from the inventory only - remote files remain on the server until the cloud instance is destroyed.

For servers provisioned externally, only the inventory entry is removed. The actual server remains running.
