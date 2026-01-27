# Deleting a Site

The `site:delete` command removes a site:

```bash
deployer site:delete
```

Safety features:

1. **Type-to-confirm** - You must type the domain to confirm
2. **Double confirmation** - An additional Yes/No prompt

Options:

| Option             | Description                      |
| ------------------ | -------------------------------- |
| `--domain`         | Site domain to delete            |
| `--force`, `-f`    | Skip type-to-confirm             |
| `--yes`, `-y`      | Skip Yes/No confirmation         |
| `--inventory-only` | Only remove from local inventory |

> [!WARNING]
> This permanently deletes all site files, releases, and shared data from the server.
