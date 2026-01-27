# Cloudflare

DeployerPHP can manage DNS records in your Cloudflare zones.

## Configuration

Set your Cloudflare API token as an environment variable:

```bash
export CLOUDFLARE_API_TOKEN="your-api-token"
```

Or in a `.env` file:

```env
CLOUDFLARE_API_TOKEN=your-api-token
```

Generate an API token at [https://dash.cloudflare.com/profile/api-tokens](https://dash.cloudflare.com/profile/api-tokens). Your token needs the **Zone:DNS:Edit** permission for the zones you want to manage.

## Managing DNS Records

```bash
# List DNS records
deployer cf:dns:list

# Create or update a record
deployer cf:dns:set

# Delete a record
deployer cf:dns:delete
```

### Listing Records

The `cf:dns:list` command displays DNS records for a zone. You can filter by record type to show only A, AAAA, or CNAME records.

| Option   | Description                            |
| -------- | -------------------------------------- |
| `--zone` | Zone (domain name)                     |
| `--type` | Filter by record type (A, AAAA, CNAME) |

### Setting Records

The `cf:dns:set` command creates a new DNS record or updates an existing one (upsert). Cloudflare supports proxying traffic through their CDN and DDoS protection network.

| Option      | Description                            | Default |
| ----------- | -------------------------------------- | ------- |
| `--zone`    | Zone (domain name)                     |         |
| `--type`    | Record type (A, AAAA, CNAME)           |         |
| `--name`    | Record name (use "@" for root)         |         |
| `--value`   | Record value (IP address or hostname)  |         |
| `--ttl`     | TTL in seconds (1 for auto)            | 300     |
| `--proxied` | Enable Cloudflare proxy (orange cloud) |         |

When proxy is enabled, Cloudflare hides your origin IP address and routes traffic through their global network, providing CDN caching and DDoS protection.

### Deleting Records

The `cf:dns:delete` command removes a DNS record from a zone. DeployerPHP shows the record details and requires confirmation before deletion.

| Option           | Description                            |
| ---------------- | -------------------------------------- |
| `--zone`         | Zone (domain name)                     |
| `--type`         | Record type (A, AAAA, CNAME)           |
| `--name`         | Record name (use "@" for root)         |
| `--force` / `-f` | Skip typing the record name to confirm |
| `--yes` / `-y`   | Skip Yes/No confirmation prompt        |

> [!NOTE]
> Cloudflare commands also support the full `cloudflare:` prefix (e.g., `cloudflare:dns:list`).
