# Shared Files

Shared files persist across deployments. Common examples include `.env` files, user uploads, and configuration files.

## Pushing Files

The `site:shared:push` command uploads files to the shared directory:

```bash
deployer site:shared:push
```

Options:

| Option     | Description                           |
| ---------- | ------------------------------------- |
| `--domain` | Site domain                           |
| `--local`  | Local file path to upload             |
| `--remote` | Remote filename (relative to shared/) |

## Pulling Files

The `site:shared:pull` command downloads files from the shared directory:

```bash
deployer site:shared:pull
```

Options:

| Option     | Description                           |
| ---------- | ------------------------------------- |
| `--domain` | Site domain                           |
| `--remote` | Remote filename (relative to shared/) |
| `--local`  | Local destination file path           |
| `--yes`    | Skip overwrite confirmation           |
