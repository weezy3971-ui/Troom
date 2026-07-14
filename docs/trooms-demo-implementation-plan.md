# Trooms Demo — Full Implementation Plan (Meeting Feedback)

**Status:** Proposal · **Date:** 2026-07-14 · **Author:** Brian Kamau
**Source:** `Trooms Demo.m4a` (~72 min, transcribed locally with faster-whisper) **+** 16 written meeting notes.

> **Companion doc:** the crop/revenue **projection** work (note 1) is specced separately in
> [french-beans-yield-projection-plan.md](french-beans-yield-projection-plan.md) and only summarised here (Theme 1) to avoid duplication.

---

## How to read this doc — context markers

The request came from two sources that don't fully overlap. Every item below is tagged so you can see *where it came from* and *how firm it is*:

| Marker | Meaning |
|--------|---------|
| 🎙️ **Recording** | Discussed on the call; timestamped quote given. |
| 📝 **Note only** | In the written notes but **not** in the recording — lower certainty, confirm intent before building. |
| ✅ **Both** | Appears in recording *and* notes — highest confidence. |
| ⛔ **Deprioritised / shot down** | Raised then parked or rejected on the call — **do not build now.** |
| ⚪ **Context, not a feature** | Background discussion that informs design but isn't a work item. |

The transcript is rough in places (accents, jargon, ~7 min of inaudible tail from 64:00). Figures are approximate and flagged; genuine ambiguities live in §Open Questions, not guessed at.

---

## 0. Explicitly out of scope for this round

| Item | Why | Source |
|------|-----|--------|
| ⛔ **GPS / geo-coordinates on blocks & plantings** | Raised, then parked: *"now the GPS… it's on very low [priority]… I can just save this for now."* (11:52–12:48). `Block.boundary_geojson` and `DailyActivity.gps_location` columns already exist unused — leave as-is. | 🎙️ |
| ⚪ **Child-labour / PAYE remarks** | The team stated they do **not** employ children and won't model "tax jokes" (26:15–26:51). This is a compliance stance, **not** a feature. | 🎙️ |
| ⚪ **The "$5 AI companion"** | Different meeting; already covered by [ai-companion-implementation-plan.md](ai-companion-implementation-plan.md). | — |
| 📝 **Mobile-money disbursement** (part of note 4) | **Not discussed on the call.** Paying workers to a phone number is a sensitive financial integration — see Theme 4.5 for the caution and why it should be scoped on its own. | 📝 |

---

## Theme 1 — Crop & revenue projection  ✅ *(note 1)*

**Asked:** *"Your system should be able to do projection… planted X seeds, projected revenue."* (06:43) plus *"farmers to provide marker [market] checkers"* — field staff walk sample beds and feed the numbers in (11:51, *"we just get the market checkers"*).

**Status:** fully specced in the companion doc. Covers bed/seed planting detail, germination tracking, plant-population (stand) counts, the projection engine (yield **and** revenue), pre-harvest sampling forecast, and projected-vs-actual variance.

➡️ **See [french-beans-yield-projection-plan.md](french-beans-yield-projection-plan.md).** Note 1's "both crops and revenue" is satisfied by the crop-agnostic `YieldProjectionService` + `reference_price_per_kg` there.

---

## Theme 2 — Crop stage programs + stage-arrival notifications (AI-assisted)  ✅ *(note 2)*

**Asked:** 🎙️ *"When we enter a plant [we define] different stages — week 1 irrigation, fertigation, pest & disease, in what order… and notify when a stage arrives."* (note 2) The recording backs the cadence: *"after [germination] we do like 6 to 7 sprays — every week you do a spray… feeding is like 3 times, second week, fourth week."* (16:12–16:58). *"Integrate AI for different plants"* → the schedule differs per crop.

**Current state:** `DailyActivity` already has a controlled activity list (`land_preparation, planting, weeding, scouting, spraying, fertigation, pruning, harvesting`) and `SprayLog`/`FertigationLog`/`IrrigationLog` exist — but they are *recorded after the fact*. There is **no forward schedule** and no reminders. `AlertService` ([app/Services/AlertService.php](../app/Services/AlertService.php)) is the existing proactive-alert mechanism to hang notifications off.

