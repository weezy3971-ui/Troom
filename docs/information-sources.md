# Where planting-plan information comes from, and how it is pulled

Written for the 23 July 2026 review of the planting-plan feature. It answers the
question put to the developer — *which sources feed the planner, and how is the
information pulled from them* — and records what changed as a result.

## Short answer

**Nothing is scraped.** No part of the system fetches, crawls or parses any of
the agronomy websites. The figures in the planting planner are typed into the
codebase by hand, with a citation attached to each crop saying which published
guide the numbers were taken from.

The two websites listed in the meeting are therefore *references*, not *feeds*.
The risk they carry is not "a scraper might pull in something funny" — it is
"someone transcribed a number wrong, or the published guide itself is wrong, and
a farmer acted on it."

## The actual data path

```
app/Support/PlannerPrograms.php   ← hand-curated PHP array, one program per crop
        │                            (spacing, fertiliser rates, phase offsets,
        │                             agronomy notes, and a `sources` list)
        ▼
app/Support/CuratedSources::vet() ← drops citations an admin has removed
        │
        ▼
CropCycleController::planner()    ← passes the vetted programs to the view
        │
        ▼
resources/views/crop-cycles/planner.blade.php
                                  ← re-dates every phase client-side from the
                                    planting date; prints to PDF
```

Every date on the sheet is arithmetic on a day-offset from Day 0 (sowing for
direct-sown crops, transplant for nursery-raised ones). No network call is
involved in producing a planting plan, and the planner works offline.

Crops currently covered: French Bean, Capsicum, Collard Greens (sukuma wiki),
Spinach, African Nightshade.

## Every outbound network call in the system

Audited by searching the whole application for HTTP clients. There are exactly
two, and neither touches an agronomy site:

| Destination | Where | What for |
|---|---|---|
| `api.anthropic.com` | `app/Services/Ai/AiClient.php` | Generating executive reports and KPI narratives |
| Bonga SMS | `app/Services/SmsService.php` | Texting a user their password when an admin sets or resets it |

The AI reports are worth naming explicitly, because an LLM *is* a source of
information. It is constrained to internal data only: the system prompt in
`app/Jobs/GenerateAiReport.php` instructs it to write "based ONLY on the data
provided" and to never invent figures, and the data it is given is the farm's
own records assembled by `FarmContextAssembler`. It is not asked for agronomy
advice and does not cite outside websites. If that ever changes, the AI becomes
an information source in the sense this review cares about, and it should be
declared in the register like any other.

## The declared sources

Now held in the database and managed at **Administration → Source of
Information**, categorised by what each is used for:

| Source | Used for | Cited by |
|---|---|---|
| Greenlife Kenya | Crop cycle & planting plans | Capsicum, collard greens, spinach, French bean |
| Infonet-Biovision | Agronomy, pests & diseases | French bean, collard greens, spinach, African nightshade |
| Simlaw Seeds | Seed & variety data | French bean |
| Cropnuts | Soil & laboratory testing | French bean |
| KEPHIS | Compliance, export & residue limits | French bean |
| GLOBALG.A.P. | Compliance, export & residue limits | Named in checklists |

"KBS" in the meeting was KEPHIS — the Kenya Plant Health Inspectorate Service.

## What changed after the review

1. **The sources are visible.** Administration → **Sources** lists every website
   the system quotes, one row per publisher, grouped by what it's used for. The
   list syncs itself from the citations in the code each time it's opened, so a
   source added to a crop program appears without anyone remembering to declare
   it. Adding, editing and removing are admin actions and go to the audit log.

2. **Deleting a source reaches the plan, not just the credit.** The figures on
   a planting plan came FROM its sources, so dropping the citation while leaving
   the numbers would produce a sheet that looks authoritative and is backed by
   nothing. `CuratedSources::vet()` therefore applies a deletion three ways:

   - citations from the deleted source are dropped;
   - a crop plan that loses **some** of its sources is marked unverified, which
     raises the printed "confirm with your agronomist" banner over its figures;
   - a crop plan that loses **all** of them is withdrawn from the planner, and
     the planner reports itself unavailable if no sourced plan is left.

   The admin is told which plans are affected before confirming, and again in
   the confirmation message afterwards.

   A deleted source the code still cites is kept as a deleted row rather than
   erased — otherwise the automatic sync would put it straight back on the next
   page load. Those rows are listed under "Deleted" and can be restored, which
   brings the affected plans back with them. A source added by hand and cited
   nowhere is erased outright.

   There is deliberately **no edit**. A source's name and description come from
   the code, and editing them changed nothing a farmer sees, so the screen
   offers only what has an effect: open it, or delete it.

3. **A mis-citation was fixed.** The French bean program credited "KEPHIS /
   Infonet-Biovision" for export and residue (MRL) requirements but linked to
   `infonet-biovision.org` — attributing regulatory limits to a source that is
   not the regulator. It now points at `kephis.go.ke`.

## Open items for the next review

- **Nobody has checked the sites yet.** The list makes every source one click
  away, but reading them and confirming the figures still matches what we
  publish is a human job that hasn't been done.
- **Figures are unversioned.** We cite a page, not a snapshot of it. If
  Greenlife revises a fertiliser rate, our copy silently becomes stale. Worth
  recording a "figures checked on" date per crop program.
- **`verified => true` is asserted, not evidenced.** Every program in
  `PlannerPrograms.php` is flagged verified, but nothing records who verified it
  or when. That flag decides whether a farmer sees the "confirm with your
  agronomist" banner, so it should be backed by the same review trail as the
  sources are now.
- The 06:43 clip of the meeting is still untranscribed and may contain further
  instructions.
