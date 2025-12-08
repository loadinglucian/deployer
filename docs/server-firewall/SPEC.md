# Technical Specification - Server Firewall Command

## Overview

The `server:firewall` command provides interactive UFW management through a single playbook with two modes (detect/apply). The PHP command orchestrates user interaction while the playbook handles all server-side operations including port detection, UFW status checking, and rule application.

**Components:**

| Component                 | Type     | Purpose                                     |
| ------------------------- | -------- | ------------------------------------------- |
| ServerFirewallCommand.php | Command  | User interaction, validation, orchestration |
| server-firewall.sh        | Playbook | Port detection, UFW management, rule apply  |
| helpers.sh                | Playbook | Shared `get_listening_services()` function  |

**Architecture:**

```
┌─────────────────────────────────────────────────────────────────────┐
│                     ServerFirewallCommand.php                       │
├─────────────────────────────────────────────────────────────────────┤
│  1. selectServer()           ──► ServersTrait (existing)            │
│  2. executePlaybook(detect)  ──► get ports, UFW status              │
│  3. promptMultiselect()      ──► user selects ports                 │
│  4. displayConfirmation()    ──► show changes summary               │
│  5. executePlaybook(apply)   ──► apply UFW rules                    │
└─────────────────────────────────────────────────────────────────────┘
                                    │
                                    ▼
┌─────────────────────────────────────────────────────────────────────┐
│                       server-firewall.sh                            │
├─────────────────────────────────────────────────────────────────────┤
│  DEPLOYER_MODE=detect:                                              │
│    - get_listening_services() (from helpers.sh)                     │
│    - get_ufw_status()                                               │
│    - Output: ports, ufw_open_ports, ufw_installed, ufw_enabled      │
│                                                                     │
│  DEPLOYER_MODE=apply:                                               │
│    - validate DEPLOYER_SSH_PORT in DEPLOYER_ALLOWED_PORTS           │
│    - install_ufw() if needed                                        │
│    - apply_ufw_rules() (SSH-safe reset sequence)                    │
│    - Output: status, rules_applied, ports_opened, ports_closed      │
└─────────────────────────────────────────────────────────────────────┘
```

**Design Decisions:**

- **Single playbook with mode parameter**: Reduces SSH connections while keeping logic cohesive. Mode controlled via `DEPLOYER_MODE` env var.
- **Shared helper function**: `get_listening_services()` extracted to `helpers.sh` for DRY with `server-info.sh`. Automatically inlined by PHP runtime.
- **SSH port from ServerDTO**: Uses `$server->port` rather than runtime detection since we already connected via that port.
- **Defense in depth**: SSH port validated in both PHP command and bash playbook.

---

## Feature Specifications

### F12: Shared Port Detection Helper

| Attribute      | Value                                          |
| -------------- | ---------------------------------------------- |
| Source         | FEATURES.md §F12                               |
| Components     | helpers.sh                                     |
| New Files      | None (modify existing)                         |
| Modified Files | playbooks/helpers.sh, playbooks/server-info.sh |

**Interface Contract:**

| Function               | Input | Output                      | Errors                             |
| ---------------------- | ----- | --------------------------- | ---------------------------------- |
| get_listening_services | None  | Lines of `{port}:{process}` | None (empty output if no services) |

**Data Structures:**

| Name   | Type   | Format                        | Purpose                            |
| ------ | ------ | ----------------------------- | ---------------------------------- |
| Output | stdout | `{port}:{process}\n` per line | Port/process pairs, sorted by port |

**Technical Notes:**

- Function moved from `server-info.sh` to `helpers.sh`
- Uses `ss -tulnp` primarily, `netstat -tlnp` as fallback
- Detects TCP ports only (UDP not needed for firewall use case - services listen on TCP)
- Output sorted numerically by port, deduplicated
- `server-info.sh` continues to source helpers via placeholder comment

**Verification:**