**Proposed:**
- **`CropProgram`** (template) + **`CropProgramStage`** rows: per crop, an ordered list of stages with `offset_days` (from planting), `activity_type`, cadence (e.g. "weekly ×7"), and default inputs. This is the reusable protocol.
- On cycle activation, **materialise a schedule**: generate `CropCycleStage` rows (due dates from `planting_date + offset`). Show as a timeline on the crop-cycle page; tick off when the matching log is recorded.
- **Stage-due notifications** via `AlertService` (a new `stage_due` alert type) surfaced in the existing notifications UI; SMS is a later add-on (Theme 4.5 infra).
- **AI assist (optional, phase 2):** reuse the AI foundation from the companion plan to *draft* a crop program for a new crop from its agronomy, which a supervisor edits and saves. Grounded/editable, never auto-applied.

**Effort:** medium (templates + scheduler) + small (alert wiring).

---

## Theme 3 — Input/fertiliser procurement proactivity  ✅ *(note 3)*

**Asked:** 📝 *"Fertilisers — proactivity to see if items were bought or not and record as inventory."* 🎙️ The call showed logging inputs against an activity and watching the budget draw down (36:53–37:39), but there's no "was this actually purchased?" step.

**Current state:** `InventoryTransaction` records `receipt`/`issue`/`adjustment`; there is **no purchase-order / procurement request** concept, so "planned but not yet bought" can't be represented.

