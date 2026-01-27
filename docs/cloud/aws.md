# AWS

DeployerPHP can provision EC2 instances, manage SSH keys, and configure Route53 DNS records in your AWS account.

## Configuration

Set your AWS credentials as environment variables:

```bash
export AWS_ACCESS_KEY_ID="your-access-key"
export AWS_SECRET_ACCESS_KEY="your-secret-key"
export AWS_DEFAULT_REGION="us-east-1"
```

Or create a `.env` file in your project:

```env
AWS_ACCESS_KEY_ID=your-access-key
AWS_SECRET_ACCESS_KEY=your-secret-key
AWS_DEFAULT_REGION=us-east-1
```

Required IAM permissions for EC2:

- `ec2:RunInstances`
- `ec2:DescribeInstances`
- `ec2:TerminateInstances`
- `ec2:DescribeInstanceTypes`
- `ec2:CreateKeyPair`
- `ec2:DeleteKeyPair`
- `ec2:DescribeKeyPairs`
- `ec2:DescribeImages`
- `ec2:DescribeVpcs`
- `ec2:DescribeSubnets`
- `ec2:CreateSecurityGroup`
- `ec2:AuthorizeSecurityGroupIngress`
- `ec2:DescribeSecurityGroups`
- `ec2:AllocateAddress`
- `ec2:ReleaseAddress`
- `ec2:AssociateAddress`
- `ec2:DisassociateAddress`
- `sts:GetCallerIdentity`

Required IAM permissions for Route53 DNS:

- `route53:ListHostedZones`
- `route53:GetHostedZone`
- `route53:ListResourceRecordSets`
- `route53:ChangeResourceRecordSets`

## Managing SSH Keys

Before provisioning, upload your SSH public key to AWS:

```bash
# List existing keys
deployer aws:key:list

# Add a new key
deployer aws:key:add

# Delete a key
deployer aws:key:delete
```

### Listing Keys

The `aws:key:list` command displays all EC2 key pairs in your configured AWS region. It shows each key's name along with a truncated fingerprint for identification.

This command has no options beyond the standard `--env` and `--inventory` flags.

### Adding Keys

When adding a key, you'll be prompted for:

- **Public key path** - Path to your `.pub` file (leave empty to auto-detect `~/.ssh/id_ed25519.pub` or `~/.ssh/id_rsa.pub`)
- **Key pair name** - Identifier in AWS (default: `deployer-key`)

| Option              | Description          | Default        |
| ------------------- | -------------------- | -------------- |
| `--public-key-path` | SSH public key path  | Auto-detected  |
| `--name`            | Key pair name in AWS | `deployer-key` |

### Deleting Keys

When deleting a key, you'll be prompted for:

- **Key** - The AWS key pair name to delete
- **Type-to-confirm** - Type the key name to confirm deletion
- **Yes/No confirmation** - Final confirmation before deletion

| Option           | Description                         |
| ---------------- | ----------------------------------- |
| `--key`          | AWS key pair name                   |
| `--force` / `-f` | Skip typing the key name to confirm |
| `--yes` / `-y`   | Skip Yes/No confirmation prompt     |

## Provisioning Servers

The `aws:provision` command creates a new EC2 instance:

```bash
deployer aws:provision
```

You'll be prompted for server details, instance configuration, and network settings. The command supports two approaches for instance type selection:

### Options

| Option               | Description                                             |
| -------------------- | ------------------------------------------------------- |
| `--name`             | Server name for your inventory                          |
| `--instance-type`    | Full instance type (e.g., t3.large) - skips family/size |
| `--instance-family`  | Instance family (e.g., t3, m6i, c7g)                    |
| `--instance-size`    | Instance size (e.g., micro, large, xlarge)              |
| `--ami`              | AMI ID for the OS image                                 |
| `--key-pair`         | AWS key pair name for SSH access                        |
| `--private-key-path` | Path to your SSH private key                            |
| `--vpc`              | VPC ID for network isolation                            |
| `--subnet`           | Subnet ID (determines availability zone)                |
| `--disk-size`        | Root disk size in GB (default: 8)                       |
| `--monitoring`       | Enable detailed CloudWatch monitoring (extra cost)      |
| `--no-monitoring`    | Disable detailed monitoring                             |

DeployerPHP will:

1. Verify your instance type is available in the selected region
2. Create or reuse a "deployer" security group with SSH (22), HTTP (80), and HTTPS (443) rules
3. Launch an EC2 instance with the selected OS and configuration
4. Wait for the instance to reach the running state
5. Allocate a new Elastic IP address and associate it with the instance
6. Verify SSH connectivity to the new server
7. Add the server to your local inventory

If any step fails after the instance is created, DeployerPHP automatically rolls back by releasing the Elastic IP and terminating the instance.

After provisioning, run `deployer server:install` to set up the server.

> [!NOTE]
> When you delete a server provisioned through AWS, DeployerPHP also terminates the EC2 instance and releases the Elastic IP.

## Managing DNS Records

DeployerPHP can manage DNS records in your Route53 hosted zones:

```bash
# List DNS records
deployer aws:dns:list

# Create or update a record
deployer aws:dns:set

# Delete a record
deployer aws:dns:delete
```

### Listing Records

The `aws:dns:list` command displays DNS records for a hosted zone. You can filter by record type to show only A, AAAA, or CNAME records.

| Option   | Description                            |
| -------- | -------------------------------------- |
| `--zone` | Hosted zone ID or domain name          |
| `--type` | Filter by record type (A, AAAA, CNAME) |

### Setting Records

The `aws:dns:set` command creates a new DNS record or updates an existing one (upsert). When prompted for a record name, use `@` for the root domain.

| Option    | Description                           | Default |
| --------- | ------------------------------------- | ------- |
| `--zone`  | Hosted zone ID or domain name         |         |
| `--type`  | Record type (A, AAAA, CNAME)          |         |
| `--name`  | Record name (use "@" for root)        |         |
| `--value` | Record value (IP address or hostname) |         |
| `--ttl`   | TTL in seconds                        | 300     |

### Deleting Records

The `aws:dns:delete` command removes a DNS record from a hosted zone. DeployerPHP shows the record details and requires confirmation before deletion.

| Option           | Description                            |
| ---------------- | -------------------------------------- |
| `--zone`         | Hosted zone ID or domain name          |
| `--type`         | Record type (A, AAAA, CNAME)           |
| `--name`         | Record name (use "@" for root)         |
| `--force` / `-f` | Skip typing the record name to confirm |
| `--yes` / `-y`   | Skip Yes/No confirmation prompt        |
