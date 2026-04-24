# Fase 60/90 Master Continuity Plan

- Short name: fase-60-90-master-continuity

## Status

- State: active
- Folder: `exec-plans/active/`
- Created at: `2026-04-23T22:42:55-06:00`
- Last updated at: `2026-04-23T22:55:45-06:00`
- Canonical handoff doc: this file

## Continuity

- Current owner (person or team): Carlo
- Last updated by: Codex
- Next handoff / waiting on: choose the next focused Phase 60 hotspot before opening code changes

## Goal

- Keep the 30/60/90 roadmap executable across sessions without losing the target state.
- Use this plan as the active handoff for the remaining professionalization work.
- Avoid drifting into endless small extractions by tying each session to a phase, hotspot, closure criteria, and validation evidence.

## Phase Targets

### Phase 30: Stabilize Standards And Contracts

State: substantially complete.

Target state:

- unexpected errors route through `ErrorReporter`
- audit writes route through the standard audit path
- stable logic has focused tests
- schema hardening exists for closed vocabularies and low-risk indexes
- internal contracts are explicit enough to support later extraction

Known evidence:

- `GaticLastRound/2026-04-02T22-36-29-0600_fase-30-corte-seguro-schema-hardening.md`
- `GaticLastRound/2026-04-02T20-35-16-0600_gatic-30-60-90-roadmap.md`

Do not reopen Phase 30 unless a regression shows a transversal rule was missed.

### Phase 60: Extract Complexity From Hotspots

State: active, advanced.

Target state:

- `PendingTaskShow` remains the route/component entrypoint, but line-editor, validation, bulk paste, and process-line behavior live behind focused support classes
- `DashboardMetrics` remains the Livewire coordinator, but read-heavy queries, payload builders, alert computations, inventory value, movement trend, and critical queue pieces are delegated to focused readers/builders
- `TimelineBuilder` and `RecentActivityBuilder` have a deliberate consolidation path, not duplicate event-mapping drift
- `UndoMovementByToken` is split by undo strategy only after current validated fronts are stable
- oversized feature tests are split only where it directly reduces risk for active refactors

Already validated:

- DB-backed checkpoint for Dashboard + PendingTasks completed inside `laravel.test`
- no fixes were required during DB-backed validation

Known evidence:

- `GaticLastRound/2026-04-23T20-16-24-0600_fase-60-checkpoint-dashboard-pendingtasks.md`
- `GaticLastRound/exec-plans/completed/2026-04-23-fase-60-db-backed-verification.md`

Remaining Phase 60 candidates, in recommended order:

1. Close or continue `DashboardMetrics` only with a single named target, likely remaining `critical queue` assembly or another read-heavy block with nearby tests.
2. Consolidate `TimelineBuilder` / `RecentActivityBuilder` event-feed duplication.
3. Split `UndoMovementByToken` by strategy after dashboard/event-feed work is stable.
4. Split oversized tests only when they block or slow the active refactor.

### Phase 90: Formalize Proven Structure

State: not started.

Target state:

- introduce targeted authorization refinement only where current gates are too coarse
- split routes/domain boundaries only where Phase 60 boundaries proved stable
- standardize repeated Livewire interaction patterns only after at least two stable use cases justify it
- add CI guardrails for structural drift after conventions are mature
- document domain conventions for PendingTasks, Inventory, Dashboard, Attachments, and Settings

Do not start Phase 90 until:

- Phase 60 hotspots have a green focalized validation baseline
- the next structure being formalized already exists in working code
- the change enforces proven practice rather than inventing architecture ahead of need

## Closure Criteria

This master plan can close when:

- Phase 60 remaining hotspots are either completed or explicitly deferred with rationale
- Phase 90 entry criteria are met or the project decides to stop at a stable Phase 60 baseline
- `GaticLastRound/README.md` points clearly to the current canonical roadmap and active exec plan
- any completed exec plans are moved to `exec-plans/completed/`

## Scope

- In scope:
  - planning and continuity for the remaining 30/60/90 work
  - choosing one focused hotspot per session
  - recording validation commands and results
  - creating child exec plans for substantial sub-efforts
- Out of scope:
  - broad rewrites
  - cosmetic modularization
  - enterprise permission DSLs
  - route reshuffles before stable ownership boundaries exist
  - touching Blade / JS unless a focused plan explicitly requires it

## Base Context / Sources Read

- `AGENTS.md`: source-of-truth order and project rules
- `GaticLastRound/2026-04-02T20-35-16-0600_gatic-30-60-90-roadmap.md`: canonical 30/60/90 roadmap
- `GaticLastRound/2026-04-23T20-16-24-0600_fase-60-checkpoint-dashboard-pendingtasks.md`: Phase 60 checkpoint evidence
- `GaticLastRound/exec-plans/completed/2026-04-23-fase-60-db-backed-verification.md`: completed DB-backed validation evidence

## Preconditions / Assumptions

- Docker/Sail should be available for DB-backed validation before and after touching DB-heavy flows.
- Worktree may remain dirty; do not revert unrelated changes.
- Each future session should execute only the current `Next Step` unless the user redirects.

## Execution Sequence

1. Pick one Phase 60 target from the recommended candidates.
2. Create a child exec plan if the target is expected to span sessions.
3. Run preflight:
   - `.\bin\gatic-doctor.cmd`
   - `.\bin\gatic-status.cmd`
4. Inspect current code and tests for the target.
5. Make the smallest behavior-preserving change that advances the target.
6. Validate with focused tests and DB-backed tests when relevant.
7. Update the child plan or this master plan with commands, result, and next step.

