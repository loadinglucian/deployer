# Automation

When integrating DeployerPHP into CI/CD pipelines or shell scripts, you'll want commands to run without interactive prompts. DeployerPHP provides several features to make automation straightforward: command replay for learning the CLI syntax, quiet mode for suppressing output, and scaffolded GitHub workflows for common deployment patterns.

- [Command Replay](#command-replay)
- [Quiet Mode](#quiet-mode)
- [GitHub Workflows](#github-workflows)
    - [Preview Deploy](#preview-deploy)
    - [Production Deploy](#production-deploy)
    - [Preview Cleanup](#preview-cleanup)
    - [Required Secrets](#required-secrets)

<a name="command-replay"></a>

## Command Replay

Every DeployerPHP command displays a **non-interactive command replay** at the end of execution. This replay shows the exact command with all options you selected during the interactive session, making it easy to copy and use in scripts or CI pipelines.

For example, when you run `deployer server:add` interactively and fill in the prompts, you'll see output like:

```shell
# Non-interactive command replay
deployer server:add \
    --name=production \
    --host=192.168.1.100 \
    --port=22 \
    --username=root \
    --private-key-path=~/.ssh/id_rsa
```

You can copy this command directly into your automation scripts. The replay teaches you the CLI syntax as you use the tool: run interactively once, then automate with the generated command.

<a name="quiet-mode"></a>

## Quiet Mode

For CI/CD environments where you want minimal output, use the `--quiet` (or `-q`) global option. This option is available on all commands and suppresses all output except errors.

```shell
deployer site:deploy \
    --domain=example.com \
    --repo=git@github.com:user/app.git \
    --branch=main \
    --yes \
    --quiet
```

When using quiet mode, you must provide **all required options via CLI**. DeployerPHP can't prompt for missing values when output is suppressed. If a required option is missing, you'll receive a clear error:

```
Option --domain is required when using --quiet mode
```

> [!NOTE]
> The `--yes` flag skips confirmation prompts. In automation, you'll typically combine `--quiet` with `--yes` to run completely non-interactively.

<a name="github-workflows"></a>

## GitHub Workflows

DeployerPHP can scaffold GitHub Actions workflows for common deployment patterns. Run the scaffold command from your project directory:

```shell
deployer scaffold:workflows
```

This creates three workflow files in `.github/workflows/`:

| Workflow                | Trigger           | Purpose                                   |
| ----------------------- | ----------------- | ----------------------------------------- |
| `preview-deploy.yml`    | PR opened/updated | Creates preview sites for pull requests   |
| `production-deploy.yml` | CI passes on main | Deploys to production after tests pass    |
| `preview-cleanup.yml`   | PR closed         | Removes preview sites when PRs are closed |

Each workflow file includes detailed setup instructions in the comments. You'll need to configure environment variables and uncomment your DNS provider section.

<a name="preview-deploy"></a>

### Preview Deploy

The preview deploy workflow automatically creates a preview site whenever a pull request is opened or updated. Each PR gets its own subdomain based on the PR number and title:

```
pr-{number}-{sanitized-title}.previews.example.com
```

The workflow:

1. Generates a DNS-safe subdomain from the PR title
2. Posts an initial status comment on the PR
3. Creates a DNS record pointing to your preview server
4. Creates the site if it doesn't already exist
5. Uploads your `.env` file from GitHub secrets
6. Deploys the application from the PR branch
7. Enables HTTPS via Let's Encrypt
8. Updates the PR comment with the live URL

Configure the workflow by editing these environment variables:

| Variable        | Description                               | Example                |
| --------------- | ----------------------------------------- | ---------------------- |
| `ROOT_DOMAIN`   | Base domain for preview subdomains        | `previews.example.com` |
| `SERVER_NAME`   | Server name from your inventory           | `preview`              |
| `PHP_VERSION`   | PHP version for preview sites             | `8.3`                  |
| `WEB_ROOT`      | Public directory relative to project root | `public`               |
| `KEEP_RELEASES` | Number of old releases to keep            | `3`                    |

You'll also need to uncomment one DNS provider section (AWS Route53, DigitalOcean, or Cloudflare) and configure the corresponding secrets.

<a name="production-deploy"></a>

### Production Deploy

The production deploy workflow uses GitHub's `workflow_run` trigger to deploy after your CI workflows complete successfully. This ensures your tests pass before deploying to production.

The workflow:

1. Waits for specified workflows to complete on the `main` branch
2. Checks that the triggering workflow succeeded
3. Deploys the application to your production server
4. Creates a GitHub deployment record for tracking

Configure which workflows must pass before deployment:

```yaml
on:
    workflow_run:
        workflows:
            - 'CI' # Your test/lint workflow name
            # - "Tests"       # Add more workflows as needed
        types: [completed]
        branches: [main]
```

> [!NOTE]
> Use workflow **names** as shown in GitHub's UI, not file names. If you list multiple workflows, deployment triggers after **each** one completes, so consider using a single umbrella CI workflow instead.

<a name="preview-cleanup"></a>

### Preview Cleanup

The preview cleanup workflow removes preview sites when pull requests are closed (merged or abandoned). This keeps your preview server tidy and frees up resources.

The workflow:

1. Generates the same subdomain used during deployment
2. Deletes the site from the server
3. Removes the DNS record
4. Updates the PR comment to indicate cleanup

The cleanup workflow uses `continue-on-error: true` for deletion steps, so it completes gracefully even if the site or DNS record was already removed manually.

<a name="required-secrets"></a>

### Required Secrets

All workflows require these base secrets:

| Secret               | Description                       | How to Get                   |
| -------------------- | --------------------------------- | ---------------------------- |
| `DEPLOYER_SSH_KEY`   | SSH private key for server access | Contents of `~/.ssh/id_rsa`  |
| `DEPLOYER_INVENTORY` | Base64-encoded inventory          | `cat deployer.yml \| base64` |

The preview deploy workflow additionally requires:

| Secret            | Description                            |
| ----------------- | -------------------------------------- |
| `APP_ENV_CONTENT` | Full `.env` file contents for previews |

For DNS management, add secrets for your chosen provider:

**AWS Route53:**

| Secret                  | Description                             |
| ----------------------- | --------------------------------------- |
| `AWS_ACCESS_KEY_ID`     | AWS access key with Route53 permissions |
| `AWS_SECRET_ACCESS_KEY` | AWS secret key                          |
| `AWS_ROUTE53_ZONE_ID`   | Hosted zone ID for your preview domain  |

**DigitalOcean:**

| Secret               | Description                    |
| -------------------- | ------------------------------ |
| `DIGITALOCEAN_TOKEN` | API token with DNS permissions |

**Cloudflare:**

| Secret                 | Description                             |
| ---------------------- | --------------------------------------- |
| `CLOUDFLARE_API_TOKEN` | API token with Zone:DNS:Edit permission |
| `CLOUDFLARE_ZONE_ID`   | Zone ID for your preview domain         |

> [!WARNING]
> Your `DEPLOYER_SSH_KEY` grants access to your servers. Use repository secrets (not organization secrets) and consider creating a dedicated SSH key pair for CI/CD deployments.

For DNS provider setup details, see [Managing DNS Records](/docs/pro#aws-dns) in the Pro documentation.
