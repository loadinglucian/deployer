# Introduction

DeployerPHP integrates with cloud providers to provision servers and manage DNS records directly from the command line. Instead of manually creating servers through web dashboards or configuring DNS through provider portals, you can provision cloud resources and point domains to your servers with simple commands.

Currently supported providers:

- **AWS** - EC2 instances and Route53 DNS
- **Cloudflare** - DNS management
- **DigitalOcean** - Droplets and DNS

> [!NOTE]
> Cloud commands require API credentials from your cloud provider. These credentials are stored locally and never transmitted to third parties.

> [!NOTE]
> Commands below run interactively. For automation, see [Command Replay](/docs/automation/command-replay).
