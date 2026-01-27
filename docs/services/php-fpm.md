# PHP-FPM

PHP-FPM is installed during `server:install` for each PHP version you select. These commands control PHP-FPM for all installed versions.

## Managing PHP-FPM

```bash
deployer php:start
deployer php:stop
deployer php:restart
```

| Option      | Description                                   |
| ----------- | --------------------------------------------- |
| `--server`  | Server name                                   |
| `--version` | PHP version to target (omit for all versions) |

To view PHP-FPM logs, use `server:logs` and select the PHP-FPM service for your version.

## Installing Additional PHP Versions

To install additional PHP versions on an existing server, run `server:install` again:

```bash
deployer server:install
```

This adds the new PHP version alongside existing versions without affecting running sites.
