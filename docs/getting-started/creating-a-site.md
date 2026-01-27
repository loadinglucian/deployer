# Creating a Site

Now that your server is installed, you're ready to create your first site. Run the `site:create` command:

```shell
deployer site:create
```

DeployerPHP will prompt you for:

- **Server** - Select the server to host your site
- **Domain name** - Your site's domain (e.g., "example.com")
- **WWW handling** - Whether to redirect www to non-www (or vice versa)
- **PHP version** - The PHP version to use for this site

The creation process will:

1. Create the site directory structure on your server
2. Configure Nginx for your domain
3. Add the site to your inventory

Once completed, DeployerPHP displays the next steps:

- Point your DNS records (both `@` and `www`) to your server's IP address
- Run `site:https` to enable HTTPS once DNS propagates
- Run `site:deploy` to deploy your application

> [!NOTE]
> If your DNS is managed by AWS Route53, Cloudflare, or DigitalOcean, you can use DeployerPHP's DNS commands to point your domain to your server. For example, `cf:dns:set --zone=example.com --type=A --name=@ --value=YOUR_SERVER_IP` creates the A record for your root domain. See [Managing DNS Records](/docs/cloud/aws#managing-dns-records) in the Cloud documentation.

## Delete a Site

To delete a site from a server, run the `site:delete` command:

```shell
deployer site:delete
```

DeployerPHP will prompt you to select a site, type its name to confirm, and give final confirmation before deletion.
