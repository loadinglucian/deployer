# Implementation Plan - Server Firewall Command

## Overview

The `server:firewall` command provides interactive UFW management through a single playbook with detect/apply modes. Users select ports via multiselect prompt, with SSH port automatically protected.

**Source:** [PRD](./01-PRD.md) | [FEATURES](./02-FEATURES.md) | [SPEC](./03-SPEC.md)

## File Changes

| Type | File                                           | Purpose                                                     |
| ---- | ---------------------------------------------- | ----------------------------------------------------------- |
| Mod  | `playbooks/helpers.sh`                         | Add `get_listening_services()` function                     |
| Mod  | `playbooks/server-info.sh`                     | Remove inline `get_listening_services()`, use shared helper |
| New  | `playbooks/server-firewall.sh`                 | UFW detection and rule application                          |
| New  | `app/Console/Server/ServerFirewallCommand.php` | Interactive firewall configuration command                  |

## Prerequisites

**Reference Patterns:**

- `playbooks/server-info.sh` - Existing playbook structure, YAML output, mode handling
- `app/Console/Server/ServerInfoCommand.php` - Server selection, playbook execution, deets display
- `playbooks/helpers.sh` - Shared function patterns (`run_cmd`, `apt_get_with_retry`)

## Milestones

### Milestone 1: Shared Port Detection Helper

| Features | F12 (Shared Port Detection Helper) |
| -------- | ---------------------------------- |
| Branch   | `server-firewall/milestone-1`      |

**Deliverables:**

- `playbooks/helpers.sh` with `get_listening_services()` function
- Updated `playbooks/server-info.sh` sourcing the helper

**Steps:**

1. Extract `get_listening_services()` from `server-info.sh` lines 228-262 into `helpers.sh`
2. Add new section header `# Listening Services` in `helpers.sh`
3. Update `server-info.sh` to rely on helpers being inlined (comment already exists at line 31)

**Integration:** Function uses existing `run_cmd` from helpers.sh for privilege handling

**Verification:**

- [ ] `server:info` command produces identical output before/after refactor
- [ ] `get_listening_services` returns `{port}:{process}` format, sorted by port

**Enables:** Milestone 2

---

### Milestone 2: Detection Playbook

| Features | F1 (Port Detection), F2 (UFW Status Detection) |
| -------- | ---------------------------------------------- |
| Branch   | `server-firewall/milestone-2`                  |

**Deliverables:**

- `playbooks/server-firewall.sh` with detect mode

**Steps:**

1. Create playbook skeleton with shebang, header comment, env validation (`DEPLOYER_OUTPUT_FILE`, `DEPLOYER_MODE`)
2. Implement `get_ufw_status()` function:
    - Check `command -v ufw` for installation
    - Parse `ufw status` for enabled state
    - Parse `ufw status numbered` to extract allowed ports (handle IPv4/IPv6 dedup)
3. Implement `detect_mode()`:
    - Call `get_listening_services()` from helpers
    - Call `get_ufw_status()`
    - Output YAML per 03-SPEC.md §F1: `status`, `ufw_installed`, `ufw_enabled`, `ufw_open_ports`, `ports`
4. Wire main() to switch on `DEPLOYER_MODE=detect`

**Integration:** Sources helpers via comment placeholder (PHP inlines automatically)

**Verification:**

- [ ] `DEPLOYER_MODE=detect` outputs valid YAML with all required keys
- [ ] Works when UFW is not installed (`ufw_installed: false`, `ufw_open_ports: []`)
- [ ] Works when UFW is disabled (`ufw_enabled: false`, rules still parsed)
- [ ] Ports are integers, process names are strings

**Enables:** Milestone 3

---

### Milestone 3: Apply Playbook

| Features | F4 (SSH Port Protection), F7 (UFW Installation), F8 (Apply Rules), F9 (IPv4/IPv6) |
| -------- | --------------------------------------------------------------------------------- |
| Branch   | `server-firewall/milestone-3`                                                     |

**Deliverables:**

- `playbooks/server-firewall.sh` with apply mode

**Steps:**

1. Add env var validation for apply mode: `DEPLOYER_SSH_PORT`, `DEPLOYER_ALLOWED_PORTS`
2. Implement `validate_ssh_port()`:
    - Fail if `DEPLOYER_SSH_PORT` not set
    - Fail if SSH port not in `DEPLOYER_ALLOWED_PORTS` (defense in depth)
3. Implement `install_ufw()`:
    - Use `apt_get_with_retry install -y -q ufw`
    - Display progress message
4. Implement `apply_ufw_rules()` with SSH-safe reset sequence per 03-SPEC.md §F8:
    - `ufw allow $SSH_PORT/tcp` (before reset)
    - `ufw --force reset`
    - `ufw allow $SSH_PORT/tcp` (after reset)
    - `ufw default deny incoming`
    - `ufw default allow outgoing`
    - Loop: `ufw allow $port/tcp` for each allowed port
    - `ufw --force enable`
5. Implement `apply_mode()`:
    - Call `validate_ssh_port()`
    - Call `install_ufw()` if needed
    - Call `apply_ufw_rules()`
    - Output YAML: `status`, `ufw_installed`, `ufw_enabled`, `rules_applied`
6. Wire main() to switch on `DEPLOYER_MODE=apply`

**Integration:** Uses `apt_get_with_retry` and `run_cmd` from helpers.sh

**Verification:**

