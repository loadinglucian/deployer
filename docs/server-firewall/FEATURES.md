# Features - Server Firewall Command

## Summary

| Priority    | Count |
| ----------- | ----- |
| Must have   | 12    |
| Should have | 0     |
| Could have  | 1     |
| Won't have  | 0     |
| **Total**   | 13    |

| Category            | Features |
| ------------------- | -------- |
| Port Detection      | 2        |
| UFW Management      | 4        |
| User Interface      | 3        |
| Safety & Validation | 2        |
| CLI Options         | 2        |

## Dependencies Overview

```
F12 (Shared Helper) ─┬─► F1 (Port Detection)
                     └─► F2 (UFW Status Detection)

F1 + F2 ──► F3 (Multi-select Prompt) ──► F6 (Confirmation Summary)

F4 (SSH Protection) ──► F8 (Apply Rules)

F7 (UFW Installation) ──► F8 (Apply Rules)

F1 ──► F10/F11 (CLI Options)
```

**Critical Path:** F12 → F1/F2 → F3 → F6 → F8

## Table of Contents

- [Port Detection](#port-detection)
- [UFW Management](#ufw-management)
- [User Interface](#user-interface)
- [Safety & Validation](#safety--validation)
- [CLI Options](#cli-options)

---

## Port Detection

### F1: Port Detection

| Attribute  | Value                             |
| ---------- | --------------------------------- |
| Priority   | Must have                         |
| Complexity | Medium                            |
| Phase      | 1                                 |
| Source     | PRD §Functional Requirements - F1 |
| Depends on | F12                               |
| Blocks     | F3, F10, F11                      |

**User Story:** As a server administrator, I want to see all listening ports and their associated services so that I can make informed decisions about which ports to allow.

**Description:**
Detect all listening TCP and UDP ports on the server along with the process/service name using `ss` or `netstat`. This information is used to populate the multi-select prompt and validate CLI-provided port lists.

**Acceptance Criteria:**

- [ ] Detects all TCP listening ports
- [ ] Detects all UDP listening ports
- [ ] Associates each port with its process/service name
- [ ] Returns structured data usable by the command
- [ ] Uses shared helper function from `helpers.sh`
- [ ] Completes within 5 seconds

**Technical Notes:**

- Use `ss -tulnp` as primary method (preferred on modern systems)
- Fall back to `netstat` if `ss` unavailable
- Parse output to extract port number, protocol, and process name
- Handle cases where process name is unavailable (show "unknown")

---

### F12: Shared Port Detection Helper

| Attribute  | Value                              |
| ---------- | ---------------------------------- |
| Priority   | Must have                          |
| Complexity | Medium                             |
| Phase      | 1                                  |
| Source     | PRD §Functional Requirements - F12 |
| Depends on | None                               |
| Blocks     | F1, F2                             |

**User Story:** As a developer, I want port detection logic in a shared location so that `server-info` and `server-firewall` commands use consistent detection.

**Description:**
Extract the `get_listening_services()` function from `server-info.sh` into `playbooks/helpers.sh`. Update `server-info.sh` to source this shared helper. The function returns port:process pairs for both TCP and UDP.

**Acceptance Criteria:**

- [ ] `helpers.sh` contains `get_listening_services()` function
- [ ] Function detects both TCP and UDP ports
- [ ] `server-info.sh` updated to source `helpers.sh`
- [ ] `server-info.sh` behavior unchanged after refactor
- [ ] Function is idempotent and safe to call multiple times

**Technical Notes:**

- Place in `playbooks/helpers.sh`
- Use `source helpers.sh` pattern in consuming playbooks
- Ensure relative path works regardless of execution directory

---

## UFW Management

### F2: UFW Status Detection

| Attribute  | Value                             |
| ---------- | --------------------------------- |
| Priority   | Must have                         |
| Complexity | Low                               |
| Phase      | 2                                 |
| Source     | PRD §Functional Requirements - F2 |
| Depends on | F12                               |
| Blocks     | F3, F5                            |

**User Story:** As a server administrator, I want to see which ports are currently allowed in UFW so that I can understand the current firewall state.

**Description:**
Detect currently allowed ports in UFW rules. This information is used to pre-check ports in the multi-select prompt and show current state to the user.

**Acceptance Criteria:**

- [ ] Detects all currently allowed ports in UFW
- [ ] Handles UFW being disabled (returns empty list)
- [ ] Handles UFW not installed (returns empty list)
- [ ] Parses both numbered and named rules
- [ ] Returns structured list of allowed ports

**Technical Notes:**

- Use `ufw status numbered` to get current rules
- Parse output to extract port numbers
- Handle IPv4 and IPv6 rules (may appear separately)

---

### F7: UFW Installation

| Attribute  | Value                             |
| ---------- | --------------------------------- |
| Priority   | Must have                         |
| Complexity | Low                               |
| Phase      | 2                                 |
| Source     | PRD §Functional Requirements - F7 |
| Depends on | None                              |
| Blocks     | F8                                |

**User Story:** As a server administrator setting up a new server, I want UFW automatically installed if missing so that I can configure the firewall without manual setup.

**Description:**
Check if UFW is installed on the server. If not present, install it using the system package manager before proceeding with firewall configuration.

**Acceptance Criteria:**

- [ ] Detects whether UFW is installed
- [ ] Installs UFW using `apt-get` if not present
- [ ] Displays message to user during installation
- [ ] Handles installation failures gracefully
- [ ] Works on Ubuntu 20.04, 22.04, 24.04 and Debian 11, 12

**Technical Notes:**

- Use `command -v ufw` or `which ufw` to check installation
- Use `apt-get install -y ufw` for installation
- Consider running `apt-get update` first if install fails

---

### F8: Apply UFW Rules

| Attribute  | Value                             |
| ---------- | --------------------------------- |
| Priority   | Must have                         |
| Complexity | High                              |
| Phase      | 2                                 |
| Source     | PRD §Functional Requirements - F8 |
| Depends on | F4, F7                            |
| Blocks     | None                              |

**User Story:** As a server administrator, I want my firewall rules applied atomically and safely so that I never lose SSH access during configuration.

**Description:**
Reset all UFW rules and apply the user's selected ports plus the SSH port. The reset must be performed in a specific order to prevent SSH lockout: allow SSH first, reset, re-allow SSH, set defaults, allow other ports, enable.

**Acceptance Criteria:**

- [ ] Allows SSH port before any reset operation
- [ ] Resets UFW to clear all existing rules
- [ ] Re-allows SSH port immediately after reset
- [ ] Sets default policy to deny incoming, allow outgoing
- [ ] Allows all user-selected ports
- [ ] Enables UFW (if not already enabled)
- [ ] Applies rules to both IPv4 and IPv6
- [ ] Running multiple times with same selection produces same result (idempotent)

**Technical Notes:**

- Order is critical: SSH allow → reset → SSH allow → default deny → other ports → enable
- Use `ufw --force reset` to avoid interactive prompts
- Use `ufw --force enable` to avoid interactive prompts
- Allow both TCP and UDP for SSH port

---

### F9: IPv4/IPv6 Support

| Attribute  | Value                             |
| ---------- | --------------------------------- |
| Priority   | Must have                         |
| Complexity | Low                               |
| Phase      | 2                                 |
| Source     | PRD §Functional Requirements - F9 |
| Depends on | F8                                |
| Blocks     | None                              |

**User Story:** As a server administrator with IPv6-enabled infrastructure, I want firewall rules applied to both IPv4 and IPv6 so that my server is protected on all network interfaces.

**Description:**
Ensure all UFW rules are applied to both IPv4 and IPv6. UFW handles this automatically when using standard `ufw allow` commands, but verification is needed.

**Acceptance Criteria:**

- [ ] Rules appear in both IPv4 and IPv6 sections of `ufw status`
- [ ] Verified on servers with IPv6 enabled
- [ ] Verified on servers with IPv6 disabled (no errors)

**Technical Notes:**

- UFW automatically creates IPv6 rules when `/etc/default/ufw` has `IPV6=yes`
- No special handling needed if using standard `ufw allow` commands
- Verify during QA phase

---

## User Interface

### F3: Multi-select Prompt

| Attribute  | Value                             |
| ---------- | --------------------------------- |
| Priority   | Must have                         |
| Complexity | Medium                            |
| Phase      | 2                                 |
| Source     | PRD §Functional Requirements - F3 |
| Depends on | F1, F2                            |
| Blocks     | F6                                |

**User Story:** As a server administrator, I want an interactive prompt showing all available ports so that I can easily select which ones to allow.

**Description:**
Display a multi-select prompt with all detected listening ports formatted as `Port {port} ({service})` to match `server:info` output style. Pre-check ports that are currently allowed in UFW, plus ports 80 and 443 by default. The prompt hint explains that pre-checked items are currently open, making the current firewall state immediately visible without a separate display. Exclude the SSH port from the list (it's always allowed).

**Acceptance Criteria:**

- [ ] Displays all detected listening ports except SSH port
- [ ] Format: `Port {port} ({service})` matching `server:info` style
- [ ] Currently open UFW ports are pre-checked
- [ ] Ports 80 and 443 are pre-checked by default (if listening)
- [ ] SSH port is never displayed in the list
- [ ] User can toggle selections before confirming
- [ ] Uses Laravel Prompts multiselect component
- [ ] Prompt includes hint: "Pre-selected ports are currently open"

**Technical Notes:**

- Use `multiselect()` from Laravel Prompts with `hint` parameter
- Merge currently open ports + default ports (80, 443) for pre-selection
- Filter out SSH port from display list
- Handle case where no ports are detected (except SSH)
- Format matches `ServerInfoCommand` display style

---

### F5: Default Ports Pre-selection

| Attribute  | Value                             |
| ---------- | --------------------------------- |
| Priority   | Must have                         |
| Complexity | Low                               |
| Phase      | 2                                 |
| Source     | PRD §Functional Requirements - F5 |
| Depends on | F2                                |
| Blocks     | F3                                |

**User Story:** As a server administrator, I want common web ports (80, 443) pre-selected so that I don't accidentally block HTTP/HTTPS traffic.

**Description:**
When displaying the multi-select prompt, pre-check ports 80 and 443 in addition to any currently open UFW ports. This provides sensible defaults for web servers.

**Acceptance Criteria:**

- [ ] Port 80 is pre-checked if it's a detected listening port
- [ ] Port 443 is pre-checked if it's a detected listening port
- [ ] Pre-selection merges with currently open UFW ports
- [ ] User can uncheck these defaults if desired

**Technical Notes:**

- Only pre-check if the port is actually listening (don't show non-listening ports)
- Merge with F2's currently open ports for complete pre-selection list

---

### F6: Confirmation Summary

| Attribute  | Value                             |
| ---------- | --------------------------------- |
| Priority   | Must have                         |
| Complexity | Low                               |
| Phase      | 2                                 |
| Source     | PRD §Functional Requirements - F6 |
| Depends on | F3                                |
| Blocks     | F8                                |

**User Story:** As a server administrator, I want to see a summary of changes before applying so that I can verify I'm not making a mistake.

**Description:**
After port selection, display a confirmation summary showing which ports will be opened and which will be closed. Require explicit user confirmation before applying changes.

**Acceptance Criteria:**

- [ ] Shows list of ports to be opened (newly allowed)
- [ ] Shows list of ports to be closed (currently allowed but not selected)
- [ ] Shows message that SSH port will remain open
- [ ] Requires yes/no confirmation
- [ ] Does not apply changes if user says no
- [ ] Format matches PRD example output

**Technical Notes:**

- Compare selected ports vs currently open ports to determine changes
- Use `confirm()` from Laravel Prompts
- Display "No changes needed" if selection matches current state

---

### F14: Common Port Labels

| Attribute  | Value                              |
| ---------- | ---------------------------------- |
| Priority   | Could have                         |
| Complexity | Low                                |
| Phase      | 3                                  |
| Source     | PRD §Functional Requirements - F14 |
| Depends on | F3                                 |
| Blocks     | None                               |

**User Story:** As a server administrator, I want friendly names for common ports so that I can quickly understand what each port is for.

**Description:**
Display friendly names for well-known ports alongside the process name. For example, show "HTTP" for port 80, "HTTPS" for port 443, "MySQL" for port 3306.

**Acceptance Criteria:**

- [ ] Common ports display friendly names
- [ ] Format: `{port} ({friendly_name} - {service})` or similar
- [ ] Covers at least: 80, 443, 22, 3306, 5432, 6379, 27017
- [ ] Falls back to process name only for unknown ports

**Technical Notes:**

- Maintain a map of port → friendly name in PHP
- Only display if different from detected process name
- Consider format: "80 (HTTP/caddy)" or "80 (caddy) [HTTP]"

---

## Safety & Validation

### F4: SSH Port Protection

| Attribute  | Value                             |
| ---------- | --------------------------------- |
| Priority   | Must have                         |
| Complexity | Medium                            |
| Phase      | 2                                 |
| Source     | PRD §Functional Requirements - F4 |
| Depends on | None                              |
| Blocks     | F3, F8                            |

**User Story:** As a server administrator, I want the SSH port always protected so that I never accidentally lock myself out of the server.

**Description:**
The server's configured SSH port (from ServerDTO) must never be displayed in the selection list and must always be included in the allow rules. Validation occurs in both PHP command and bash playbook as defense in depth.

**Acceptance Criteria:**

- [ ] SSH port never appears in multi-select prompt
- [ ] SSH port always included in allowed ports sent to playbook
- [ ] Playbook validates `DEPLOYER_SSH_PORT` environment variable is set
- [ ] Playbook validates SSH port is in allowed list
- [ ] Playbook aborts with error if SSH validation fails
- [ ] Uses port from `$server->port` (ServerDTO)

**Technical Notes:**

- PHP: Filter SSH port from display list, add to allowed list
- Bash: Validate `DEPLOYER_SSH_PORT` env var exists and is in allowed list
- Bash: Exit with error code 1 if validation fails
- Double validation provides defense in depth

---

## CLI Options

### F10: CLI --allow Option

| Attribute  | Value                              |
| ---------- | ---------------------------------- |
| Priority   | Must have                          |
| Complexity | Medium                             |
| Phase      | 3                                  |
| Source     | PRD §Functional Requirements - F10 |
| Depends on | F1                                 |
| Blocks     | F11                                |

**User Story:** As a DevOps engineer, I want to specify ports via CLI so that I can script firewall configuration across multiple servers.

**Description:**
Support `--allow=80,443,3306` option for non-interactive execution. When provided, skip the multi-select prompt and use the specified ports (after filtering to detected ports only).

**Acceptance Criteria:**

- [ ] Accepts comma-separated port list via `--allow` option
- [ ] Skips multi-select prompt when `--allow` is provided
- [ ] Still shows confirmation summary before applying
- [ ] Works with `--server` option for full non-interactive use
- [ ] SSH port automatically added (not required in `--allow` list)

**Technical Notes:**

- Parse comma-separated string to array of integers
- Combine with `--server` option from ServersTrait
- Consider adding `--force` to skip confirmation for full automation

---

### F11: Filter Invalid Ports

| Attribute  | Value                              |
| ---------- | ---------------------------------- |
| Priority   | Must have                          |
| Complexity | Low                                |
| Phase      | 3                                  |
| Source     | PRD §Functional Requirements - F11 |
| Depends on | F1, F10                            |
| Blocks     | None                               |

**User Story:** As a DevOps engineer, I want invalid ports silently filtered so that my scripts don't fail when a port isn't listening on a particular server.

**Description:**
When using `--allow`, filter the port list to only include ports that are actually detected as listening. Display a note for any filtered ports but continue execution.

**Acceptance Criteria:**

- [ ] Ports not in detected listening list are filtered out
- [ ] Displays note for each filtered port (e.g., "Port 9999 is not listening and will be ignored")
- [ ] Does not cause command failure
- [ ] Remaining valid ports are still applied
- [ ] Empty list after filtering shows appropriate message

**Technical Notes:**

- Use `array_intersect()` to filter to detected ports
- Display filtered ports as informational message, not error
- Handle edge case where all ports are filtered (only SSH remains)
