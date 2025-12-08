---
description: Create technical specification from PRD and features
model: opus
allowedTools: ['Read', 'Write', 'Glob', 'AskUserQuestion']
---

# Technical Specification

Create SPEC.md from PRD and FEATURES documents.

## Process

1. Read PRD.md and FEATURES.md from `docs/{feature}/`
2. Ask clarifying questions (1-2 rounds max)
3. Generate SPEC.md in same directory

## Questions

**Round 1 - Architecture:**

- Ambiguous integration points
- Unclear data flow
- Unspecified technology choices

**Round 2 (if needed):**

- Complex error handling
- State management concerns

## SPEC.md Template

````markdown
# Technical Specification - {Product Name}

## Overview

{1-2 sentence technical approach}

**Components:**

| Component | Type | Purpose |
| --------- | ---- | ------- |

**Architecture:**

```
{ASCII diagram}
```

**Design Decisions:**

- {Decision}: {Rationale}

---

## Feature Specifications

### F{n}: {Feature Name}

| Attribute      | Value             |
| -------------- | ----------------- |
| Source         | FEATURES.md §F{n} |
| Components     | {list}            |
| New Files      | {list or None}    |
| Modified Files | {list or None}    |

**Interface Contract:**

| Method/Function | Input | Output | Errors |
| --------------- | ----- | ------ | ------ |

**Data Structures:**

| Name | Type | Fields | Purpose |
| ---- | ---- | ------ | ------- |

**Playbook Contract:** _(if applicable)_

| Variable | Type | Required | Description |
| -------- | ---- | -------- | ----------- |
| DEPLOYER_{NAME} | string | Yes | {purpose} |

Output:

```yaml
status: success|error
```

**Integration Points:**

- {Class}: {usage}

**Error Taxonomy:**

| Condition | Message | Behavior |
| --------- | ------- | -------- |

**Edge Cases:**

| Scenario | Behavior |
| -------- | -------- |

**Security Constraints:**

- {requirement}

**Verification:**

- {observable outcome}

---
````

## Rules

- Spec every feature from FEATURES.md
- Interface contracts: WHAT not HOW
- Error messages: exact user-facing text
- Integration points: reference existing classes
- Playbook contracts: env vars and YAML output only
- Verification: observable outcomes, not test cases

## Output

Save to `docs/{feature-name}/SPEC.md`
