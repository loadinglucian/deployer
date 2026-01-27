# Rollbacks

DeployerPHP follows a forward-only deployment philosophy:

```bash
deployer site:rollback
```

Rather than reverting to a previous release, this command explains why forward-only deployments are preferred:

- Rollbacks can leave databases in inconsistent states
- Forward-only encourages proper testing before deployment
- Quick fixes and redeployments are often faster than rollbacks

If you need to revert code, revert in Git and redeploy.