- `server-info.sh` produces identical output before/after refactor
- `get_listening_services` returns expected format when called standalone

---

### F1: Port Detection

| Attribute      | Value                          |
| -------------- | ------------------------------ |
| Source         | FEATURES.md §F1                |
| Components     | server-firewall.sh, helpers.sh |
| New Files      | playbooks/server-firewall.sh   |
| Modified Files | None                           |

**Interface Contract:**

| Method/Function      | Input                  | Output              | Errors                  |
| -------------------- | ---------------------- | ------------------- | ----------------------- |
| Playbook detect mode | `DEPLOYER_MODE=detect` | YAML with ports map | Exit 1 on write failure |

**Playbook Contract:**

| Variable             | Type   | Required | Description                  |
| -------------------- | ------ | -------- | ---------------------------- |
| DEPLOYER_OUTPUT_FILE | string | Yes      | Path for YAML output         |
| DEPLOYER_MODE        | string | Yes      | Must be `detect`             |
| DEPLOYER_PERMS       | string | Yes      | Permission level (root/sudo) |

Output (detect mode):

```yaml
status: success
ufw_installed: true
ufw_enabled: true
ufw_open_ports: [80, 443, 22]
ports:
    22: sshd
    80: caddy
    443: caddy
    3306: mysql
```

**Integration Points:**

- `helpers.sh`: Sources `get_listening_services()` function
- `PlaybooksTrait::executePlaybook()`: Executes with env vars

**Error Taxonomy:**

| Condition              | Message                              | Behavior       |
| ---------------------- | ------------------------------------ | -------------- |
| Output file write fail | "Error: Failed to write output file" | Exit 1, stderr |

**Edge Cases:**

| Scenario                 | Behavior                      |
| ------------------------ | ----------------------------- |
| No listening services    | Return empty `ports: {}` map  |
| `ss` not available       | Fall back to `netstat`        |
| Neither ss nor netstat   | Return empty `ports: {}` map  |
| Process name unavailable | Use "unknown" as process name |

**Verification:**

- Playbook returns YAML with ports map containing all listening TCP services
- Port numbers are integers, process names are strings
- Detection completes within 5 seconds

---

### F2: UFW Status Detection

| Attribute      | Value                      |
| -------------- | -------------------------- |
| Source         | FEATURES.md §F2            |
| Components     | server-firewall.sh         |
| New Files      | None (part of F1 playbook) |
| Modified Files | None                       |

**Interface Contract:**

| Function       | Input | Output                                               | Errors |
| -------------- | ----- | ---------------------------------------------------- | ------ |
| get_ufw_status | None  | Sets ufw_installed, ufw_enabled, ufw_open_ports vars | None   |

**Data Structures:**

| Name           | Type    | Values         | Purpose                        |
| -------------- | ------- | -------------- | ------------------------------ |
| ufw_installed  | boolean | true/false     | Whether UFW binary exists      |
| ufw_enabled    | boolean | true/false     | Whether UFW is active          |
| ufw_open_ports | array   | [80, 443, ...] | Currently allowed port numbers |

**Technical Notes:**

- Use `command -v ufw` to check installation
- Use `ufw status` to check if enabled (look for "Status: active")
- Parse `ufw status numbered` to extract allowed ports
- Handle IPv4/IPv6 rules appearing separately (deduplicate)

**Edge Cases:**

| Scenario             | Behavior                                 |
| -------------------- | ---------------------------------------- |
| UFW not installed    | `ufw_installed: false`, empty ports      |
| UFW disabled         | `ufw_enabled: false`, parse rules anyway |
| No rules configured  | `ufw_open_ports: []`                     |
| Named services (ssh) | Resolve to port number (22)              |

**Verification:**

- `ufw_open_ports` contains all currently allowed port numbers
- Ports are integers, not strings

---

### F7: UFW Installation

