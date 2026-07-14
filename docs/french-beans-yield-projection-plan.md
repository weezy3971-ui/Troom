# French Beans — Yield Projection & Crop Forecasting — Implementation Plan

**Status:** Proposal · **Date:** 2026-07-14 · **Author:** Brian Kamau
**Source:** Trooms demo recording (`Trooms Demo.m4a`, transcribed locally with faster-whisper). French-beans discussion at **05:17–11:52**, with supporting mentions at 51:18 (buyer reports) and 58:46 (harvest by crop).

> **Transcription caveat:** the recording has background talk, accents, and domain jargon, so some figures below are approximate. Every number is flagged where the audio was unclear, and genuine ambiguities are collected in §8 (Open questions) rather than guessed at.

---

## 1. What was asked (from the recording)

The stakeholder (a farm agronomist) used **French beans** as the worked example for a capability the system does not yet have: **projecting yield and revenue from planting data, then refining that projection through the crop's life and again just before harvest.** Reconstructed from the transcript:

> *"Like French beans, we are doing planting on many beds… 100,000 seeds… 80, 90 beds."* (06:08–06:42)

> *"Your system should be able to do projection knowing that we have [one acre], we have 80 beds or 90 beds, planted X number of seeds, projected revenue — because with 100% germination [it] will be [Y]."* (06:43–07:03)

> *"Germination after five days was 80–90%. That is with evidence — empirical data… you use that data to do projection of the revenue."* (07:03–07:45)

> *"After 7 to 10 days… you do a scouting… the plant population I have is 70%, 80%, 90%, 100%. Now you give a report during that period."* (09:01–09:26)

> *"When you do your applications — fertilization, chemical applications — sometimes you lose one, two, three plants, then you do another [population count]… you do it like three times and then you are able to… project my production by 80%, 90%."* (09:27–10:00)

> *"It's good to understand those stages… have records to see, like — surprise, we expected 10,000, we only have 1,000."* (10:14–10:27)

> *"How we do the projection before harvest… we walk through 10 beds out of the 90 beds… this one bed gives us 7 kilos and we have 100 beds, so that is 700 kilos… we find this farmer giving us 700, [maybe he] gives us 650 or 800, which is very close."* (10:55–11:37)

Distilled into distinct features:

| # | Feature | Plain description |
|---|---------|-------------------|
| A | **Bed/seed-level planting record** | Capture how a cycle was actually planted: number of beds, seeds sown, area — the basis for any projection. |
| B | **Germination tracking** | Record germination assessments (≈5 days after sowing, e.g. 80–90%) from sampled counts. |
| C | **Plant-population / stand counts (scouting)** | Periodic surviving-population counts (70/80/90/100%) taken ~3× through the cycle, capturing losses from sprays/fertigation. |
| D | **Yield & revenue projection engine** | Compute projected kg and projected revenue from planting data × germination × population × expected yield, and show it against budget and actuals. |
| E | **Pre-harvest yield forecast (sampling)** | Walk a sample of beds, measure kg/bed, extrapolate to the block to forecast the coming harvest; compare forecast vs actual. |
| F | **Expected-vs-actual variance report** | At each stage, surface the gap between projected and realised figures. |

This is **crop-agnostic** — French beans is the demonstrator, but cabbage was named too (10:37, *"cabbage… 9,000"*), so the model must not hard-code beans.

---

## 2. What we're building on (current state)

The crop pipeline exists but stops short of projection. Relevant surfaces:

- **`Crop`** ([app/Models/Crop.php](../app/Models/Crop.php)) — has `days_to_maturity` and `expected_yield_per_acre`, plus default budget template fields. **No** price-per-kg, no per-bed yield.
- **`CropCycle`** ([app/Models/CropCycle.php](../app/Models/CropCycle.php)) — the natural home for a projection; already aggregates `plantings`, `harvestBatches`, `costAllocations`, `seasonalBudget`, and has `actualCost()` / `isBudgetExceeded()`.
- **`Planting`** ([app/Models/Planting.php](../app/Models/Planting.php)) — records only `quantity` + `planting_date`. **No beds, seeds, or area.**
- **`NurseryBatch`** ([app/Models/NurseryBatch.php](../app/Models/NurseryBatch.php)) — sowing/ready dates and quantity, with a `remainingQuantity()`. **No germination rate.**
- **`HarvestBatch`** ([app/Models/HarvestBatch.php](../app/Models/HarvestBatch.php)) — actual `quantity_kg`, grade, rejects. **No forecast to compare against.**
- **`Block`** ([app/Models/Block.php](../app/Models/Block.php)) — has `size_acres`, so acre-based math is available.
- **Revenue basis** — `SalesOrderLine.unit_price` ([app/Models/SalesOrderLine.php](../app/Models/SalesOrderLine.php)) is the only price in the system today; there is **no default/reference price** on a crop to project revenue before a sale exists.
- **KPIs** — `KpiSnapshotService` already computes yield/acre and revenue KPIs; projected yield can later feed an "expected vs actual" KPI (out of scope for v1, noted in §7).

