---
name: docs
description: Use this skill when writing, creating, or modifying documentation or prompts for AI agents. Activates for tasks involving CLAUDE.md, the .claude folder, or any documentation optimized for token efficiency.
---

# AI-Optimized Documentation

Guidelines for writing documentation optimized for AI agents with limited token windows.

All rules MANDATORY.

## Token Efficiency

- Imperative mood, not conversational prose
- One example per pattern maximum
- Remove "Benefits", "Why This Matters" sections
- Inline comments over separate explanations
- Bullets over paragraphs
- Single "All rules MANDATORY" per file
- No repetitive CRITICAL/IMMUTABLE warnings
- No emoji

## Structure

**File Header:**

```markdown
# [Section Name]

All rules MANDATORY.
```

**Organization:**

- Clear, scannable headers
- Related rules grouped
- Alphabetical when no logical grouping
- Max 3 heading levels

## Examples

Show correct first, wrong only when non-obvious:

```php
// CORRECT
$result = $container->build(Service::class);

// WRONG - breaks DI
$result = new Service(new Dependency());
```

**Rules:**

- Under 10 lines per example
- Use `// CORRECT` and `// WRONG` markers
- Inline comments over prose
- Don't explain well-known patterns (AAA, SOLID)
- Don't explain framework features

## Cross-File Coordination

**Single source of truth:**

- One primary location per concept
- Cross-reference by filename: "See architecture.md"
- No line number references

**Valid references:**

```markdown
See @docs/architecture.md
Covered in architecture.md
```

## Writing Style

**Prefer:**

```markdown
Commands handle I/O. Services contain logic. No circular dependencies.
```

**Over:**

```markdown
Commands are responsible for handling all user interaction including input and output operations, while Services provide the core business logic functionality.
```

**Emphasis Hierarchy:**

1. Code examples (most efficient)
2. Imperative bullets
3. Short declarative sentences
4. Tables (reference data only)
5. Prose (last resort)

## Token Budget

- AI agents have 8K-32K context windows
- Rules should consume <20% of tokens
- Leave 80% for code, history, responses
- Target: <3000 tokens total (~600-800 lines)

## Quality Gates

After writing documentation:

```bash
# Check file size (target: <800 lines)
wc -l docs/rules/new-doc.md

# Estimate tokens (~4 chars per token)
wc -c docs/rules/new-doc.md | awk '{print int($1/4)}'
```

## Checklist

Before committing documentation:

- [ ] Remove outdated file references
- [ ] Check for duplication across files
- [ ] Verify no contradictions with other docs
- [ ] Make sure code examples compile/run
- [ ] Validate referenced files or URLs exist
- [ ] Token count under budget (<3000)
- [ ] Uses CORRECT/WRONG markers in examples