| Attribute      | Value                     |
| -------------- | ------------------------- |
| Source         | FEATURES.md §F7           |
| Components     | server-firewall.sh        |
| New Files      | None (part of apply mode) |
| Modified Files | None                      |

**Interface Contract:**

| Function    | Input | Output        | Errors                    |
| ----------- | ----- | ------------- | ------------------------- |
| install_ufw | None  | UFW installed | Exit 1 on install failure |

**Technical Notes:**

- Only called in apply mode when `ufw_installed: false`
- Use `apt_get_with_retry install -y -q ufw`
- Display progress: "Installing UFW..."

**Error Taxonomy:**

| Condition     | Message                                 | Behavior |
| ------------- | --------------------------------------- | -------- |
| apt-get fails | "Error: Failed to install UFW" (stderr) | Exit 1   |

**Verification:**

- After install, `command -v ufw` succeeds
- UFW version can be queried

---

### F4: SSH Port Protection

| Attribute      | Value                                         |
| -------------- | --------------------------------------------- |
| Source         | FEATURES.md §F4                               |
| Components     | ServerFirewallCommand.php, server-firewall.sh |
| New Files      | None                                          |
| Modified Files | None                                          |

**Interface Contract:**

PHP Command:

| Method            | Input               | Output                  | Errors |
| ----------------- | ------------------- | ----------------------- | ------ |
| getPortOptions    | ports[], sshPort    | Options excluding SSH   | None   |
| buildAllowedPorts | selected[], sshPort | Ports with SSH included | None   |

Playbook:

| Check                | Input                  | Output   | Errors                                                      |
| -------------------- | ---------------------- | -------- | ----------------------------------------------------------- |
| SSH port env var set | DEPLOYER_SSH_PORT      | Continue | "FATAL: DEPLOYER_SSH_PORT environment variable must be set" |
| SSH port in list     | DEPLOYER_ALLOWED_PORTS | Continue | "FATAL: SSH port {port} must always be allowed"             |

**Playbook Contract:**

| Variable               | Type   | Required | Description                    |
| ---------------------- | ------ | -------- | ------------------------------ |
| DEPLOYER_SSH_PORT      | string | Yes      | Server's SSH port (e.g., "22") |
| DEPLOYER_ALLOWED_PORTS | string | Yes      | Comma-separated ports to allow |

**Data Structures:**

| Name         | Type  | Example       | Purpose                  |
| ------------ | ----- | ------------- | ------------------------ |
| sshPort      | int   | 22            | From ServerDTO->port     |
| allowedPorts | array | [22, 80, 443] | Always includes SSH port |

**Integration Points:**

- `ServerDTO::$port`: Source of SSH port
- Playbook env validation: Defense in depth

**Error Taxonomy:**

| Condition               | Message                                                     | Behavior       |
| ----------------------- | ----------------------------------------------------------- | -------------- |
| SSH port env missing    | "FATAL: DEPLOYER_SSH_PORT environment variable must be set" | Exit 1, stderr |
| SSH port not in allowed | "FATAL: SSH port {port} must always be allowed"             | Exit 1, stderr |

**Security Constraints:**

- SSH port NEVER displayed in multiselect prompt
- SSH port ALWAYS added to allowed ports before playbook execution
- Playbook validates SSH port presence as defense in depth
- UFW reset sequence allows SSH BEFORE and AFTER reset

**Verification:**

- SSH port not visible in selection prompt
- Allowed ports sent to playbook always include SSH port
- Playbook exits with error if SSH port validation fails

---

### F8: Apply UFW Rules

| Attribute      | Value                     |
| -------------- | ------------------------- |
| Source         | FEATURES.md §F8           |
| Components     | server-firewall.sh        |
| New Files      | None (part of apply mode) |
| Modified Files | None                      |

**Interface Contract:**

