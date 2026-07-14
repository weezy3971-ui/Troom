@extends('layouts.app')
@section('title', 'French Bean Planting Planner')

@section('content')
<style>
    /* ---- Planner-scoped styles (build on the app design system) ---- */
    .planner-meta { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; }
    .planner-check { list-style: none; margin: 0; padding: 0; display: grid; gap: 10px; }
    .planner-check li { display: flex; align-items: flex-start; gap: 10px; font-size: 13.5px; color: var(--text-secondary); }
    .planner-check input[type="checkbox"] { width: 17px; height: 17px; margin-top: 1px; accent-color: var(--olive); flex: none; }
    .planner-check .must { color: var(--danger-text); font-weight: 600; }

    .sched-wrap { overflow-x: auto; border-radius: var(--radius); border: 1px solid var(--border); }
    table.sched { width: 100%; border-collapse: collapse; min-width: 720px; }
    table.sched thead th { background: var(--bg-secondary); padding: 10px 14px; text-align: left;
        font-size: 10.5px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.6px;
        color: var(--text-muted); border-bottom: 1px solid var(--border); white-space: nowrap; }
    table.sched tbody td { padding: 11px 14px; font-size: 13px; border-bottom: 1px solid rgba(214, 222, 204, 0.6);
        color: var(--text-secondary); vertical-align: top; }
    table.sched tbody tr:last-child td { border-bottom: none; }
    table.sched tbody tr.is-plant { background: var(--olive-bg); }
    table.sched tbody tr.is-harvest { background: var(--terracotta-bg); }
    .sched .col-done { text-align: center; width: 34px; }
    .sched .col-done input { width: 17px; height: 17px; accent-color: var(--olive); }
    .sched .ph-name { font-weight: 600; color: var(--text-primary); }
    .sched .ph-day { font-family: var(--font-mono); font-size: 11px; color: var(--text-muted); }
    .sched .ph-date { font-family: var(--font-mono); font-size: 12.5px; color: var(--olive); white-space: nowrap; font-feature-settings: 'tnum' 1; }
    .sched tr.is-harvest .ph-date { color: var(--terracotta); }
    .sched .ph-task b { color: var(--text-primary); }
    .sched .col-notes { width: 160px; }
    .sched .col-notes input { width: 100%; min-width: 120px; border: none; border-bottom: 1px dashed var(--border);
        background: transparent; font-family: inherit; font-size: 12.5px; color: var(--text-primary); padding: 3px 2px; }
    .sched .col-notes input:focus { outline: none; border-bottom-color: var(--olive); }

    .planner-stack { display: grid; gap: 20px; }

    /* ---- Print / Save-as-PDF: strip the app chrome, keep only the worksheet ---- */
    @media print {
        @page { size: A4; margin: 12mm; }
        .sidebar, .topbar, .no-print, .alert { display: none !important; }
        .main-content { margin: 0 !important; padding: 0 !important; }
        .card { box-shadow: none !important; border-color: #bbb !important; break-inside: avoid; }
        table.sched tbody tr.is-plant, table.sched tbody tr.is-harvest {
            background: #fff !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        table.sched tbody tr, table.sched thead th { break-inside: avoid; }
    }
</style>

<div class="page-header">
    <div>
        <h1 class="page-title">French Bean Planting Planner</h1>
        <p class="page-subtitle">Set a planting date and the full crop cycle re-dates itself. Tick tasks, add notes, then save as PDF.</p>
    </div>
    <div class="actions no-print">
        <a href="{{ route('crop-cycles.index') }}" class="btn btn-secondary">← Crop Cycles</a>
        <button type="button" class="btn btn-primary" onclick="window.print()">⬇ Save as PDF</button>
    </div>
</div>

<div class="planner-stack">

    {{-- Header details --}}
    <div class="card">
        <div class="card-header"><h3 class="card-title">Cycle details</h3></div>
        <div class="planner-meta">
            <div class="form-group" style="margin:0;">
                <label class="form-label">Farm / Block</label>
                <x-combobox name="planner_block" :options="$blocks" placeholder="e.g. Trooms Naivasha — Block C" />
            </div>
            <div class="form-group" style="margin:0;">
                <label class="form-label">Variety</label>
                <x-combobox name="planner_variety" :options="$varieties" placeholder="e.g. Serengeti (extra-fine)" />
            </div>
            <div class="form-group" style="margin:0;">
                <label class="form-label">Area (acres)</label>
                <input type="text" class="form-input" placeholder="e.g. 1.0">
            </div>
            <div class="form-group" style="margin:0;">
                <label class="form-label">Planting date (Day 0)</label>
                <input type="date" class="form-input" id="plantDate" value="{{ now()->addDay()->toDateString() }}" onchange="renderSchedule()">
            </div>
            <div class="form-group" style="margin:0;">
                <label class="form-label">Prepared by</label>
                <input type="text" class="form-input" value="{{ auth()->user()->name }}">
            </div>
            <div class="form-group" style="margin:0;">
                <label class="form-label">Exporter / buyer</label>
                <x-combobox name="planner_buyer" :options="$buyers" placeholder="Contract / market" />
            </div>
        </div>
    </div>

    {{-- Readiness checklist --}}
    <div class="card">
        <div class="card-header"><h3 class="card-title">Before you plant — readiness check</h3></div>
        <ul class="planner-check">
            <li><input type="checkbox"> Land ploughed &amp; harrowed to a fine tilth; drip lines laid</li>
            <li><input type="checkbox"> Certified seed in hand (~30 kg/acre)</li>
            <li><input type="checkbox"> DAP basal fertiliser ready (~80 kg/acre)</li>
            <li><input type="checkbox"> <span class="must">Irrigation working</span> — the dry season has no rain to save the crop</li>
            <li><input type="checkbox"> Well-rotted manure / compost worked in</li>
            <li><input type="checkbox"> Soil test done (target pH 6.5–7.5)</li>
            <li><input type="checkbox"> Exporter contract &amp; compliance (GLOBALG.A.P / KEPHIS) in motion</li>
        </ul>
    </div>

    {{-- The dated schedule --}}
    <div class="card">
        <div class="card-header"><h3 class="card-title">The cycle, by date</h3></div>
        <div class="sched-wrap">
            <table class="sched">
                <thead>
                    <tr>
                        <th class="col-done">✓</th>
                        <th>Phase</th>
                        <th>Date(s)</th>
                        <th>What to do</th>
                        <th class="col-notes">Notes</th>
                    </tr>
                </thead>
                <tbody id="schedRows"></tbody>
            </table>
        </div>
    </div>

    {{-- Agronomy notes --}}
    <div class="alert alert-warning no-print">
        <div><strong>WEATHER</strong> — In the cool, cloudy Kenyan dry season (roughly Jun–Aug) growth can run a few days slower, so treat the first-harvest date as the earliest, not the guaranteed, start.</div>
    </div>
    <div class="alert alert-error no-print">
        <div><strong>TWO DATES DECIDE THE CROP</strong> — During <b>flowering</b>, thrips scar pods (→ export rejection) and moisture must stay even. And stop residual sprays early enough that the <b>pre-harvest interval clears before the first pick</b>.</div>
    </div>

    <p class="page-subtitle" style="max-width:70ch;">Typical ranges for Kenyan export French beans; they vary by variety, altitude and buyer spec. Defer to your exporter's protocol, current KEPHIS/EU rules and a local soil test.</p>

</div>

<script>
    // [label, task(html), startOffsetDays, endOffsetDays|null, rowClass]
    var PLANNER_ROWS = [
        ["Plant", "Sow at <b>30&times;15 cm</b>, DAP in the furrow (off the seed), irrigate in.", 0, null, "is-plant"],
        ["Germination &amp; emergence", "Emergence in 5&ndash;8 days. Keep soil damp. <b>Scout bean fly</b> from day one.", 3, 8, ""],
        ["Gap-fill", "Replant blanks to keep a uniform plant stand.", 9, null, ""],
        ["1st top-dress (CAN)", "At 2&ndash;3 leaf stage: CAN ~60 kg/acre (split). <b>Weed now.</b>", 14, 18, ""],
        ["Vegetative growth", "Steady water ~50 mm/week. Scout aphids &amp; whitefly.", 8, 30, ""],
        ["Flowering", "<b>2nd top-dress (CAN)</b> as flowers open. Even moisture + <b>thrips control</b> are critical.", 30, 40, ""],
        ["Pod fill", "Finish sprays &mdash; respect pre-harvest intervals. Watch rust / anthracnose.", 40, 48, ""],
        ["First harvest", "First pick around day 45&ndash;50. Pick young, straight, string-free pods.", 45, 50, "is-harvest"],
        ["Harvest window", "Rolling ~3 weeks. Fine: pick 2&times;/week &middot; extra-fine: 3&times;/week. Early-morning picks.", 45, 70, "is-harvest"],
        ["Close-out &amp; rotation", "Uproot, sanitise residues, rotate to a <b>non-legume</b> next.", 75, 90, ""]
    ];
    var PL_DOW = ["Sun","Mon","Tue","Wed","Thu","Fri","Sat"];
    var PL_MON = ["Jan","Feb","Mar","Apr","May","Jun","Jul","Aug","Sep","Oct","Nov","Dec"];

    function plAddDays(base, n) { return new Date(base.getTime() + n * 86400000); }
    function plFmt(d) { return PL_DOW[d.getUTCDay()] + " " + d.getUTCDate() + " " + PL_MON[d.getUTCMonth()] + " " + d.getUTCFullYear(); }

    function renderSchedule() {
        var v = document.getElementById("plantDate").value;
        var tbody = document.getElementById("schedRows");
        tbody.innerHTML = "";
        if (!v) return;
        var p = v.split("-");
        var base = new Date(Date.UTC(+p[0], +p[1] - 1, +p[2]));
        PLANNER_ROWS.forEach(function (r) {
            var start = plAddDays(base, r[2]);
            var dateStr = (r[3] === null) ? plFmt(start) : plFmt(start) + " &ndash; " + plFmt(plAddDays(base, r[3]));
            var dayStr = (r[3] === null) ? "Day " + r[2] : "Day " + r[2] + "&ndash;" + r[3];
            var tr = document.createElement("tr");
            if (r[4]) tr.className = r[4];
            tr.innerHTML =
                '<td class="col-done"><input type="checkbox"></td>' +
                '<td><div class="ph-name">' + r[0] + '</div><div class="ph-day">' + dayStr + '</div></td>' +
                '<td class="ph-date">' + dateStr + '</td>' +
                '<td class="ph-task">' + r[1] + '</td>' +
                '<td class="col-notes"><input type="text" aria-label="notes"></td>';
            tbody.appendChild(tr);
        });
    }
    renderSchedule();
</script>
@endsection
