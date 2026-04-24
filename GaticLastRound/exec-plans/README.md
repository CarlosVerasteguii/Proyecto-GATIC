# Exec Plans (GATIC)

This folder provides a lightweight convention for multi-session work continuity in `C:\dev\Proyecto-GATIC`.

If work spans more than one session, involves significant decisions/risks, or needs reproducible validation, the open exec plan becomes the canonical handoff document.

## Canonical Rule

- Source of truth for an open multi-session effort:
  - `GaticLastRound/exec-plans/active/<PLAN>.md`
- The package index remains:
  - `GaticLastRound/README.md`

Do not keep a parallel timeline in chat. Update the exec plan:

- Scope
- Decisions / Risks
- Validation
- Key Artifacts (real paths)
- Next Step (executable)

## Active Plans

- `GaticLastRound/exec-plans/active/2026-04-23-fase-60-90-master-continuity.md`

## Completed Plans

- `GaticLastRound/exec-plans/completed/2026-04-23-fase-60-db-backed-verification.md`

## Lifecycle

- `active/`: open work (state `active` or `blocked`)
- `completed/`: closed work (state `completed` or `abandoned`)

## When To Create One

Create an exec plan when:

- the task will continue in another session;
- the work crosses major areas (PendingTasks + Dashboard, schema + UI, etc.);
- the work needs evidence, commands, or artifacts for repeatable validation;
- the work involves risk or a decision that should survive chat context.

## Template

Use:

- `GaticLastRound/exec-plans/templates/exec-plan.template.md`

## Wrappers (Recommended)

From repo root:

```powershell
.\bin\gatic-doctor.cmd
.\bin\gatic-status.cmd
.\bin\gatic-artisan.cmd --version
```

## Base Prompt (New Codex Session)

Copy/paste and replace `<PLAN>.md`:

```text
You are in `C:\dev\Proyecto-GATIC`.

Source of truth: `GaticLastRound/exec-plans/active/<PLAN>.md`

Rules:
- Read the plan first.
- Execute the current **Next Step** only.
- Keep changes within scope.
- Record decisions, validation commands, and key artifacts in the plan.

Preflight:
- Run: `.\bin\gatic-doctor.cmd`
- Run: `.\bin\gatic-status.cmd`
```