| Function        | Input                                     | Output         | Errors            |
| --------------- | ----------------------------------------- | -------------- | ----------------- |
| apply_ufw_rules | DEPLOYER_SSH_PORT, DEPLOYER_ALLOWED_PORTS | UFW configured | Exit 1 on failure |

**Playbook Contract:**

| Variable               | Type   | Required | Description                    |
| ---------------------- | ------ | -------- | ------------------------------ |
| DEPLOYER_OUTPUT_FILE   | string | Yes      | Path for YAML output           |
| DEPLOYER_MODE          | string | Yes      | Must be `apply`                |
| DEPLOYER_PERMS         | string | Yes      | Permission level (root/sudo)   |
| DEPLOYER_SSH_PORT      | string | Yes      | SSH port to always allow       |
| DEPLOYER_ALLOWED_PORTS | string | Yes      | Comma-separated ports to allow |

Output (apply mode):

```yaml
status: success
ufw_installed: true
ufw_enabled: true
rules_applied: 4
```

**Technical Notes:**

SSH-safe reset sequence (order critical):

```bash
# 1. Allow SSH BEFORE reset (idempotent safety)
ufw allow $DEPLOYER_SSH_PORT/tcp

# 2. Reset UFW (clears all rules)
ufw --force reset

# 3. Re-allow SSH immediately after reset
ufw allow $DEPLOYER_SSH_PORT/tcp

# 4. Set default policies
ufw default deny incoming
ufw default allow outgoing

# 5. Allow user-selected ports (includes SSH)
for port in ALLOWED_PORTS; do
    ufw allow $port/tcp
done

# 6. Enable UFW
ufw --force enable
```

**Error Taxonomy:**

| Condition        | Message                              | Behavior |
| ---------------- | ------------------------------------ | -------- |
| ufw allow fails  | "Error: Failed to allow port {port}" | Exit 1   |
| ufw reset fails  | "Error: Failed to reset UFW"         | Exit 1   |
| ufw enable fails | "Error: Failed to enable UFW"        | Exit 1   |

**Edge Cases:**

| Scenario               | Behavior                             |
| ---------------------- | ------------------------------------ |
| UFW already enabled    | Reset and reconfigure (idempotent)   |
| Same ports as current  | Still reset and reapply (idempotent) |
| Only SSH port selected | Valid configuration (SSH only)       |

**Security Constraints:**

- SSH port allowed BEFORE ufw reset (prevents lockout during reset)
- SSH port re-allowed AFTER reset (ensures persistence)
- All ufw commands use `--force` to prevent interactive prompts

**Verification:**

- `ufw status` shows only allowed ports after apply
- SSH connection maintained throughout operation
- Running twice with same ports produces identical state

---

### F9: IPv4/IPv6 Support

| Attribute      | Value              |
| -------------- | ------------------ |
| Source         | FEATURES.md §F9    |
| Components     | server-firewall.sh |
| New Files      | None               |
| Modified Files | None               |

**Technical Notes:**

- UFW automatically creates IPv6 rules when `/etc/default/ufw` has `IPV6=yes` (Ubuntu/Debian default)
- Standard `ufw allow {port}` commands create both IPv4 and IPv6 rules
- No special handling required in playbook

**Verification:**

- `ufw status` shows rules for both IPv4 and IPv6
- Server with IPv6 enabled correctly blocks/allows on both protocols

---

### F3: Multi-select Prompt

| Attribute      | Value                                        |
| -------------- | -------------------------------------------- |
| Source         | FEATURES.md §F3                              |
| Components     | ServerFirewallCommand.php                    |
| New Files      | app/Console/Server/ServerFirewallCommand.php |
| Modified Files | None                                         |

**Interface Contract:**

| Method           | Input                   | Output               | Errors |
| ---------------- | ----------------------- | -------------------- | ------ |
| buildPortOptions | ports[], sshPort        | options[] for prompt | None   |
| getDefaultPorts  | ports[], ufwOpenPorts[] | preselected[]        | None   |

