@extends('layouts.app')
@section('title', 'Planting Planner')

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

    /* Crop picker sits above the sheet — prominent, since it re-dates everything. */
    .crop-picker { display: flex; align-items: center; gap: 12px; flex-wrap: wrap; }
    .crop-picker select { max-width: 320px; }

    /* Load-a-cycle panel — the primary way in from an activated cycle, so it's
       given the brand green to stand out above the plain crop picker. */
    .pl-cycle-loader {
        background: var(--olive-bg);
        border: 1px solid var(--olive);
        border-left: 4px solid var(--olive);
        border-radius: var(--radius);
        padding: 16px 18px;
    }
    .pl-cycle-loader-head { display: flex; align-items: flex-start; gap: 12px; margin-bottom: 12px; }
    .pl-cycle-icon {
        font-size: 20px; line-height: 1; flex: none;
        width: 38px; height: 38px; border-radius: 10px;
        display: inline-flex; align-items: center; justify-content: center;
        background: var(--olive); box-shadow: 0 2px 6px rgba(47,107,59,0.30);
    }
    .pl-cycle-title { font-size: 15px; font-weight: 700; color: var(--text-primary); }
    .pl-cycle-sub { font-size: 12.5px; color: var(--text-secondary); margin-top: 2px; }
    .pl-cycle-loader select { max-width: 420px; border-color: var(--olive); }
    @media print { .pl-cycle-loader { display: none !important; } }

    .pl-sources { list-style: none; margin: 0; padding: 0; display: grid; gap: 7px; }
    .pl-sources li { font-size: 12.5px; color: var(--text-secondary); line-height: 1.45; }
    .pl-sources a { color: var(--olive); text-decoration: none; }
    .pl-sources a:hover { text-decoration: underline; }

    /* ---- Print / Save-as-PDF: strip the app chrome, keep only the worksheet ---- */
    @media print {
        @page { size: A4; margin: 12mm; }
        .sidebar, .topbar, .no-print, .alert { display: none !important; }
        .main-content { margin: 0 !important; padding: 0 !important; }
        .card { box-shadow: none !important; border-color: #bbb !important; break-inside: avoid; }
        table.sched tbody tr.is-plant, table.sched tbody tr.is-harvest {
            background: #fff !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        table.sched tbody tr, table.sched thead th { break-inside: avoid; }
        /* The unverified banner must survive printing — it's a safety notice.
           :not([hidden]) so a verified crop's hidden banner stays hidden: this
           rule outranks the global [hidden] rule on specificity alone. */
        .alert.print-keep:not([hidden]) { display: block !important; border: 1px solid #bbb; }
    }
</style>

<div class="page-header">
    <div>
        <h1 class="page-title" id="plTitle">{{ $programs[$selected]['title'] }}</h1>
        <p class="page-subtitle">Choose a crop and a planting date — the full cycle re-dates itself. Tick tasks, add notes, then save as PDF.</p>
    </div>
    <div class="actions no-print">
        <a href="{{ route('crop-cycles.index') }}" class="btn btn-secondary">← Crop Cycles</a>
        <button type="button" class="btn btn-primary" onclick="window.print()">⬇ Save as PDF</button>
    </div>
</div>

<div class="planner-stack">

    @if($activeCycles->isNotEmpty())
        {{-- Load a live, activated crop cycle: sets crop + planting date. --}}
        <div class="pl-cycle-loader">
            <div class="pl-cycle-loader-head">
                <span class="pl-cycle-icon">🗓</span>
                <div>
                    <div class="pl-cycle-title">Load an active crop cycle</div>
                    <div class="pl-cycle-sub">Pull in a live cycle's crop &amp; planting date — the whole plan re-dates itself.</div>
                </div>
            </div>
            <select id="plCycle" class="form-select" onchange="plLoadCycle()" aria-label="Load active crop cycle">
                <option value="">+ New plan</option>
                @foreach($activeCycles as $cyc)
                    <option value="{{ $cyc['id'] }}"
                        data-slug="{{ $cyc['slug'] }}"
                        data-date="{{ $cyc['date'] }}"
                        data-block="{{ $cyc['block'] }}"
                        data-variety="{{ $cyc['variety'] }}"
                        data-area="{{ $cyc['area'] }}"
                        {{ ($selectedCycleId ?? null) === $cyc['id'] ? 'selected' : '' }}>{{ $cyc['label'] }}</option>
                @endforeach
            </select>
        </div>
    @endif

    {{-- Crop picker --}}
    <div class="card">
        <div class="card-header"><h3 class="card-title">Which crop?</h3></div>
        <div class="crop-picker">
            <select id="plCrop" class="form-select" onchange="plRenderAll()" aria-label="Choose crop">
                @foreach($programs as $slug => $program)
                    <option value="{{ $slug }}" {{ $slug === $selected ? 'selected' : '' }}>{{ $program['crop'] }}</option>
                @endforeach
            </select>
            <span class="page-subtitle" style="margin:0;" id="plDayZeroHint"></span>
        </div>
    </div>

    {{-- Shown only for programs that haven't been agronomist-verified --}}
    <div class="alert alert-warning print-keep" id="plUnverified" hidden>
        <div><strong>UNVERIFIED PLAN</strong> — <span id="plUnverifiedText"></span></div>
    </div>

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
                <x-combobox name="planner_variety" :options="[]" placeholder="Variety" />
            </div>
            <div class="form-group" style="margin:0;">
                <label class="form-label">Area (acres)</label>
                <input type="text" id="planner_area" class="form-input" placeholder="e.g. 1.0">
            </div>
            <div class="form-group" style="margin:0;">
                <label class="form-label" id="plDateLabel">Planting date (Day 0)</label>
                <input type="date" class="form-input" id="plantDate" value="{{ $plantDate ?? now()->addDay()->toDateString() }}" onchange="plRenderSchedule()">
            </div>
            <div class="form-group" style="margin:0;">
                <label class="form-label">Prepared by</label>
                <input type="text" class="form-input" value="{{ auth()->user()->name }}">
            </div>
        </div>
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

    {{-- Agronomy notes (per crop) --}}
    <div id="plNotes"></div>

    <p class="page-subtitle" style="max-width:70ch;" id="plFooter"></p>

    {{-- Where these figures come from — kept on the printed sheet, like the source list on the French Bean doc. --}}
    <div class="card" id="plSourcesCard">
        <div class="card-header"><h3 class="card-title">Sources</h3></div>
        <ul class="pl-sources" id="plSources"></ul>
    </div>

</div>

<script>
    var PL_PROGRAMS  = @json($programs);
    var PL_VARIETIES = @json($varietiesByCrop);

    var PL_DOW = ["Sun","Mon","Tue","Wed","Thu","Fri","Sat"];
    var PL_MON = ["Jan","Feb","Mar","Apr","May","Jun","Jul","Aug","Sep","Oct","Nov","Dec"];

    function plAddDays(base, n) { return new Date(base.getTime() + n * 86400000); }
    function plFmt(d) { return PL_DOW[d.getUTCDay()] + " " + d.getUTCDate() + " " + PL_MON[d.getUTCMonth()] + " " + d.getUTCFullYear(); }
    function plCurrent() { return PL_PROGRAMS[document.getElementById("plCrop").value]; }

    // Day 0 differs by crop: nursery crops are dated from transplant, not sowing.
    function plDayZeroLabel(p) {
        return p.day_zero === "transplant" ? "Transplant date (Day 0)" : "Planting date (Day 0)";
    }

    function plRenderSchedule() {
        var p = plCurrent();
        var v = document.getElementById("plantDate").value;
        var tbody = document.getElementById("schedRows");
        tbody.innerHTML = "";
        if (!v) return;
        var parts = v.split("-");
        var base = new Date(Date.UTC(+parts[0], +parts[1] - 1, +parts[2]));
        p.rows.forEach(function (r) {
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

    // Sources an admin has removed under Administration → Sources are filtered
    // out server-side and never reach this list.
    function plRenderSources(p) {
        var ul = document.getElementById("plSources");
        ul.innerHTML = "";
        (p.sources || []).forEach(function (s) {
            var li = document.createElement("li");
            var a = document.createElement("a");
            a.href = s.url;
            a.target = "_blank";
            a.rel = "noopener";
            a.textContent = s.label;
            li.appendChild(document.createTextNode("› "));
            li.appendChild(a);
            ul.appendChild(li);
        });
        document.getElementById("plSourcesCard").hidden = !(p.sources && p.sources.length);
    }

    function plRenderNotes(p) {
        var box = document.getElementById("plNotes");
        box.innerHTML = "";
        p.notes.forEach(function (n) {
            var div = document.createElement("div");
            div.className = "alert alert-" + n.type + " no-print";
            div.innerHTML = "<div><strong>" + n.title + "</strong> &mdash; " + n.body + "</div>";
            box.appendChild(div);
        });
    }

    // Repoint the variety combobox at the chosen crop's list (free text still allowed).
    function plRenderVarieties(p) {
        var input = document.getElementById("planner_variety");
        var menu = input.closest("[data-combobox]").querySelector("[data-combobox-menu]");
        var list = PL_VARIETIES[p.crop] || [];
        menu.innerHTML = "";
        list.forEach(function (name) {
            var opt = document.createElement("div");
            opt.className = "combobox-option";
            opt.setAttribute("role", "option");
            opt.dataset.value = name;
            opt.textContent = name;
            menu.appendChild(opt);
        });
        input.value = "";
        input.placeholder = p.variety_placeholder;
    }

    function plRenderAll() {
        var p = plCurrent();
        document.getElementById("plTitle").textContent = p.title;
        document.getElementById("plDateLabel").textContent = plDayZeroLabel(p);
        document.getElementById("plDayZeroHint").textContent =
            p.day_zero === "transplant"
                ? "Day 0 = transplant (this crop is nursery-raised first)."
                : "Day 0 = sowing (direct-sown crop).";

        var banner = document.getElementById("plUnverified");
        if (p.verified) {
            banner.hidden = true;
        } else {
            banner.hidden = false;
            // A plan can be unverified from the outset, or become unverified
            // because an administrator deleted a source it was built on.
            document.getElementById("plUnverifiedText").innerHTML = p.sources_deleted
                ? "An administrator deleted " + p.sources_deleted + " of the sources behind this " + p.crop +
                  " plan, so its spacing and fertiliser figures are no longer backed by a checked reference. " +
                  "Confirm them with your agronomist before planting to it."
                : "This " + p.crop + " plan is an indicative baseline, not a verified protocol. " +
                  "Confirm spacing, fertiliser rates and the spray programme with your agronomist before planting to it.";
        }

        plRenderVarieties(p);
        plRenderNotes(p);
        plRenderSources(p);
        plRenderSchedule();
        document.getElementById("plFooter").textContent = p.footer;
    }

    var PL_DEFAULT_DATE = "{{ now()->addDay()->toDateString() }}";
    function plSetVal(id, v) { var el = document.getElementById(id); if (el) { el.value = v || ""; } }

    // Load a selected active crop cycle: auto-fill crop + all cycle details, then
    // re-date the whole sheet. Choosing "New plan" resets the details to blank.
    function plLoadCycle() {
        var sel = document.getElementById("plCycle");
        if (!sel) { return; }
        var opt = sel.options[sel.selectedIndex];
        var isCycle = !!(opt && opt.value);

        if (isCycle) {
            var slug = opt.getAttribute("data-slug");
            if (slug) { document.getElementById("plCrop").value = slug; }
            plSetVal("plantDate", opt.getAttribute("data-date") || PL_DEFAULT_DATE);
            plSetVal("planner_block", opt.getAttribute("data-block"));
            plSetVal("planner_variety", opt.getAttribute("data-variety"));
            plSetVal("planner_area", opt.getAttribute("data-area"));
        } else {
            // New plan — reset the cycle-detail fields to a clean slate.
            plSetVal("plantDate", PL_DEFAULT_DATE);
            plSetVal("planner_block", "");
            plSetVal("planner_variety", "");
            plSetVal("planner_area", "");
        }
        plRenderAll();
    }

    plRenderAll();
</script>
@endsection
