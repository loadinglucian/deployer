# AI Automation

If you use AI tools like Claude, Cursor, or Codex, you can create a rules file that guides agents on safely interacting with your DeployerPHP-managed servers. This is useful when debugging issues with your application in production. Agents can read logs or execute remote, non-destructive commands on your server to investigate and resolve problems.

> [!WARNING]
> **Use at your own risk!** Granting AI agents access to production servers can be risky. Always review generated rules and monitor AI-initiated actions. You are solely responsible for any changes, data loss, or issues arising from AI-assisted debugging.

Run the `scaffold:ai` command from your project directory:

```shell
deployer scaffold:ai
```

DeployerPHP will prompt you to select your AI agent:

- **Claude**: Creates rules in `.claude/rules/`
- **Cursor**: Creates rules in `.cursor/rules/`
- **Codex**: Creates rules in `.codex/rules/`

> [!NOTE]
> If an existing AI agent directory is detected in your project, DeployerPHP will automatically use it. If multiple are found, you'll be prompted to choose one.

The generated rules file provides your AI assistant with:

- **Inventory context**: Understanding of your `deployer.yml` structure
- **Deployment layout**: Knowledge of the release directory structure
- **Safe debugging commands**: Commands for viewing logs, checking status, and reading files
- **Guardrails**: Explicit restrictions preventing destructive operations like deployments, service restarts, or configuration changes

This ensures your AI assistant can help troubleshoot issues on your servers without accidentally running commands that could affect production stability.