**Net gap:** no place to store beds/seeds, germination, population counts, or a forecast; and no service that turns those into a projected kg/revenue figure.

---

## 3. Data model changes

All migrations follow the existing dated convention in `database/migrations/`.

### 3.1 Extend `crops` (projection inputs & price)
Add nullable columns so existing crops are unaffected:
- `seeds_per_bed` (integer, nullable)
- `expected_yield_per_bed_kg` (decimal, nullable) — alternative basis to per-acre; agronomists reason in beds
- `reference_price_per_kg` (decimal, nullable) — needed for **revenue** projection (§1 feature D)
- `expected_germination_rate` (decimal, nullable, e.g. 0.90) — default assumption before a real reading exists

### 3.2 Extend `plantings` (feature A)
- `bed_count` (integer, nullable)
- `seeds_sown` (integer, nullable)
- `area_acres` (decimal, nullable) — fallback when block size isn't the planted area

### 3.3 New table `germination_checks` (feature B)
Belongs to a `Planting` (and via it, the cycle):
`id, planting_id, check_date, days_after_sowing, sample_size, germinated_count, germination_rate (stored, derived), notes, recorded_by, timestamps`

### 3.4 New table `plant_population_counts` (feature C)
Belongs to a `CropCycle`:
`id, crop_cycle_id, count_date, sample_bed_count, plants_counted, expected_plants, population_rate (derived), notes, recorded_by, timestamps`

### 3.5 New table `yield_forecasts` (feature E)
Belongs to a `CropCycle`, optionally linked to the resulting `HarvestBatch` for variance:
`id, crop_cycle_id, forecast_date, sample_bed_count, total_bed_count, sample_yield_kg, projected_total_kg (derived), harvest_batch_id (nullable), actual_kg (nullable, copied on close), notes, recorded_by, timestamps`

> Design note: keep every derived field (`germination_rate`, `population_rate`, `projected_total_kg`) computed in the model as an accessor **and** persisted, matching the pattern in `CropCycle::actualCost()` — persisted for cheap listing/queries, accessor as the source of truth on write.

---

## 4. Business logic — `YieldProjectionService`

A single service (new: `app/Services/YieldProjectionService.php`) owns the math so controllers and KPIs stay thin. Public API keyed on a `CropCycle`:

- **`plantedBeds()` / `plantedArea()`** — sum from the cycle's plantings (fallback to block `size_acres`).
- **`currentGerminationRate()`** — latest `germination_check` for the cycle's plantings, else `crop.expected_germination_rate`, else a config default.
- **`currentPopulationRate()`** — latest `plant_population_count`, else 100%.
- **`projectedYieldKg()`** — the core estimate. Two supported bases, pick per crop:
  - **Per-bed:** `beds × expected_yield_per_bed_kg × germination × population`
  - **Per-acre:** `area × expected_yield_per_acre × germination × population`
- **`projectedRevenue()`** — `projectedYieldKg() × crop.reference_price_per_kg`.
- **`preHarvestForecastKg()`** — most recent `yield_forecast`: `sample_yield_kg / sample_bed_count × total_bed_count` (the "7 kg/bed × 100 beds = 700 kg" walk-through at 11:14).
- **`variance()`** — projected vs pre-harvest-forecast vs actual (`sum(harvestBatches.quantity_kg)`), returned as a small struct for the "we expected 10,000, we got 1,000" report (10:24).

Guard rails: every method returns `null`/0 gracefully when inputs are missing (a planned cycle with no counts yet shows "insufficient data", never a divide-by-zero). Mirror the all-DB-engine, collection-filter style used in `CropCycle::hasActivePreHarvestInterval()`.

---

## 5. Controllers, routes, UI

Follow the existing per-module controller + Blade convention (e.g. `HarvestBatchController` + `resources/views/harvest-batches/`).

**Controllers / routes** (`routes/web.php`, gated through `ModuleAccess` like the other modules):
- `GerminationCheckController` — nested under a planting/cycle; store + destroy.
- `PlantPopulationCountController` — nested under a cycle; store + destroy.
- `YieldForecastController` — nested under a cycle; store + destroy + link-to-harvest.
- Extend `PlantingController` create/edit forms with the new bed/seed/area fields.
- Extend `CropController` create/edit with the new per-bed/price/germination defaults.

