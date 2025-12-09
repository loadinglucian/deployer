---
description: Create implementation plan from PRD, FEATURES, and SPEC
model: opus
allowedTools: ['Read', 'Write', 'Glob', 'AskUserQuestion']
---

**Load skills:** @.claude/skills/command @.claude/skills/playbook @.claude/skills/testing

# Implementation Plan

Create 04-PLAN.md from PRD, FEATURES, and SPEC documents.

## Process

1. Read all three documents from `docs/{feature}/`
1. Extract critical path from 02-FEATURES.md dependency graph
1. Group features into milestones following dependency order
1. Synthesize implementation details from 03-SPEC.md
1. Save to `docs/{feature}/04-PLAN.md`

## Milestone Grouping

- Respect dependencies (no forward references)
- Group features modifying same file/component
- Each milestone independently verifiable
- Playbooks before PHP commands
- Core functionality before CLI options
- 2-4 features per milestone maximum
- **Parallel milestones:** When features have no dependencies between them, use suffix notation (2a, 2b) and annotate with `(parallel with N)`

## 04-PLAN.md Template

```markdown
# Implementation Plan - {Product Name}

## Overview

{1-2 sentence summary}

**Source:** [PRD](./01-PRD.md) | [FEATURES](./02-FEATURES.md) | [SPEC](./03-SPEC.md)

## File Changes

| Type | File     | Purpose       |
| ---- | -------- | ------------- |
| New  | `{path}` | {description} |
| Mod  | `{path}` | {changes}     |

## Prerequisites

**Reference Patterns:**

- `{existing-file}` - {pattern to study}

## Milestones

### Milestone 1: {Name}

| Features | F{n}, F{n} |
| -------- | ---------- |

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

### Milestone 2a: {Name} (parallel with 2b)

| Features | F{n} |
| -------- | ---- |
| Parallel | 2b   |

**Deliverables:**

- {Concrete file or function}

**Steps:**

1. {Verb} {specific task}

**Integration:** {How this connects to existing code}

**Verification:**

- [ ] {Testable criterion}

**Enables:** Milestone 3

---

## Implementation Notes

{Cross-milestone guidance, error handling summary from SPEC}

## Completion Criteria

- [ ] All milestones verified
- [ ] Quality gates pass
- [ ] Manual test against PRD user journeys
```

## Example

```markdown
### Milestone 2: Detection Playbook

| Features | F1 (Port Detection), F2 (UFW Status) |
| -------- | ------------------------------------ |

**Deliverables:**

- `playbooks/server-firewall.sh` detect mode

**Steps:**

1. Create playbook skeleton with mode switch
1. Implement `detect_mode()` - call `get_listening_services`, output YAML per 03-SPEC.md
1. Implement `get_ufw_status()` - check installation, parse rules, handle disabled state

**Integration:** Sources `helpers.sh`, called via `PlaybooksTrait::executePlaybook()`

**Verification:**

- [ ] `DEPLOYER_MODE=detect` outputs valid YAML with all required keys
- [ ] Works with UFW disabled or uninstalled

**Enables:** Milestone 3
```

## Rules

- Reference 03-SPEC.md sections, don't duplicate contracts
- Steps start with verb (Create, Add, Extract, Update, Wire)
- Steps name exact functions, files, variables
- Code snippets show signatures/patterns, not full implementations
- Verification manually testable without test suite
- Note integration points explicitly
- Identify parallel opportunities from 02-FEATURES.md dependency graph
- Milestones with no shared dependencies can be marked parallel
