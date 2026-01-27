# Installing a Server

Second, install and configure your new server by running the `server:install` command:

```shell
deployer server:install
```

DeployerPHP will prompt you for:

- **Server** - Select from your inventory
- **PHP version** - The version of PHP you want to install
- **PHP extensions** - The PHP extensions you want to install
- **Deploy key** - The SSH key used to access your repositories

The installation process will:

1. Update package lists and install base packages
2. Configure Nginx with a monitoring endpoint
3. Set up the firewall (UFW)
4. Install your chosen PHP version with selected extensions
5. Install Composer and Bun
6. Create a `deployer` user for deployments
7. Generate an SSH key pair for repository access

> [!NOTE]
> After installation, the command displays the server's public key. Add this key to your Git provider to enable access to your repositories.

## Install Multiple PHP Versions

You can install multiple PHP versions, each with its own set of extensions, by running the `server:install` command again at any time, even after deploying multiple sites.

> [!NOTE]
> If you have multiple PHP versions installed on the server, you can choose which version each site should use. This is useful when running multiple applications with different PHP requirements on the same server.