**UI — main surface is the Crop Cycle show page** ([resources/views/crop-cycles/show.blade.php](../resources/views/crop-cycles/show.blade.php)):
- A **"Projection & Forecast"** panel showing: planted beds/area → current germination → current population → **projected kg** and **projected revenue**, with a small "expected vs actual" strip once harvests exist.
- Inline mini-forms/tables to add germination checks, population counts, and a pre-harvest forecast (same pattern as the existing spray/fertigation log tables on the cycle).
- Projected revenue displayed **against the seasonal budget** already on this page — this is what the stakeholder tied it to ("projected revenue… affects your budget/analysis").

---

## 6. Phased delivery

Each phase ships independently and is demoable on its own.

### Phase 1 — Planting detail + projection skeleton *(small)*
Migrations 3.1 + 3.2; extend Planting/Crop models, forms, and seeder; `YieldProjectionService` with the per-bed/per-acre projection (germination/population assumed from crop defaults); Projection panel on the cycle page showing projected kg + revenue. **Delivers feature A + D immediately** using assumed rates.

### Phase 2 — Germination tracking *(small)*
Table 3.3 + controller + inline form/table; service switches to the latest real germination reading. **Feature B.**

### Phase 3 — Population counts (scouting) *(small)*
Table 3.4 + controller + inline form/table; projection refines through the cycle; timeline of the 70/80/90/100% readings. **Feature C.**

### Phase 4 — Pre-harvest forecast + variance *(medium)*
Table 3.5 + controller; sampling calculator; link a forecast to its `HarvestBatch` and show **projected vs forecast vs actual** variance. **Features E + F.**

### Phase 5 (optional) — KPI & reporting rollup
Feed projected yield/revenue into `KpiSnapshotService` for an "expected vs actual" executive tile, and add a per-crop projection report. Deferred; depends on §7.

---

## 7. Seed / demo data (French beans)

Extend `database/seeders/DatabaseSeeder.php` so the existing **French Bean (Samantha)** crop and its completed cycle (`$cycle4`, Block B, Short Rains 2025/26) demonstrate the full flow:
- Set French Bean's `seeds_per_bed`, `expected_yield_per_bed_kg`, `reference_price_per_kg`, `expected_germination_rate`.
- Give `$cycle4`'s planting a `bed_count` (~90) and `seeds_sown` (~100,000) per the demo figures.
- Add 1 germination check (~85% at 5 days), 2–3 population counts (declining), and 1 pre-harvest forecast (7 kg/bed × 90 beds ≈ 630 kg) that resolves against the existing `harvest3` (650 kg) — so the variance panel has a live, believable story.

---

## 8. Open questions (need a decision before/within Phase 1)

1. **Projection basis** — do agronomists here reason **per bed** or **per acre**? The demo used beds; `Crop` currently stores per-acre. Recommend supporting both and choosing per crop (§4). *Which is primary for French beans?*
2. **Reference price** — is there a standard price/kg per crop for revenue projection, or does it vary per buyer/contract? If per-buyer, projection revenue should point at a contract price rather than a single crop field.
3. **Bed size** — is a "bed" a consistent unit (so beds→acres is derivable), or does it vary by block? Affects whether we store `area_acres` per planting.
4. **Who records** counts/forecasts — supervisors only, or field staff? Drives `ModuleAccess` gating.
5. **Later mentions (51:18 / 58:46)** — the buyer-rejection reports ("biggest French beans… they give us a report") and post-harvest quality tie-in read as a **separate** buyer/quality-feedback feature, not part of yield projection. Flagged here so it isn't lost; recommend a separate plan. *Confirm it's out of scope for this one.*

---

## 9. Files touched (summary)

| Area | Files |
|------|-------|
| Migrations | 4 new in `database/migrations/` (crops alter, plantings alter, 3 new tables) |
| Models | `Crop`, `Planting` (edit); `GerminationCheck`, `PlantPopulationCount`, `YieldForecast` (new); `CropCycle` (relations + helpers) |
| Service | `app/Services/YieldProjectionService.php` (new) |
| Controllers | `PlantingController`, `CropController` (edit); 3 new nested controllers |
| Routes | `routes/web.php` |
| Views | `crop-cycles/show.blade.php` (projection panel); `plantings/` + `crops/` forms |
| Seeder | `database/seeders/DatabaseSeeder.php` |
