# AGENTS.md — PDAM Anomaly Detection Research (Security Rules)

> TEMPLATE. Review before adopting. Copy this file to the repository root
> as `AGENTS.md` (or merge its rules into your existing `AGENTS.md`).

## Non-negotiable rules

- **Never read real PDAM datasets.** This includes everything under
  `storage/data/`, `storage/app/`, `uploads/`, `data/`, and `private/`.
- **Never read `.env` files** or any `*.env*` files.
- **Never expose API keys or credentials.** Do not open, log, display,
  paste, or summarize files containing keys/secrets/tokens.
- **Never inspect private uploaded datasets.**
- **Never inspect generated private analysis data** (e.g. result JSON in
  `storage/app/`).
- **Never send sensitive research data to AI providers.** No real data
  enters the model context — ever.
- **Never automatically scan the entire repository.** Inspect only the
  minimum files required for the task.
- **Use minimum necessary context** for every tool call.
- **Use dummy/sample data** whenever a data structure or schema is needed —
  never read the real files.
- **Ask before accessing research Python source code** (`*.py`). Do not
  scan Python files automatically. Only request access when a specific
  Python file is genuinely needed for an explicitly requested task.
- **Do not bypass opencode permission restrictions using terminal
  commands.** Do not use `cat`, `type`, `Get-Content`, `more`, `less`,
  `head`, `tail`, Python/Node scripts, shell redirection, database CLI
  tools, or any equivalent as a workaround to read DENY-listed files.
- **If a task requires sensitive data, STOP and ask the user instead.**

## What IS allowed

- Normal application source code (`*.php`, `*.js`, `*.ts`, `*.tsx`,
  `*.jsx`, `*.css`, `*.html`, templates, config templates, scripts) may be
  read and edited as needed for the task.
- Read only the minimal set of files necessary. Do not open whole
  directories without need.

## When blocked

- If a step needs real research data and is blocked by these rules or by
  opencode permissions, stop and explain. Do not work around the block.
- Ask the user for a safe alternative (sample data, anonymized export,
  or a description of the structure).

## Context

- Project: PDAM Anomaly Detection research (Isolation Forest + Z-Score).
- Research datasets and analysis outputs are confidential.
- This file is the permanent instruction layer that backs up the
  opencode `permission` configuration.
