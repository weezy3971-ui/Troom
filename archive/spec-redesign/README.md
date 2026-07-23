# Spec redesign — archived

This folder holds the **TROOMs-V1.pdf spec redesign** that was pulled back out of
the live app on 2026-07-23. The app was reverted to its pre-redesign behaviour;
this code is kept for reference only and is **not wired into the application**.

## What the redesign was

The template-driven planting engine from `TROOMs-V1.pdf`:

- **Crop cycle templates** — the reusable planting-to-harvest plan
  (`crop_cycle_templates`), its growth stages (`crop_cycle_stages`) and its
  spray/input schedule (`crop_cycle_schedule_points`).
- **The reminder engine** — `ScheduleReminderService` + the
  `horticulture:send-reminders` command: raised `tasks` for schedule points that
  had come due on each active cycle, notified the assignee, and escalated stale
  tasks.
- **Activity logging** — `planting_cycle_activities`, closing scheduled tasks and
  mirroring their cost into the cycle.
- A **Task** list, a **CropCycleTemplate** admin UI, and the wiring that fed
  cycles from templates.

## What's in this folder

The **net-new source files** the redesign introduced, at their original paths:

- `app/` — the controllers, models, service, command, observer, support class
- `database/migrations/` — the seven `2026_07_23_1000xx_*` migrations
- `database/seeders/SpecDemoSeeder.php`
- `resources/views/crop-cycle-templates/`, `resources/views/tasks/`
- `tests/Feature/ScheduleReminderTest.php`

## The complete redesign (including edits to existing files)

This folder holds only the **new** files. The redesign also modified existing
files (`CropCycle`, `CropCycleController`, routes, `AppServiceProvider`, several
views). The **entire** change — new files, edits, and deletions together — is
preserved in a git tag:

```
git tag                       # checkpoint-before-archive
git show checkpoint-before-archive
git diff 1eb6a25 checkpoint-before-archive   # everything that differs from pre-redesign
```

To bring the whole redesign back into a branch:

```
git checkout -b spec-redesign checkpoint-before-archive
```

## What was deliberately kept

The **merged "New Crop Cycle" screen** (the single-page farm → block → crop →
budget form) was kept, minus its template picker. That was a separate change from
the redesign and the user asked to retain it.
