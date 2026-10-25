---
name: alex-kassel-site-cloner
origin: alex-kassel/site-cloner
version: 0.0.1
status: draft
description: >-
  TODO: Operational agent skill for alex-kassel/site-cloner package.
---

# alex-kassel-site-cloner Skill

> [!NOTE]
> This skill is currently in **draft** status.
> Fill in instructions and workflows for AI agents, then change `status: draft` to `status: published` to activate publishing.

---

## Operational Workflow

### Phase 0: Tooling Verification & Bootstrapping
Before performing actions with this package:
1. Verify if the package service or commands are available:
   ```bash
   composer show alex-kassel/site-cloner
   ```
2. **If installed**: Proceed to next phase.
3. **If missing**:
   - Check your environment execution policy:
     - If authorized to install dependencies autonomously:
       ```bash
       composer require alex-kassel/site-cloner
       ```
     - Otherwise, request human confirmation before modifying dependencies:
       *"The package [alex-kassel/site-cloner] is required for this operation. May I install it via composer require?"*