**Data Structures:**

| Name    | Type  | Example                          | Purpose               |
| ------- | ----- | -------------------------------- | --------------------- |
| options | array | ['80' => 'Port 80 (caddy)', ...] | Multiselect options   |
| default | array | ['80', '443']                    | Pre-checked port keys |

**Technical Notes:**

Format: `Port {port} ({process})` matching `ServerInfoCommand` display style

```php
$options = [];
foreach ($ports as $port => $process) {
    if ($port === $sshPort) {
        continue; // Never show SSH port
    }
    $options[(string) $port] = "Port {$port} ({$process})";
}
```

Pre-selection logic:

```php
$default = array_unique(array_merge(
    $ufwOpenPorts,           // Currently open in UFW
    array_intersect([80, 443], array_keys($ports))  // 80/443 if listening
));
$default = array_filter($default, fn($p) => $p !== $sshPort);
```

**Integration Points:**

- `IOService::promptMultiselect()`: Display multiselect prompt with hint

**Edge Cases:**

| Scenario                  | Behavior                              |
| ------------------------- | ------------------------------------- |
| No ports except SSH       | Display message, skip to confirmation |
| Port 80/443 not listening | Not pre-selected (only if listening)  |
| All ports already in UFW  | All shown as pre-selected             |

**Verification:**

- SSH port never appears in options
- Currently open UFW ports are pre-checked
- Ports 80 and 443 pre-checked if listening
- Format matches `server:info` display style

---

### F5: Default Ports Pre-selection

| Attribute      | Value                     |
| -------------- | ------------------------- |
| Source         | FEATURES.md §F5           |
| Components     | ServerFirewallCommand.php |
| New Files      | None (part of F3)         |
| Modified Files | None                      |

**Technical Notes:**

Pre-selection merge logic (covered in F3):

1. Start with currently open UFW ports
2. Add port 80 if it's in detected listening ports
3. Add port 443 if it's in detected listening ports
4. Remove SSH port from pre-selection (not displayed anyway)

**Verification:**

- Port 80 pre-checked only if service listening on 80
- Port 443 pre-checked only if service listening on 443
- User can uncheck these defaults

---

### F6: Confirmation Summary

| Attribute      | Value                     |
| -------------- | ------------------------- |
| Source         | FEATURES.md §F6           |
| Components     | ServerFirewallCommand.php |
| New Files      | None                      |
| Modified Files | None                      |

**Interface Contract:**

| Method              | Input                               | Output             | Errors |
| ------------------- | ----------------------------------- | ------------------ | ------ |
| displayConfirmation | selected[], ufwOpenPorts[], sshPort | void               | None   |
| calculateChanges    | selected[], ufwOpenPorts[]          | [opening, closing] | None   |

**Data Structures:**

| Name    | Type  | Example      | Purpose                        |
| ------- | ----- | ------------ | ------------------------------ |
| opening | array | [3306, 5432] | Ports to be newly opened       |
| closing | array | [6379]       | Ports currently open, to close |

**Technical Notes:**

Change calculation:

```php
$selected = array_map('intval', $selectedPorts);
$opening = array_diff($selected, $ufwOpenPorts);
$closing = array_diff($ufwOpenPorts, $selected);
// Remove SSH port from closing (defense)
$closing = array_filter($closing, fn($p) => $p !== $sshPort);
```

Display format (using displayDeets or custom):

```
┌ Firewall Changes ──────────────────────────────────────────────┐
│                                                                │
│   Opening: 3306, 5432                                          │
│   Closing: 6379                                                │
│                                                                │
│   SSH port will remain open.                                   │
│                                                                │
└────────────────────────────────────────────────────────────────┘
```

**Integration Points:**

- `IOService::promptConfirm()`: Yes/no confirmation
- `BaseCommand::displayDeets()`: Formatted output (or custom box)

**Edge Cases:**

