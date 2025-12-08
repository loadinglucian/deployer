---
description: Create implementation plan from PRD, FEATURES, and SPEC
model: opus
allowedTools: ['Read', 'Write', 'Glob', 'AskUserQuestion']
---

# Implementation Plan

Create PLAN.md from PRD, FEATURES, and SPEC documents.

## Process

1. Read all three documents from `docs/{feature}/`
1. Extract critical path from FEATURES.md dependency graph
1. Group features into milestones following dependency order
1. Synthesize implementation details from SPEC.md
1. Save to `docs/{feature}/PLAN.md`

## Milestone Grouping

- Respect dependencies (no forward references)
- Group features modifying same file/component
- Each milestone independently verifiable
- Playbooks before PHP commands
- Core functionality before CLI options
- 2-4 features per milestone maximum

## PLAN.md Template

````markdown
# Implementation Plan - {Product Name}

## Overview

{1-2 sentence summary}

**Source:** [PRD](./PRD.md) | [FEATURES](./FEATURES.md) | [SPEC](./SPEC.md)

## File Changes

| Type | File | Purpose |
| ---- | ---- | ------- |
| New | `{path}` | {description} |
| Mod | `{path}` | {changes} |

## Prerequisites

**Reference Patterns:**

- `{existing-file}` - {pattern to study}

## Milestones

### Milestone 1: {Name}

| Features | F{n}, F{n} |
| -------- | ---------- |
| Branch   | `{feature}/milestone-1` |

**Deliverables:**

- {Concrete file or function}

**Steps:**

1. {Verb} {specific task}
1. {Next step}

**Integration:** {How this connects to existing code}

**Verification:**

- [ ] {Testable criterion}

**Enables:** Milestone {n}

---

## Implementation Notes

{Cross-milestone guidance, error handling summary from SPEC}

## Completion Criteria

- [ ] All milestones verified
- [ ] Quality gates pass
- [ ] Manual test against PRD user journeys
````

## Example

````markdown
### Milestone 2: Detection Playbook

| Features | F1 (Port Detection), F2 (UFW Status) |
| -------- | ------------------------------------ |
| Branch   | `server-firewall/milestone-2` |

**Deliverables:**

- `playbooks/server-firewall.sh` detect mode

**Steps:**

1. Create playbook skeleton with mode switch
1. Implement `detect_mode()` - call `get_listening_services`, output YAML per SPEC.md
1. Implement `get_ufw_status()` - check installation, parse rules, handle disabled state

**Integration:** Sources `helpers.sh`, called via `PlaybooksTrait::executePlaybook()`

**Verification:**

- [ ] `DEPLOYER_MODE=detect` outputs valid YAML with all required keys
- [ ] Works with UFW disabled or uninstalled

**Enables:** Milestone 3
````

## Rules

- Reference SPEC.md sections, don't duplicate contracts
- Steps start with verb (Create, Add, Extract, Update, Wire)
- Steps name exact functions, files, variables
- Code snippets show signatures/patterns, not full implementations
- Verification manually testable without test suite
- Note integration points explicitly
