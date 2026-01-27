# DigitalOcean

DeployerPHP can provision Droplets, manage SSH keys, and configure DNS records in your DigitalOcean account.

## Configuration

Set your DigitalOcean API token as an environment variable:

```bash
export DIGITALOCEAN_TOKEN="your-api-token"
```

Or in a `.env` file:

```env
DIGITALOCEAN_TOKEN=your-api-token
```

Generate an API token at [https://cloud.digitalocean.com/account/api/tokens](https://cloud.digitalocean.com/account/api/tokens) with read and write access.

## Managing SSH Keys

```bash
# List existing keys
deployer do:key:list

# Add a new key
deployer do:key:add

# Delete a key
deployer do:key:delete
```

### Adding Keys

When adding a key, you'll be prompted for:

- **Public key path** - Path to your `.pub` file (leave empty to auto-detect `~/.ssh/id_ed25519.pub` or `~/.ssh/id_rsa.pub`)
- **Key name** - Identifier in DigitalOcean (default: `deployer-key`)

| Option              | Description                      | Default        |
| ------------------- | -------------------------------- | -------------- |
| `--public-key-path` | SSH public key path              | Auto-detected  |
| `--name`            | Key name in DigitalOcean account | `deployer-key` |

### Deleting Keys

When deleting a key, you'll be prompted for:

- **Key** - The DigitalOcean SSH key ID to delete
- **Type-to-confirm** - Type the key ID to confirm deletion
- **Yes/No confirmation** - Final confirmation before deletion

| Option           | Description                       |
| ---------------- | --------------------------------- |
| `--key`          | DigitalOcean public SSH key ID    |
| `--force` / `-f` | Skip typing the key ID to confirm |
| `--yes` / `-y`   | Skip Yes/No confirmation prompt   |

## Provisioning Droplets

The `do:provision` command creates a new Droplet:

```bash
deployer do:provision
```

You'll be prompted for server details, droplet configuration, and optional features.

### Options

| Option               | Description                            |
| -------------------- | -------------------------------------- |
| `--name`             | Server name for your inventory         |
| `--region`           | DigitalOcean region (e.g., nyc3, sfo3) |
| `--size`             | Droplet size (e.g., s-1vcpu-1gb)       |
| `--image`            | OS image (e.g., ubuntu-24-04-x64)      |
| `--ssh-key-id`       | SSH key ID in DigitalOcean             |
| `--private-key-path` | Path to your SSH private key           |
| `--vpc-uuid`         | VPC UUID for network isolation         |
| `--backups`          | Enable automatic backups (extra cost)  |
| `--no-backups`       | Disable automatic backups              |
| `--monitoring`       | Enable monitoring metrics (free)       |
| `--no-monitoring`    | Disable monitoring                     |
| `--ipv6`             | Enable IPv6 address (free)             |
| `--no-ipv6`          | Disable IPv6                           |

DeployerPHP will:

1. Create a Droplet with the selected OS
2. Wait for the Droplet to become active
3. Add the server to your local inventory

After provisioning, run `deployer server:install` to set up the server.

> [!NOTE]
> When you delete a server provisioned through DigitalOcean, DeployerPHP also destroys the Droplet.

### Available Regions

Common DigitalOcean regions:

| Slug           | Location      |
| -------------- | ------------- |
| `nyc1`, `nyc3` | New York      |
| `sfo3`         | San Francisco |
| `ams3`         | Amsterdam     |
| `sgp1`         | Singapore     |
| `lon1`         | London        |
| `fra1`         | Frankfurt     |
| `tor1`         | Toronto       |
| `blr1`         | Bangalore     |

### Available Sizes

Common Droplet sizes:

| Slug                 | Specs               | Monthly |
| -------------------- | ------------------- | ------- |
| `s-1vcpu-512mb-10gb` | 1 vCPU, 512MB, 10GB | $4      |
| `s-1vcpu-1gb`        | 1 vCPU, 1GB, 25GB   | $6      |
| `s-1vcpu-2gb`        | 1 vCPU, 2GB, 50GB   | $12     |
| `s-2vcpu-4gb`        | 2 vCPU, 4GB, 80GB   | $24     |
| `s-4vcpu-8gb`        | 4 vCPU, 8GB, 160GB  | $48     |

Use `deployer do:provision` interactively to see all available options.

## Managing DNS Records

DeployerPHP can manage DNS records for domains in your DigitalOcean account:

```bash
# List DNS records
deployer do:dns:list

# Create or update a record
deployer do:dns:set

# Delete a record
deployer do:dns:delete
```

> [!NOTE]
> Your domain must be added to DigitalOcean's DNS management before you can create records. You can add domains through the DigitalOcean dashboard or API.

### Listing Records

The `do:dns:list` command displays DNS records for a domain. You can filter by record type to show only A, AAAA, or CNAME records.

| Option   | Description                            |
| -------- | -------------------------------------- |
| `--zone` | Zone (domain name)                     |
| `--type` | Filter by record type (A, AAAA, CNAME) |

### Setting Records

The `do:dns:set` command creates a new DNS record or updates an existing one (upsert). When prompted for a record name, use `@` for the root domain.

| Option    | Description                           | Default |
| --------- | ------------------------------------- | ------- |
| `--zone`  | Zone (domain name)                    |         |
| `--type`  | Record type (A, AAAA, CNAME)          |         |
| `--name`  | Record name (use "@" for root)        |         |
| `--value` | Record value (IP address or hostname) |         |
| `--ttl`   | TTL in seconds                        | 300     |

### Deleting Records

The `do:dns:delete` command removes a DNS record from a domain. DeployerPHP shows the record details and requires confirmation before deletion.

| Option           | Description                            |
| ---------------- | -------------------------------------- |
| `--zone`         | Zone (domain name)                     |
| `--type`         | Record type (A, AAAA, CNAME)           |
| `--name`         | Record name (use "@" for root)         |
| `--force` / `-f` | Skip typing the record name to confirm |
| `--yes` / `-y`   | Skip Yes/No confirmation prompt        |

> [!NOTE]
> DigitalOcean commands also support the full `digitalocean:` prefix (e.g., `digitalocean:dns:list`).
