# Pro

- [Introduction](#introduction)
- [AWS EC2](#aws-ec2)
    - [Configuration](#aws-configuration)
    - [Managing SSH Keys](#aws-ssh-keys)
    - [Provisioning Servers](#aws-provisioning)
- [DigitalOcean](#digitalocean)
    - [Configuration](#do-configuration)
    - [Managing SSH Keys](#do-ssh-keys)
    - [Provisioning Droplets](#do-provisioning)

<a name="introduction"></a>
## Introduction

DeployerPHP's Pro features integrate with cloud providers to provision servers directly from the command line. Instead of manually creating servers through web dashboards, you can provision, configure, and destroy cloud resources with simple commands.

Currently supported providers:

- **AWS EC2** - Amazon's Elastic Compute Cloud
- **DigitalOcean** - Simple cloud hosting with Droplets

> [!NOTE]
> Pro features require API credentials from your cloud provider. These credentials are stored locally and never transmitted to third parties.

<a name="aws-ec2"></a>
## AWS EC2

DeployerPHP can provision EC2 instances and manage SSH keys in your AWS account.

<a name="aws-configuration"></a>
### Configuration

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

Required IAM permissions:

- `ec2:RunInstances`
- `ec2:DescribeInstances`
- `ec2:TerminateInstances`
- `ec2:CreateKeyPair`
- `ec2:DeleteKeyPair`
- `ec2:DescribeKeyPairs`
- `ec2:AllocateAddress`
- `ec2:ReleaseAddress`
- `ec2:AssociateAddress`

<a name="aws-ssh-keys"></a>
### Managing SSH Keys

Before provisioning, upload your SSH public key to AWS:

```bash
# List existing keys
deployer aws:key:list

# Add a new key
deployer aws:key:add

# Delete a key
deployer aws:key:delete
```

When adding a key, you'll be prompted for:

- **Key name** - Identifier in AWS
- **Public key path** - Path to your `.pub` file

Example:

```bash
deployer aws:key:add \
    --name=deployer-key \
    --public-key-path=~/.ssh/id_rsa.pub
```

<a name="aws-provisioning"></a>
### Provisioning Servers

The `aws:provision` command creates a new EC2 instance:

```bash
deployer aws:provision
```

You'll be prompted for:

| Option | Description |
| ------ | ----------- |
| `--name` | Server name for your inventory |
| `--region` | AWS region (e.g., us-east-1) |
| `--instance-type` | Instance size (e.g., t3.micro) |
| `--key-name` | SSH key name in AWS |

Example:

```bash
deployer aws:provision \
    --name=production \
    --region=us-east-1 \
    --instance-type=t3.small \
    --key-name=deployer-key
```

DeployerPHP will:

1. Launch an EC2 instance with Ubuntu
2. Allocate an Elastic IP address
3. Wait for the instance to be ready
4. Add the server to your local inventory

After provisioning, install the server:

```bash
deployer server:install --server=production
```

> [!NOTE]
> When you delete a server provisioned through AWS, DeployerPHP also terminates the EC2 instance and releases the Elastic IP.

<a name="digitalocean"></a>
## DigitalOcean

DeployerPHP can provision Droplets and manage SSH keys in your DigitalOcean account.

<a name="do-configuration"></a>
### Configuration

Set your DigitalOcean API token as an environment variable:

```bash
export DIGITALOCEAN_TOKEN="your-api-token"
```

Or in a `.env` file:

```env
DIGITALOCEAN_TOKEN=your-api-token
```

Generate an API token at [https://cloud.digitalocean.com/account/api/tokens](https://cloud.digitalocean.com/account/api/tokens) with read and write access.

<a name="do-ssh-keys"></a>
### Managing SSH Keys

```bash
# List existing keys
deployer do:key:list

# Add a new key
deployer do:key:add

# Delete a key
deployer do:key:delete
```

Example:

```bash
deployer do:key:add \
    --name=deployer-key \
    --public-key-path=~/.ssh/id_rsa.pub
```

<a name="do-provisioning"></a>
### Provisioning Droplets

The `do:provision` command creates a new Droplet:

```bash
deployer do:provision
```

You'll be prompted for:

| Option | Description |
| ------ | ----------- |
| `--name` | Server name for your inventory |
| `--region` | DigitalOcean region (e.g., nyc1) |
| `--size` | Droplet size (e.g., s-1vcpu-1gb) |
| `--ssh-key` | SSH key name in DigitalOcean |

Example:

```bash
deployer do:provision \
    --name=production \
    --region=nyc1 \
    --size=s-1vcpu-2gb \
    --ssh-key=deployer-key
```

DeployerPHP will:

1. Create a Droplet with Ubuntu
2. Wait for the Droplet to be ready
3. Add the server to your local inventory

After provisioning:

```bash
deployer server:install --server=production
```

> [!NOTE]
> When you delete a server provisioned through DigitalOcean, DeployerPHP also destroys the Droplet.

### Available Regions

Common DigitalOcean regions:

| Slug | Location |
| ---- | -------- |
| `nyc1`, `nyc3` | New York |
| `sfo3` | San Francisco |
| `ams3` | Amsterdam |
| `sgp1` | Singapore |
| `lon1` | London |
| `fra1` | Frankfurt |
| `tor1` | Toronto |
| `blr1` | Bangalore |

### Available Sizes

Common Droplet sizes:

| Slug | Specs | Monthly |
| ---- | ----- | ------- |
| `s-1vcpu-512mb-10gb` | 1 vCPU, 512MB, 10GB | $4 |
| `s-1vcpu-1gb` | 1 vCPU, 1GB, 25GB | $6 |
| `s-1vcpu-2gb` | 1 vCPU, 2GB, 50GB | $12 |
| `s-2vcpu-4gb` | 2 vCPU, 4GB, 80GB | $24 |
| `s-4vcpu-8gb` | 4 vCPU, 8GB, 160GB | $48 |

Use `deployer do:provision` interactively to see all available options.