- [ ] Playbook exits with error if `DEPLOYER_SSH_PORT` not set
- [ ] Playbook exits with error if SSH port not in allowed list
- [ ] UFW installed if missing before applying rules
- [ ] SSH connection maintained throughout operation
- [ ] `ufw status` shows only allowed ports after apply
- [ ] Running twice produces identical state (idempotent)

**Enables:** Milestone 4

---

### Milestone 4: PHP Command (Interactive)

| Features | F3 (Multi-select Prompt), F5 (Default Ports), F6 (Confirmation Summary) |
| -------- | ----------------------------------------------------------------------- |
| Branch   | `server-firewall/milestone-4`                                           |

**Deliverables:**

- `app/Console/Server/ServerFirewallCommand.php` with interactive flow

**Steps:**

1. Create command skeleton with `#[AsCommand]`, traits (`ServersTrait`, `PlaybooksTrait`)
2. Implement `configure()`: add `--server` option
3. Implement `execute()` flow:
    - Call `selectServer()` to get ServerDTO
    - Execute playbook with `DEPLOYER_MODE=detect`
    - Parse YAML response for ports, UFW status
4. Implement `buildPortOptions(array $ports, int $sshPort)`:
    - Filter out SSH port
    - Format as `Port {port} ({process})` per 03-SPEC.md §F3
5. Implement `getDefaultPorts(array $ports, array $ufwOpenPorts)`:
    - Merge UFW open ports + 80/443 if listening
    - Filter out SSH port
6. Display multiselect prompt via `IOService::promptMultiselect()` with hint
7. Implement `calculateChanges(array $selected, array $ufwOpenPorts)`:
    - Return `[opening, closing]` arrays
8. Implement `displayConfirmation()`:
    - Show opening/closing ports per 03-SPEC.md §F6 format
    - Show "SSH port will remain open" message
    - Handle "No changes needed" case
9. Implement confirmation via `IOService::promptConfirm()`
10. Build allowed ports: merge selected + SSH port
11. Execute playbook with `DEPLOYER_MODE=apply`, pass `DEPLOYER_SSH_PORT`, `DEPLOYER_ALLOWED_PORTS`
12. Display success message

**Integration:**

- Uses `ServersTrait::selectServer()` for server selection
- Uses `PlaybooksTrait::executePlaybook()` for both detect and apply modes
- SSH port from `ServerDTO::$port`

**Verification:**

- [ ] SSH port never appears in multiselect options
- [ ] Currently open UFW ports are pre-selected
- [ ] Ports 80/443 pre-selected if listening
- [ ] Confirmation shows opening/closing lists correctly
- [ ] SSH port always included in playbook call
- [ ] Success message displayed after apply

**Enables:** Milestone 5

---

### Milestone 5: CLI Options

| Features | F10 (CLI --allow Option), F11 (Filter Invalid Ports), F14 (Common Port Labels) |
| -------- | ------------------------------------------------------------------------------ |
| Branch   | `server-firewall/milestone-5`                                                  |

**Deliverables:**

- `--allow` option for non-interactive mode
- Port validation and filtering
- Common port labels (optional)

**Steps:**

1. Add `--allow` option in `configure()`: `InputOption::VALUE_REQUIRED`
2. Implement `parseAllowOption(string $value)`:
    - Split by comma
    - Filter to integers
    - Deduplicate
3. Implement `filterToListening(array $requested, array $detected)`:
    - Use `array_intersect()` to keep only listening ports
    - Display warning for each filtered port via `$this->warn()`
4. Modify `execute()`:
    - If `--allow` provided, parse and filter
    - Skip multiselect prompt
    - Proceed to confirmation
5. Add `PORT_LABELS` constant for common ports (22, 80, 443, 3306, 5432, 6379, 27017)
6. Update `buildPortOptions()` to include label if different from process name

**Integration:** Works with existing `--server` option from ServersTrait

**Verification:**

- [ ] `--allow=80,443` skips multiselect, allows those ports
- [ ] `--allow=80,9999` shows warning for 9999, allows only 80
- [ ] Empty `--allow` after filtering results in SSH-only config
- [ ] Common ports show friendly labels (e.g., "HTTP", "MySQL")

---

## Implementation Notes

**Error Handling (from 03-SPEC.md §Error Handling Summary):**

| Location | Condition                  | Exit       |
| -------- | -------------------------- | ---------- |
| PHP      | Server not found           | 1          |
| PHP      | User declines confirmation | 0 (silent) |
| Playbook | SSH port env missing       | 1          |
| Playbook | SSH port not in allowed    | 1          |
| Playbook | UFW command fails          | 1          |

**Defense in Depth:** SSH port validated in both PHP command and bash playbook. The playbook validation catches bugs in the PHP code.

**Playbook Env Vars:**

| Variable                 | Mode  | Description           |
| ------------------------ | ----- | --------------------- |
| `DEPLOYER_OUTPUT_FILE`   | Both  | YAML output path      |
| `DEPLOYER_MODE`          | Both  | `detect` or `apply`   |
| `DEPLOYER_PERMS`         | Both  | `root` or `sudo`      |
| `DEPLOYER_SSH_PORT`      | Apply | SSH port to protect   |
| `DEPLOYER_ALLOWED_PORTS` | Apply | Comma-separated ports |

## Completion Criteria

- [ ] All milestones verified
- [ ] Quality gates pass (Rector, Pint, PHPStan)
- [ ] Manual test: Interactive flow on Ubuntu 22.04
- [ ] Manual test: `--server` and `--allow` options work together
- [ ] Manual test: SSH connection maintained during UFW reset
- [ ] Manual test: Running twice produces identical state
