---
description: Implement a feature from its PLAN.md
model: opus
---

# Feature Implementation

Autonomously implement a feature from PRD, FEATURES, SPEC, and PLAN documents.

## Process

1. Read all documents from `docs/{feature}/`
1. Execute milestones from PLAN.md in order
1. Fix issues encountered (don't halt)
1. Produce final summary

## Execution Model

```
For each milestone:
  Announce: "--- Milestone N: {name} ---"
  For each step:
    Show: "> Step N: {description}"
    Implement step
    If PHP/playbook changed: run quality-gatekeeper, fix until passing
    If verification fails: diagnose and fix
    Show: "Done: Step N"
  Run milestone verification
  Show: "Done: Milestone N"

Final: Summary of files created/modified
```

## Self-Healing

Never halt. Fix and continue.

| Failure Type | Response |
| ------------ | -------- |
| Quality gate | Read error, fix code, re-run until passing |
| Verification | Check SPEC.md for expected behavior, fix implementation, re-verify |
| Missing dependency | Check FEATURES.md graph, implement missing piece, continue |
| Ambiguous requirement | Consult SPEC.md/PRD.md, match codebase patterns, add comment if non-obvious |

## Progress Format

```
--- Milestone 1: Shared Helper ---

> Step 1: Extract get_listening_services() from server-info.sh
  Creating function in helpers.sh...
Done: Step 1

> Verification: server:info produces unchanged output
Done: Milestone 1
```

## Error Recovery Example

```
> Step 3: Add validatePorts method
  Running quality gates...
  X PHPStan: Parameter $ports has no type hint
  Fixing type hint...
  Running quality gates...
Done: Step 3
```

## Final Summary Format

```
--- Implementation Complete ---

Files created:
- path/to/new/file.php

Files modified:
- path/to/existing/file.php

Milestones completed: N/N

Ready for manual testing against PRD user journeys.
```

## Rules

- Prefer Edit over Write for existing files
- Read files before modifying
- Run quality-gatekeeper after PHP/playbook changes
- Execute verification criteria literally
- No commits (user decides)
- No halting (fix and continue)
- No skipping (complete every step)
- Verbose output (show progress)
