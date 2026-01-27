# Nginx

Nginx is installed automatically during `server:install`. These commands control the running service.

## Managing Nginx

```bash
deployer nginx:start
deployer nginx:stop
deployer nginx:restart
```

To view Nginx service logs, use `server:logs` and select the nginx service. For site-specific access logs, select the site domain from the log sources.

> [!NOTE]
> Site-specific Nginx configurations are managed automatically by `site:create` and `site:delete`.