## Decisions

- `PendingTaskShow` and `DashboardMetrics` are not closed forever, but they should not be reopened without a precise target and nearby validation.
- Phase 90 is explicitly blocked until remaining Phase 60 structure is either stable or deliberately deferred.
- The completed DB-backed verification plan is reference evidence, not the ongoing plan.
- `2026-04-23T22:49:25-06:00`: next Phase 60 target is Dashboard `critical queue` assembly. Rationale: `DashboardMetrics` still owns the ranked queue queries/row assembly while nearby DB-backed smoke coverage exists; this is a cleaner bounded extraction than starting the broader `TimelineBuilder` / `RecentActivityBuilder` consolidation in this session.

## Risks / Blockers

- Risk: continuing micro-extractions after the hotspot is already good enough.
- Risk: starting authorization/route formalization before Phase 60 boundaries are stable.
- Risk: dirty worktree can obscure unrelated changes; always scope diffs before edits.
- Blocker: Docker unavailable when a DB-backed flow needs validation.

## Validation

Use validation proportional to the target:

- narrow pure support class:
  - `php -l <files>`
  - `./vendor/bin/pint --test <files>`
  - focused unit tests
- DB-backed dashboard or pending task flow:
  - `docker compose -f compose.yaml up -d`
  - focused `php artisan test ... --stop-on-failure` inside `laravel.test`
- cross-cutting or checkpoint closure:
  - relevant feature tests plus a short checkpoint update in `GaticLastRound`

## Session Log

### `2026-04-23T22:55:45-06:00` — Phase 60 Dashboard Critical Queue Extraction

- Objective chosen: extract Dashboard `critical queue` assembly into a focused support builder.
- Why this target: `DashboardMetrics` still owned the ranked queue query/row assembly; existing DB-backed smoke tests made this a bounded, high-return extraction. `TimelineBuilder` / `RecentActivityBuilder` remains the next recommended front, but it is broader and should start with a child plan if pursued next.
- Files touched:
  - `gatic/app/Livewire/Dashboard/DashboardMetrics.php`
  - `gatic/app/Support/Dashboard/CriticalQueuePreviewItemsBuilder.php`
  - `gatic/tests/Feature/Dashboard/CriticalQueuePreviewItemsBuilderTest.php`
  - `GaticLastRound/exec-plans/active/2026-04-23-fase-60-90-master-continuity.md`
- Commands executed:
  - `.\bin\gatic-doctor.cmd` — PASS
  - `.\bin\gatic-status.cmd` — repo has tracked/untracked changes
  - `php -l gatic\app\Support\Dashboard\CriticalQueuePreviewItemsBuilder.php` — PASS
  - `php -l gatic\app\Livewire\Dashboard\DashboardMetrics.php` — PASS
  - `php -l gatic\tests\Feature\Dashboard\CriticalQueuePreviewItemsBuilderTest.php` — PASS
  - `docker compose -f compose.yaml up -d` — containers already running
  - `docker compose -f compose.yaml exec -T laravel.test ./vendor/bin/pint --test app/Support/Dashboard/CriticalQueuePreviewItemsBuilder.php app/Livewire/Dashboard/DashboardMetrics.php tests/Feature/Dashboard/CriticalQueuePreviewItemsBuilderTest.php` — PASS
  - `docker compose -f compose.yaml exec -T laravel.test php artisan test tests/Feature/Dashboard/CriticalQueuePreviewItemsBuilderTest.php tests/Feature/Dashboard/DashboardMetricsCriticalQueueSmokeTest.php --stop-on-failure` — PASS, 2 tests / 2 assertions
  - `docker compose -f compose.yaml exec -T laravel.test php -l app/Support/Dashboard/CriticalQueuePreviewItemsBuilder.php` — PASS
  - `docker compose -f compose.yaml exec -T laravel.test php -l app/Livewire/Dashboard/DashboardMetrics.php` — PASS
  - `docker compose -f compose.yaml exec -T laravel.test php -l tests/Feature/Dashboard/CriticalQueuePreviewItemsBuilderTest.php` — PASS
  - `docker compose -f compose.yaml exec -T laravel.test php artisan test tests/Unit/Dashboard/CriticalQueuePreviewBuilderTest.php tests/Unit/Dashboard/CriticalQueueAssetRowMapperTest.php --stop-on-failure` — PASS, 4 tests / 6 assertions
  - `docker compose -f compose.yaml exec -T laravel.test php artisan test tests/Feature/Dashboard --stop-on-failure` — PASS, 6 tests / 16 assertions
- Result: `DashboardMetrics` now delegates critical queue preview construction to `CriticalQueuePreviewItemsBuilder`; visible queue shape/ranking is preserved by existing and new DB-backed tests.
- Next step: consider Dashboard `critical queue` sufficiently advanced for now; if continuing Phase 60, open a child exec plan for deliberate `TimelineBuilder` / `RecentActivityBuilder` consolidation before touching that broader event-feed duplication.

## Key Artifacts

- `GaticLastRound/README.md`
- `GaticLastRound/2026-04-02T20-35-16-0600_gatic-30-60-90-roadmap.md`
- `GaticLastRound/2026-04-23T20-16-24-0600_fase-60-checkpoint-dashboard-pendingtasks.md`
- `GaticLastRound/exec-plans/completed/2026-04-23-fase-60-db-backed-verification.md`

## Next Step

- Open a child exec plan for `TimelineBuilder` / `RecentActivityBuilder` consolidation if Phase 60 continues.

## Closure Summary

- Fill when closing this master plan.
