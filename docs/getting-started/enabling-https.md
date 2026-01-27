# Enabling HTTPS

Once your DNS records are pointing to your server and have propagated, you can enable HTTPS using Let's Encrypt certificates:

```shell
deployer site:https
```

DeployerPHP will prompt you to select a site, then automatically:

1. Install Certbot if not already present
2. Obtain an SSL certificate from Let's Encrypt
3. Configure Nginx for HTTPS with proper redirects
4. Set up automatic certificate renewal

> [!WARNING]
> Make sure your DNS records are properly configured and have propagated before running this command. Let's Encrypt validates domain ownership by making HTTP requests to your server, which will fail if DNS isn't pointing to your server yet.

After HTTPS is enabled, your site will automatically redirect HTTP traffic to HTTPS.