**Proposed:**
- **`ProcurementRequest`** (+ lines): what a program stage / task *needs* (item, qty, needed-by), with status `requested → ordered → received`. On `received`, auto-create the matching inventory `receipt` transaction (closing note 3's "record as inventory" loop).
- **Proactive alert:** a stage is due but its required inputs aren't in stock and haven't been received → `input_not_procured` alert (reuse `AlertService` + `InventoryItem::isLowStock()` pattern).

**Effort:** medium.

---

## Theme 4 — Labour management  ✅/📝 *(notes 4–9)*

The single biggest cluster. The recording is emphatic that **casual labour must be target/piece-rate, not hourly**: *"we prefer giving them targets… when you are done with 5 beds or 10 beds, that's a target"* (32:53–33:01, 34:35), because *"they drag a lot when it's hourly"* (note 9). Permanent staff are time-based (*"staying until five… casuals until four"*, 29:14–29:23).

**Current state:** labour is split across two models today — `LabourAttendance` (name + hours + rate + cost, no worker link, no targets) and `TaskAssignment` (worker + hours + rate + cost). `Worker` has only `name, phone, default_rate, is_active` — no type, no ID, no attendance. **No piece-rate, no check-in/out, no casual-vs-permanent split.**

### 4.1 Worker types & records  ✅ *(notes 7, 8)*
Add `worker_type` (`casual | permanent`), `national_id`, `employee_no` to `Worker`. Enables note 8's **casual-vs-in-house analysis** (group cost/output by type) and note 7's **hours-logged-with-rate** per worker.

### 4.2 Payment models: hourly **and** target/piece-rate  ✅ *(notes 5, 9)*
Extend the labour/assignment record with `pay_basis` (`hourly | target`), and for target: `target_unit` (beds/crates/kg), `target_qty`, `rate_per_unit`, `qty_completed` → `cost = qty_completed × rate_per_unit`. The recording's "5 beds = 600 KES" maps directly. Keep hourly for permanent staff.

### 4.3 Attendance check-in / check-out  ✅ *(note 6)*
A `WorkerAttendance` (check-in/out timestamps per worker per day), mirroring the **already-built `AssetCheckout`** pattern ([app/Models/AssetCheckout.php](../app/Models/AssetCheckout.php)) — same in/out/overdue shape, applied to people. Note 6 literally says *"also check in checkouts on employees."* Permanent-staff hours derive from this; casuals check in but are paid on target.

### 4.4 Worker ID / biometrics  📝 *(note 4, partial)*
`national_id` (4.1) covers the ID number. **Biometric capture is note-only, not on the recording** — treat as a later enhancement (device integration, consent, storage of biometric templates has legal weight). Ship ID-number + employee-no first.

### 4.5 ⚠️ Mobile-money pay-out  📝 *(note 4, partial) — scope separately*
*"Same phone number receives money"* is **notes-only and financial.** Disbursing wages to an MSISDN (e.g. M-Pesa B2C) is a regulated integration with its own credentials, reconciliation, and audit needs. **Recommend a dedicated plan/decision before any build** — this plan only reserves `Worker.pay_phone` and a `payout_ref` on the payment record so the data model is ready. No credential handling is designed here.

**Effort:** 4.1–4.3 medium (core); 4.4 small (ID only); 4.5 deferred.

---

## Theme 5 — Projects vs field operations  ✅ *(notes 10, 16)*

**Resolved on the call.** The open question *"are projects things done just once, and the rest recurring?"* (39:43) was answered: **Projects = one-off** (construction, land refining, training — 40:27–40:46); **field operations = recurring** (*"you'll always be planting… six months we plant the same block"*, 40:50–41:06; note 16: production runs in **6-month** cycles).

**Current state:** `Project`/`ProjectTask`/`TaskAssignment` already model one-off work with budget + labour + input costing. Recurring field ops live in `DailyActivity` + the crop-cycle costing chain.

**Proposed (mostly clarity, not new tables):**
- Keep `Project` for **non-recurring** work; add a `project_type`/`is_recurring=false` guard and UI copy so users don't file weeding as a "project".
- Ensure the **recurring field-operation** flow (per-activity input + labour cost projection the call liked at 39:08–39:20) is first-class on the crop cycle, not forced through Projects.
- Represent the **6-month production cycle** as the crop-cycle horizon (note 16) — surface it in projection windows.

**Effort:** small (guards + copy); the costing engine already exists.

---

## Theme 6 — Inventory: pre-harvest vs post-harvest separation  ✅ *(note 11)*

**Asked:** 🎙️ *"There can be inventory for the other side… we can separate the two"* (44:35–44:40) — pre-harvest inputs (fertiliser, chemicals, 20 L cans) vs post-harvest packaging (crates, 4 kg cartons). Also flagged: some items are mis-filed as **assets** that should be **inventory** (43:30–43:47).

**Current state:** `InventoryItem` **already has a `category` field** ([app/Models/InventoryItem.php](../app/Models/InventoryItem.php)) — but it's free-form and unused for grouping.

**Proposed:**
- Introduce a controlled **`category` enum / `stage` field** (`pre_harvest_input | post_harvest_packaging | general`), filter inventory views by it, and report stock per stage.
- Data cleanup: reclassify packaging currently sitting under assets into post-harvest inventory (a seeder/migration housekeeping task).

**Effort:** small.

---

## Theme 7 — Harvest batch enhancements  ✅ *(notes 12, 13)*

**Asked:** 🎙️ *"We can also confirm — confirmed by who actually confirmed"* (45:26) and proper weighing (*"this is 150 kilos"*, 46:56); by-products (*"you know it's a by-product"*, 44:48; note 12 "mofefe"). 

**Current state:** `HarvestBatch` has `quantity_kg, quality_grade, rejects_kg, harvested_by` — **no confirmer, no by-product.**

**Proposed:**
- Add `confirmed_by` (+ `confirmed_at`) to `HarvestBatch` — a second person verifies the weight (note 13). Optionally gate packing on confirmation.
- Add **by-product capture**: either `by_product_kg` + `by_product_name` on the batch, or a small `HarvestByProduct` child table if multiple by-products per batch (note 12, "mofefe"). Recommend the child table for flexibility.
- "Proper weighing" = make `quantity_kg` a confirmed, audited figure (record who weighed vs who confirmed).

**Effort:** small.

---

## Theme 8 — Customer orders: rejects, returns & outgrowers  ✅ *(notes 14, 15)*

**Asked:** 🎙️ *"Out of 1000 kilos… we need 950, then 50 you'll reject"* (61:37–61:53) and monitoring order profit/rejects over time (61:20–62:20); buyers *"give us a report"* on rejects (52:23). Note 15 + 🎙️ *"in case there is no lot, then you can't [fulfil]"* (64:00) → pull from an **outgrower** to top up an order.

**Current state:** `SalesOrder` has `requested_quantity`, allocated lines from packhouse lots, and `isAtRisk()` — but **no reject/return tracking** and **no outgrower** concept.

**Proposed:**
- **Rejects/returns (note 14):** add `delivered_quantity`, `rejected_quantity`, `returned_quantity`, `amount_repaid` to the order (or a `SalesOrderReturn` child). Surface a per-customer reject-rate and repaid total — the "monitor orders if rejects and how much returned/repaid" report.
- **Outgrowers (note 15):** a lightweight `Outgrower` supplier + an `outgrower` source option on a sales-order line so an order can be topped up from external produce when in-house lots fall short (ties to `isAtRisk()`).

**Effort:** rejects small; outgrowers medium.

---

## Theme 9 — Cross-cutting: notifications & guidance  🎙️

- **Notifications channel:** Themes 2 & 3 need proactive nudges. Reuse `AlertService`; add new alert types. **No SMS provider is configured** (`config/services.php` has none) — in-app first, SMS is an infra add-on shared with Theme 4.5.
- **In-app guide tab** 🎙️ (*"we'll add a tab there with your guide… anytime you feel lost"*, 59:27): the `Guide` model already exists — attach a contextual reference panel to the post-harvest/crop pages. Small, nice-to-have.

---

## Phased delivery roadmap

Sequenced by dependency and confidence (✅ before 📝). Each phase is independently shippable.

| Phase | Contents | Confidence | Size |
|-------|----------|-----------|------|
| **P1** | Theme 1 projection (companion doc) · Theme 7 harvest (confirmed_by + by-products) · Theme 6 inventory categories | ✅ high | S–M |
| **P2** | Theme 4.1–4.3 labour (worker types, target/piece-rate pay, attendance check-in/out) | ✅ high | M |
| **P3** | Theme 2 crop-stage programs + stage-due notifications (AlertService) | ✅ high | M |
| **P4** | Theme 3 procurement proactivity · Theme 8 rejects/returns | ✅ high | M |
| **P5** | Theme 8 outgrowers · Theme 5 project/field-ops guards · Theme 9 guide tab | ✅ med | S–M |
| **P6** | Theme 2 AI-drafted programs · Theme 4.4 biometrics | 📝 lower | M |
| **Deferred** | Theme 4.5 mobile-money pay-out (own plan) · GPS (⛔ parked) | — | — |

---

## Open questions (decide before the relevant phase)

1. **Theme 2 — program granularity:** are stage programs defined **per crop** (one template) or **per crop cycle** (edited each season)? Recommend per-crop template → materialised per cycle (editable).
2. **Theme 4.2 — target units:** which units do casuals actually get paid by — beds, crates, kg, lines? Drives `target_unit`.
3. **Theme 4.5 — mobile money:** is pay-out in scope at all, and via which provider (M-Pesa B2C?)? **Blocking** for any build; needs its own security review.
4. **Theme 7 — by-products:** one by-product per harvest or several? (Chose child table above; confirm.)
5. **Theme 8 — outgrower depth:** just a produce source on an order line, or full outgrower ledger (payments owed to them)? Start minimal?
6. **Theme 3 — procurement approval:** does a purchase need an approval step, or is "ordered → received" enough?
7. **Note 4 biometrics:** is there existing hardware (fingerprint/face devices), or is this aspirational? Determines whether 4.4 is real soon.

---

## Files likely touched (by theme)

| Theme | Key files |
|-------|-----------|
| 1 | *(see companion doc)* |
| 2 | new `CropProgram*`/`CropCycleStage` models+migrations, `AlertService`, crop-cycle views |
| 3 | new `ProcurementRequest*`, `InventoryTransaction`, `AlertService` |
| 4 | `Worker`, `LabourAttendance`, `TaskAssignment` (edit); new `WorkerAttendance`; worker views |
| 5 | `Project` (guard + copy), routes/views |
| 6 | `InventoryItem` (category enum), inventory views, cleanup seeder |
| 7 | `HarvestBatch` (+ `confirmed_by`); new `HarvestByProduct`; harvest views |
| 8 | `SalesOrder` (+ reject/return fields), new `Outgrower`, `SalesOrderLine`, sales views |
| 9 | `AlertService`, `Guide` views |