| Scenario           | Behavior                                |
| ------------------ | --------------------------------------- |
| No changes needed  | Display "No changes needed", skip apply |
| Only opening ports | Show opening only, no closing section   |
| Only closing ports | Show closing only, no opening section   |
| User declines      | Return without applying, no error       |

**Verification:**

- Opening shows ports not currently in UFW
- Closing shows ports in UFW but not selected
- SSH port never shown in closing list
- Confirmation required before apply

---

### F10: CLI --allow Option

| Attribute      | Value                     |
| -------------- | ------------------------- |
| Source         | FEATURES.md §F10          |
| Components     | ServerFirewallCommand.php |
| New Files      | None                      |
| Modified Files | None                      |

**Interface Contract:**

| Method           | Input           | Output         | Errors |
| ---------------- | --------------- | -------------- | ------ |
| configure        | None            | Option defined | None   |
| parseAllowOption | string "80,443" | [80, 443]      | None   |

**Data Structures:**

| Name        | Type   | Example         | Purpose                   |
| ----------- | ------ | --------------- | ------------------------- |
| --allow     | string | "80,443,3306"   | Comma-separated port list |
| parsedPorts | array  | [80, 443, 3306] | Integer array of ports    |

**Technical Notes:**

Option definition:

```php
$this->addOption(
    'allow',
    null,
    InputOption::VALUE_REQUIRED,
    'Comma-separated list of ports to allow (e.g., 80,443,3306)'
);
```

Behavior when `--allow` provided:

1. Parse comma-separated string to integer array
2. Filter to detected listening ports only (F11)
3. Skip multiselect prompt
4. Proceed directly to confirmation summary
5. SSH port automatically added (not required in --allow)

**Integration Points:**

- `InputInterface::getOption()`: Get CLI option value

**Edge Cases:**

| Scenario           | Behavior                     |
| ------------------ | ---------------------------- |
| Empty --allow      | Treat as no ports (SSH only) |
| Non-numeric values | Filter out invalid entries   |
| Duplicate ports    | Deduplicate                  |

**Verification:**

- `--allow=80,443` skips multiselect, allows those ports
- SSH port not required in --allow list
- Works with --server for full automation

---

### F11: Filter Invalid Ports

| Attribute      | Value                     |
| -------------- | ------------------------- |
| Source         | FEATURES.md §F11          |
| Components     | ServerFirewallCommand.php |
| New Files      | None                      |
| Modified Files | None                      |

**Interface Contract:**

| Method            | Input                   | Output  | Errors |
| ----------------- | ----------------------- | ------- | ------ |
| filterToListening | requested[], detected[] | valid[] | None   |

**Data Structures:**

| Name      | Type  | Example         | Purpose                  |
| --------- | ----- | --------------- | ------------------------ |
| requested | array | [80, 443, 9999] | Ports from --allow       |
| detected  | array | [80, 443, 3306] | Actually listening ports |
| filtered  | array | [9999]          | Ports that were removed  |
| valid     | array | [80, 443]       | Ports to actually allow  |

**Technical Notes:**

```php
$valid = array_intersect($requested, array_keys($detectedPorts));
$filtered = array_diff($requested, $valid);

foreach ($filtered as $port) {
    $this->warn("Port {$port} is not listening and will be ignored");
}
```

**Integration Points:**

- `BaseCommand::warn()`: Display warning for filtered ports

**Edge Cases:**

| Scenario             | Behavior                                   |
| -------------------- | ------------------------------------------ |
| All ports filtered   | Only SSH remains, show appropriate message |
| No ports filtered    | No warnings displayed                      |
| Single port filtered | Single warning message                     |

**Verification:**

- Warning displayed for each filtered port
- Valid ports still processed
- Command does not fail on invalid ports

---

### F14: Common Port Labels (Could Have)

