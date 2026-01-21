# Automation

When integrating DeployerPHP into CI/CD pipelines or shell scripts, you'll want commands to run without interactive prompts. DeployerPHP provides two features to make automation straightforward: command replay for learning the CLI syntax, and quiet mode for suppressing output.

- [Command Replay](#command-replay)
- [Quiet Mode](#quiet-mode)

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

If you want minimal output, use the `--quiet` (or `-q`) global option. This option is available on all commands and suppresses all output except errors.

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
