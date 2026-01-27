# Server Information

The `server:info` command displays comprehensive information about a server:

```bash
deployer server:info
```

This shows:

- **Distribution** - OS name (Ubuntu, Debian)
- **User** - Permission level (root, sudo, or insufficient)
- **Hardware** - CPU cores, RAM, disk type
- **Services** - Listening ports with process names
- **Firewall** - UFW status and open ports
- **Nginx** - Version, active connections, and total requests
- **PHP** - Installed versions with extensions (default version marked)
- **PHP-FPM** - Per-version stats including pool, process counts, queue, and warnings
- **Sites** - Configured domains with HTTPS status and PHP version
