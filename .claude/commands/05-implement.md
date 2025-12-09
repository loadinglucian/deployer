---
description: Implement code from implementation plan
model: opus
allowedTools: ['*']
---

**Load skills:** @.claude/skills/command @.claude/skills/playbook @.claude/skills/testing

# Plan Implementation

Execute implementation plan milestones to produce working code.

## Process

1. Read all documents from `docs/{feature}/`: 01-PRD.md, 02-FEATURES.md, 03-SPEC.md, 04-PLAN.md
2. Create `05-IMPLEMENTATION.md` tracking document
3. Present milestone summary to user
4. Execute milestones sequentially (or parallel where marked)
5. Run quality gates after each milestone
6. Update `05-IMPLEMENTATION.md` after each milestone

## 05-IMPLEMENTATION.md Template

```markdown
# Implementation - {Product Name}

**Source:** [PRD](./01-PRD.md) | [FEATURES](./02-FEATURES.md) | [SPEC](./03-SPEC.md) | [PLAN](./04-PLAN.md)

**Status:** In Progress | Complete

## Progress

| Milestone | Status | Files Changed |
| --------- | ------ | ------------- |
| 1: {Name} | Done   | {count}       |
| 2: {Name} | Active | {count}       |
| 3: {Name} | -      | -             |

## Milestone Log

### Milestone 1: {Name}

**Status:** Complete

**Files:**

| Type | File     | Changes            |
| ---- | -------- | ------------------ |
| New  | `{path}` | {brief description}|
| Mod  | `{path}` | {brief description}|

**Verification:**

- [x] {criterion from plan}
- [x] {criterion from plan}

**Quality Gates:** Passed

---

### Milestone 2: {Name}

**Status:** In Progress

...

## Summary

**Files Created:**

- `{path}` - {purpose}

**Files Modified:**

- `{path}` - {changes}

**Manual Testing Required:**

- [ ] {test from plan completion criteria}

**Notes:**

- {any deviations from plan}
- {open questions}
```

## Execution Flow

**For each milestone:**

1. Update `05-IMPLEMENTATION.md` status to "Active"
2. Display milestone name and deliverables
3. Read reference patterns listed in Prerequisites
4. Execute steps in order, following SPEC contracts exactly
5. Run quality-gatekeeper agent on changed files
6. Verify completion criteria
7. Update `05-IMPLEMENTATION.md` with results
8. Mark milestone "Done" before proceeding

**Parallel milestones:** When milestones are marked parallel (e.g., 5a, 5b), execute them sequentially but note they have no interdependencies.

## Implementation Rules

**Follow the SPEC:**

- Interface contracts define WHAT, implement the HOW
- Error messages must match SPEC exactly
- Data structures must match SPEC definitions
- Use existing patterns from reference files

**Code Quality:**

- Follow all CLAUDE.md rules
- Load and apply relevant skills (command, playbook, testing)
- Run quality gates after each milestone
- Fix any issues before proceeding

**No Shortcuts:**

- Implement every feature listed in milestone
- Don't skip edge cases or error handling
- Verify all acceptance criteria from 02-FEATURES.md

## Error Handling

If a milestone fails:

1. Update `05-IMPLEMENTATION.md` with failure details
2. Report the failure clearly
3. Identify the blocking issue
4. Ask user how to proceed (fix, skip, abort)

If quality gates fail:

1. Fix automatically where possible
2. Report issues that need user decision
3. Log resolution in `05-IMPLEMENTATION.md`

## Completion

When all milestones complete:

1. Update `05-IMPLEMENTATION.md` status to "Complete"
2. Fill in Summary section with all files
3. List manual testing required from plan
4. Note any deviations or open questions

Save to `docs/{feature}/05-IMPLEMENTATION.md`