| Attribute      | Value                     |
| -------------- | ------------------------- |
| Source         | FEATURES.md §F14          |
| Components     | ServerFirewallCommand.php |
| New Files      | None                      |
| Modified Files | None                      |

**Data Structures:**

| Name        | Type  | Example                             | Purpose        |
| ----------- | ----- | ----------------------------------- | -------------- |
| PORT_LABELS | array | [80 => 'HTTP', 443 => 'HTTPS', ...] | Friendly names |

**Technical Notes:**

```php
private const PORT_LABELS = [
    22 => 'SSH',
    80 => 'HTTP',
    443 => 'HTTPS',
    3306 => 'MySQL',
    5432 => 'PostgreSQL',
    6379 => 'Redis',
    27017 => 'MongoDB',
];
```

Display format when label differs from process:

- If process name matches label concept: `Port 80 (caddy)`
- If label adds value: `Port 3306 (mysqld) [MySQL]`

**Verification:**

- Common ports show friendly labels
- Unknown ports show process name only

---

## Command Structure

### ServerFirewallCommand.php

```php
#[AsCommand(
    name: 'server:firewall',
    description: 'Configure server firewall rules'
)]
class ServerFirewallCommand extends BaseCommand
{
    use ServersTrait;
    use PlaybooksTrait;

    protected function configure(): void
    {
        parent::configure();
        $this->addOption('server', null, InputOption::VALUE_REQUIRED, 'Server name');
        $this->addOption('allow', null, InputOption::VALUE_REQUIRED, 'Ports to allow (comma-separated)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // 1. Select server (validates SSH, gets info)
        // 2. Execute playbook in detect mode
        // 3. Build multiselect options (or parse --allow)
        // 4. Display confirmation summary
        // 5. Execute playbook in apply mode
        // 6. Display success
    }
}
```

### Playbook Environment Variables

| Variable               | Mode  | Type   | Required | Description                   |
| ---------------------- | ----- | ------ | -------- | ----------------------------- |
| DEPLOYER_OUTPUT_FILE   | Both  | string | Yes      | YAML output path              |
| DEPLOYER_DISTRO        | Both  | string | Yes      | Distribution (ubuntu/debian)  |
| DEPLOYER_PERMS         | Both  | string | Yes      | Permission level (root/sudo)  |
| DEPLOYER_MODE          | Both  | string | Yes      | `detect` or `apply`           |
| DEPLOYER_SSH_PORT      | Apply | string | Yes      | SSH port to protect           |
| DEPLOYER_ALLOWED_PORTS | Apply | string | Yes      | Comma-separated allowed ports |

### Playbook Output

Detect mode:

```yaml
status: success
ufw_installed: true
ufw_enabled: true
ufw_open_ports: [22, 80, 443]
ports:
    22: sshd
    80: caddy
    443: caddy
    3306: mysql
```

Apply mode:

```yaml
status: success
ufw_installed: true
ufw_enabled: true
rules_applied: 4
```

---

## Error Handling Summary

| Location | Condition                  | Message                                                     | Exit |
| -------- | -------------------------- | ----------------------------------------------------------- | ---- |
| PHP      | Server not found           | "Server '{name}' not found in inventory"                    | 1    |
| PHP      | SSH connection fails       | (handled by ServersTrait)                                   | 1    |
| PHP      | Playbook fails             | (handled by PlaybooksTrait)                                 | 1    |
| PHP      | User declines confirmation | (silent return)                                             | 0    |
| Playbook | Output file write fails    | "Error: Failed to write output file"                        | 1    |
| Playbook | SSH port env missing       | "FATAL: DEPLOYER_SSH_PORT environment variable must be set" | 1    |
| Playbook | SSH port not in allowed    | "FATAL: SSH port {port} must always be allowed"             | 1    |
| Playbook | UFW install fails          | "Error: Failed to install UFW"                              | 1    |
| Playbook | UFW command fails          | "Error: Failed to {action}"                                 | 1    |
