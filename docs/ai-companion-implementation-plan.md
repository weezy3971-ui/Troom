# AI Reports & AI Companion — Implementation Plan

**Status:** Proposal · **Date:** 2026-07-14 · **Author:** Brian Kamau
**Source:** Demo meeting recording (`AI COMPANION.m4a`, transcribed) + meeting notes

---

## 1. What was asked (from the recording + notes)

The stakeholder request, transcribed from the demo, is:

> "On the executive dashboard… I want it to be having an **AI companion** and an **AI dashboard that is purely updated by AI**."
> "It will **compare** [us against competitors]… Omalo Farms in Karen is our competitor, they are doing X. You guys can capitalize by increasing your [yield]."
> "When I looked at the pricing, I saw it was only **$5 a month** to do such a thing."
> "That will form the foundation of us doing an **AI-driven** [system], and it will do **across both crops** [and every module]."

Combined with the written note — *"AI generate Reports · fully AI enabled companion"* — there are **four distinct features**:

| # | Feature | Plain description |
|---|---------|-------------------|
| A | **AI-generated reports** | On-demand narrative reports (executive summary, module reports) written by AI from the ERP's own data. |
| B | **AI dashboard narrative** ("purely updated by AI") | The executive dashboard gets an auto-refreshed AI insight/commentary layer on top of the existing KPI tiles. |
| C | **AI companion** | A conversational assistant embedded in the app that answers questions about the farm's data and can produce reports on request. |
| D | **Competitive benchmarking** | Compare our KPIs against competitors and recommend actions. *(Aspirational — see §6, needs a data source we don't yet have.)* |

The **$5/month** figure is realistic and is validated with real numbers in §5.

---

## 2. What we're building on (current state)

The codebase already has the data surfaces these features need — no new data modelling required for A–C:

- **`KpiSnapshotService`** ([app/Services/KpiSnapshotService.php](../app/Services/KpiSnapshotService.php)) — precomputes 10 executive KPIs (harvest, yield/acre, revenue, cost/kg, gross margin, orders pending, quality rejection, truck utilisation, farm health, cash-flow forecast) into the `kpi_snapshots` table, with daily history.
- **`AnalyticsController`** ([app/Http/Controllers/AnalyticsController.php](../app/Http/Controllers/AnalyticsController.php)) — the executive dashboard; also derives a full P&L from the double-entry ledger.
- **`AlertService`** ([app/Services/AlertService.php](../app/Services/AlertService.php)) — proactive cross-module alerts (budget exceeded, low inventory, orders at risk, pump failures).
- **35 module controllers** covering every domain (crops, harvest, packhouse, dispatch, finance, stables, inventory, …).
- **Queue = database** (`QUEUE_CONNECTION=database`) — async jobs already work, so report generation and dashboard refresh can run in the background.
- **Role gating** via `ModuleAccess` — reused so AI features respect the same permissions.

**What does not exist yet:** any AI/LLM integration, no HTTP client to a model provider, no API key configuration. This plan adds that foundation once (Phase 0) and every feature reuses it.

---

## 3. Technical approach

- **Provider/SDK:** Laravel/PHP project → use the official **Anthropic PHP SDK** (`anthropic-ai/sdk` via Composer). Config lives in `config/services.php` + `.env` (`ANTHROPIC_API_KEY`), matching how Postmark/Resend/Slack are already wired.
- **Grounding, not guessing:** the AI never invents numbers. A **`FarmContextAssembler`** service gathers the relevant KPI snapshots, P&L figures, and alerts into a compact structured payload that is passed to the model as context. The model's job is to *interpret and narrate* real figures, not produce them.
- **Cost governance (the "$5/month" constraint):** generate on a **schedule and cache** rather than calling the model on every page load. The dashboard narrative is computed once per day alongside the KPI recompute and stored; reports are generated on demand and stored. Every call is logged to an `ai_usage_logs` table (tokens + cost) so spend is visible and capped.
- **Model choice:** see §5 — recommend **Haiku 4.5** for the scheduled/batch work (reports + dashboard narrative) and let the companion optionally use a stronger model. This is a decision for you (§7).

---

## 4. Phased delivery

Each phase ships independently and delivers visible value on its own.

### Phase 0 — AI foundation (plumbing)
*Enables everything else. ~small.*

- Add `anthropic-ai/sdk` to `composer.json`; add `anthropic` block to `config/services.php`; add `ANTHROPIC_API_KEY` to `.env` / `.env.example`.
- **`App\Services\Ai\AiClient`** — thin wrapper around the SDK: takes a system prompt + context + user text, returns text, records token usage.
- **`App\Services\Ai\FarmContextAssembler`** — pulls KPI snapshots, P&L, and alerts into a compact text/JSON context block. One method per scope (`executive()`, `module(string $module)`).
- **`ai_usage_logs`** migration + model — timestamp, feature, model, input/output tokens, estimated cost, user_id. Powers a spend dashboard and a monthly cap.
- New `ai` entry in `ModuleAccess` so AI features are role-gated (default: owner, MD, horticulture_manager).

### Phase 1 — AI-generated reports (the "AI generate Reports" note)
*Highest-value, most concrete. Ships a real deliverable.*

- **`ai_reports`** table — type (executive_summary | module | custom), scope, period, generated markdown, model, tokens, status, generated_by.
- **`GenerateAiReport` queued job** — assembles context for the requested scope/period → calls `AiClient` → stores markdown.
- **`AiReportController`** — index (list past reports), create (pick type + date range), show (rendered report), regenerate. Routes gated by `ModuleAccess::middleware('ai')`.
- **Views** under `resources/views/ai-reports/` matching the existing Blade/card UI; "Generate Report" button on the executive dashboard.
- Report renders as narrative + the underlying figures, with a "Download" option (reuse existing PDF/print patterns if present).

### Phase 2 — AI dashboard narrative ("purely updated by AI")
*Directly answers "an AI dashboard that is purely updated by AI".*

- **`kpi_narratives`** table (or a column on the snapshot) — date, AI-written executive commentary (what changed, why it matters, what to watch), model, tokens.
- Extend the existing daily `KpiSnapshotService::recompute()` flow (already called in the seeder and can be scheduled in `routes/console.php`) to **also** generate the day's narrative via a queued job — "purely updated by AI," refreshed automatically, no per-view cost.
- Render an **"AI Insights"** panel at the top of the executive dashboard ([resources/views/analytics/index.blade.php]) showing the latest narrative + a "last updated" timestamp.
- Add a scheduled task (daily) in `routes/console.php` so it self-updates.

### Phase 3 — AI companion (the "fully AI enabled companion")
*The conversational assistant. Builds on Phase 0 context + Phase 1 report generation.*

- **`ai_conversations`** + **`ai_messages`** tables — per-user chat history.
- **`AiCompanionController`** — `chat` endpoint: takes a question, assembles relevant context (KPIs, alerts, and — via **tool use** — the ability to pull specific module data on demand), returns an answer; persists history.
- **Companion UI** — a slide-over/drawer available on the executive dashboard (and optionally app-wide), matching the current sidebar/nav styling. Streams the reply for responsiveness.
- The companion can **trigger a Phase 1 report** ("give me this month's executive summary") — reuse `GenerateAiReport`.
- Rate-limited per user; every call logged to `ai_usage_logs`.

### Phase 4 — Competitive benchmarking (aspirational — needs a data decision)
*The "compare against Omalo Farms" ask. Gated on where competitor data comes from — see §6.*

- Once a competitor-benchmark data source is agreed (§6), add a `competitor_benchmarks` table and feed those figures into `FarmContextAssembler` so reports/companion can say "your yield is X vs benchmark Y; to capitalize, do Z."

---

## 5. Cost — validating the "$5/month" expectation

Model pricing (per 1M tokens):

| Model | Input | Output |
|-------|-------|--------|
| Claude Haiku 4.5 | $1.00 | $5.00 |
| Claude Sonnet 5 | $3.00 ($2 intro) | $15.00 ($10 intro) |
| Claude Opus 4.8 | $5.00 | $25.00 |

A daily executive narrative or a report is roughly **~2K input + ~1K output tokens**:

- **Haiku 4.5:** ≈ **$0.007 per report** → a daily auto-narrative for a whole month ≈ **$0.21/month**. Add ~100 companion questions/month (~$0.01 each) ≈ **~$1.20/month total**.
- **Sonnet 5:** ~5× that — still comfortably inside **$5/month** at this volume.

**Conclusion:** the stakeholder's $5/month figure is achievable — with Haiku 4.5 it's achievable with large headroom, even including the companion. Cost only becomes a concern if the companion is very heavily used or we call the model on every page load (which this design deliberately avoids via scheduling + caching).

---

## 6. Open dependency: competitor data (Feature D)

The competitive-benchmarking ask ("Omalo Farms is doing X; you can capitalize by increasing yield to Y") **requires competitor figures the app does not have and AI cannot invent.** Options to decide:

1. **Manual entry** — a small admin screen to record competitor/industry benchmark numbers we obtain offline. Most accurate, some data-entry effort.
2. **Industry averages** — enter regional/crop benchmark ranges once; compare against those rather than a named competitor.
3. **Defer** — ship A–C now (they need no external data) and treat D as a fast-follow once a source is agreed.

**Recommendation:** ship Phases 0–3 first (they're fully self-sufficient), and treat D as Phase 4 pending your call on the data source.

---

## 7. Decisions needed from you

1. **Model for the batch work (reports + dashboard narrative):** Haiku 4.5 (cheapest, recommended for the $5/month target) vs Sonnet 5 (higher quality, still in budget at this volume).
2. **Companion scope:** executive dashboard only, or available app-wide from every page.
3. **Competitor data source** (Feature D): manual entry, industry averages, or defer.
4. **Phase order / where to start:** recommend Phase 0 → Phase 1 (reports) first, since it's the highest-value concrete deliverable.

---

## 8. Risks & mitigations

- **Hallucinated numbers** → the AI only ever *narrates* figures we compute and pass in; it never sources numbers itself. Reports display the underlying figures alongside the narrative.
- **Runaway cost** → scheduled + cached generation (not per-page-load), per-user rate limits, `ai_usage_logs` with a monthly cap.
- **API key handling** → key lives in `.env` / server secrets only, never in code or the repo, consistent with existing mail/Slack config.
- **Data privacy** → farm business data is sent to the model provider for processing; confirm that's acceptable and note it in the privacy policy.
- **Latency** → reports and narratives run as queued jobs; the companion streams responses.
```
