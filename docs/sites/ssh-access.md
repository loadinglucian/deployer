# SSH Access

The `site:ssh` command opens an SSH session directly in a site's directory:

```bash
deployer site:ssh
```

You'll be prompted to select a site from your inventory. The session opens in the site's root directory (`/home/deployer/sites/{domain}/`) as the `deployer` user.

| Option     | Description |
| ---------- | ----------- |
| `--domain` | Site domain |
