@extends('layouts.app')
@section('title', 'Set Up Planting')

@section('content')
<x-crumb-nav />

<div class="page-header">
    <div>
        <h1 class="page-title">New Crop Cycle</h1>
        <p class="page-subtitle">Pick or add the farm, block and crop, then set the budget — all in one place.</p>
    </div>
</div>

@if ($errors->any())
    <div class="alert alert-error" style="margin-bottom:16px; flex-direction:column; align-items:flex-start;">
        <strong>Please check the highlighted steps.</strong>
        <ul style="margin:6px 0 0 18px;">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="card" style="max-width: 760px;">
    <form action="{{ route('setup') }}" method="POST" id="wizForm">
        @csrf

        {{-- ───────────────── Step 1 — Farm ───────────────── --}}
        <section class="wiz-panel" data-panel="1">
            <h3 class="wiz-title"><span class="wiz-num">1</span> Which farm?</h3>

            <div class="seg" data-seg="farm_mode">
                <label class="seg-btn"><input type="radio" name="farm_mode" value="existing" @if($farms->isEmpty()) disabled @else checked @endif> Use existing</label>
                <label class="seg-btn"><input type="radio" name="farm_mode" value="new" @if($farms->isEmpty()) checked @endif> Add new</label>
            </div>

            <div class="mode mode-existing" data-mode="farm-existing">
                <div class="form-group">
                    <label class="form-label" for="existing_farm_id">Farm *</label>
                    <select id="existing_farm_id" name="existing_farm_id" class="form-select">
                        <option value="">Select a farm</option>
                        @foreach($farms as $farm)
                            <option value="{{ $farm->id }}" {{ old('existing_farm_id') == $farm->id ? 'selected' : '' }}>{{ $farm->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="mode mode-new" data-mode="farm-new">
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label" for="farm_name">Farm Name *</label>
                        <input type="text" id="farm_name" name="farm_name" value="{{ old('farm_name') }}" class="form-input">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="farm_location">Location *</label>
                        <input type="text" id="farm_location" name="farm_location" value="{{ old('farm_location') }}" class="form-input">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="farm_size_acres">Size (Acres) *</label>
                        <input type="number" id="farm_size_acres" name="farm_size_acres" value="{{ old('farm_size_acres') }}" class="form-input" step="0.01" min="0.01">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="farm_latitude">Latitude</label>
                        <input type="number" id="farm_latitude" name="farm_latitude" value="{{ old('farm_latitude') }}" class="form-input" step="0.0000001">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="farm_longitude">Longitude</label>
                        <input type="number" id="farm_longitude" name="farm_longitude" value="{{ old('farm_longitude') }}" class="form-input" step="0.0000001">
                    </div>
                </div>
            </div>
        </section>

        {{-- ───────────────── Step 2 — Block ───────────────── --}}
        <section class="wiz-panel" data-panel="2">
            <h3 class="wiz-title"><span class="wiz-num">2</span> Which block?</h3>

            <div class="seg" data-seg="block_mode">
                <label class="seg-btn"><input type="radio" name="block_mode" value="existing"> Use existing</label>
                <label class="seg-btn"><input type="radio" name="block_mode" value="new" checked> Add new</label>
            </div>

            <div class="mode mode-existing" data-mode="block-existing">
                <div class="form-group">
                    <label class="form-label" for="existing_block_id">Block *</label>
                    <select id="existing_block_id" name="existing_block_id" class="form-select">
                        <option value="">Select a block</option>
                        @foreach($blocks as $block)
                            <option value="{{ $block->id }}" data-farm-id="{{ $block->farm_id }}" {{ old('existing_block_id') == $block->id ? 'selected' : '' }}>{{ $block->name }}</option>
                        @endforeach
                    </select>
                    <p class="wiz-hint" data-block-empty style="display:none;">No blocks on the selected farm yet — add a new one.</p>
                </div>
            </div>

            <div class="mode mode-new" data-mode="block-new">
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label" for="block_name">Block Name *</label>
                        <input type="text" id="block_name" name="block_name" value="{{ old('block_name') }}" class="form-input" placeholder="e.g. KJB1">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="block_size_acres">Size (Acres) *</label>
                        <input type="number" id="block_size_acres" name="block_size_acres" value="{{ old('block_size_acres') }}" class="form-input" step="0.01" min="0.01">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="block_soil_type">Soil Type</label>
                        <x-combobox name="block_soil_type" :value="old('block_soil_type')" :options="\App\Support\ReferenceData::soilTypes()" placeholder="e.g. Clay loam, Sandy" />
                    </div>
                </div>

                {{-- Land preparation gates activation, so the wizard asks once
                     here rather than adding a step for work done in the field. --}}
                <div class="form-group" style="margin-top: 4px;">
                    <label class="form-label" style="display:flex; gap:9px; align-items:flex-start; font-weight:500; cursor:pointer;">
                        <input type="checkbox" name="block_already_prepared" value="1" style="margin-top:2px;" {{ old('block_already_prepared') ? 'checked' : '' }}>
                        <span>
                            This block is already prepared and ready to plant
                            <span style="display:block; font-size:11.5px; color:var(--text-muted); font-weight:400; margin-top:3px;">
                                Leave unticked and a preparation checklist is created for the block. The cycle stays
                                <strong>planned</strong> until preparation is finished — a block isn't planted into before it's prepared.
                            </span>
                        </span>
                    </label>
                </div>
            </div>
        </section>

        {{-- ───────────────── Step 3 — Crop ───────────────── --}}
        <section class="wiz-panel" data-panel="3">
            <h3 class="wiz-title"><span class="wiz-num">3</span> Which crop?</h3>

            <div class="seg" data-seg="crop_mode">
                <label class="seg-btn"><input type="radio" name="crop_mode" value="existing" @if($crops->isEmpty()) disabled @else checked @endif> Use existing</label>
                <label class="seg-btn"><input type="radio" name="crop_mode" value="new" @if($crops->isEmpty()) checked @endif> Add new</label>
            </div>

            <div class="mode mode-existing" data-mode="crop-existing">
                <div class="form-group">
                    <label class="form-label" for="existing_crop_id">Crop *</label>
                    <select id="existing_crop_id" name="existing_crop_id" class="form-select">
                        <option value="">Select a crop</option>
                        @foreach($crops as $crop)
                            <option value="{{ $crop->id }}"
                                data-maturity="{{ $crop->days_to_maturity }}"
                                data-labour="{{ $crop->default_labour_budget }}"
                                data-input="{{ $crop->default_input_budget }}"
                                data-irrigation="{{ $crop->default_irrigation_budget }}"
                                data-overhead="{{ $crop->default_overhead_budget }}"
                                {{ old('existing_crop_id') == $crop->id ? 'selected' : '' }}>{{ $crop->name }} {{ $crop->variety ? '('.$crop->variety.')' : '' }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="mode mode-new" data-mode="crop-new">
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label" for="crop_name">Crop Name *</label>
                        <x-combobox name="crop_name" :value="old('crop_name')" :options="\App\Support\ReferenceData::cropNames()" placeholder="Type or pick a crop" />
                    </div>
                    <div class="form-group" data-variety-field>
                        <label class="form-label" for="crop_variety">Variety</label>
                        <x-combobox name="crop_variety" :value="old('crop_variety')" :options="\App\Support\ReferenceData::varieties()" placeholder="Type or pick a variety" />
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="crop_type">Crop Type *</label>
                        <x-combobox name="crop_type" :value="old('crop_type')" :options="\App\Support\ReferenceData::cropTypes()" placeholder="e.g. Vegetable, Fruit" />
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="crop_days_to_maturity">Days to Maturity</label>
                        <input type="number" id="crop_days_to_maturity" name="crop_days_to_maturity" value="{{ old('crop_days_to_maturity') }}" class="form-input" min="1">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="crop_expected_yield_per_acre">Expected Yield per Acre (kg)</label>
                        <input type="number" id="crop_expected_yield_per_acre" name="crop_expected_yield_per_acre" value="{{ old('crop_expected_yield_per_acre') }}" class="form-input" step="0.01" min="0">
                    </div>
                </div>

            </div>
        </section>

        {{-- ───────────────── Step 4 — Crop Cycle ───────────────── --}}
        <section class="wiz-panel" data-panel="4">
            <h3 class="wiz-title"><span class="wiz-num">4</span> The crop cycle</h3>

            <div class="form-grid">
                <div class="form-group">
                    <label class="form-label" for="season_name">Season Name *</label>
                    <input type="text" id="season_name" name="season_name" value="{{ old('season_name') }}" class="form-input" placeholder="e.g. Long Rains 2026">
                </div>
                <div class="form-group">
                    <label class="form-label" for="planting_date">Planting Date</label>
                    <input type="date" id="planting_date" name="planting_date" value="{{ old('planting_date') }}" class="form-input">
                </div>
                <div class="form-group">
                    <label class="form-label" for="expected_harvest_date">Expected Harvest Date</label>
                    <input type="date" id="expected_harvest_date" name="expected_harvest_date" value="{{ old('expected_harvest_date') }}" class="form-input">
                    <p class="wiz-hint">Auto-calculated from the crop's maturity when you set a planting date — you can override it.</p>
                </div>
            </div>
        </section>

        {{-- ───────────────── Step 5 — Seasonal Budget ───────────────── --}}
        <section class="wiz-panel" data-panel="5">
            <h3 class="wiz-title"><span class="wiz-num">5</span> Seasonal budget</h3>
            <p class="wiz-hint" style="margin-bottom:16px;">Set the budget for this cycle — it's required to activate. Pre-filled from the crop's template where available; adjust as needed.</p>

            <div class="wiz-summary" id="wizSummary"></div>

            <div class="form-grid">
                <div class="form-group">
                    <label class="form-label" for="labour_budget">Labour (KES) *</label>
                    <input type="number" id="labour_budget" name="labour_budget" value="{{ old('labour_budget') }}" class="form-input wiz-budget" step="0.01" min="0" placeholder="0">
                </div>
                <div class="form-group">
                    <label class="form-label" for="input_budget">Inputs (KES) *</label>
                    <input type="number" id="input_budget" name="input_budget" value="{{ old('input_budget') }}" class="form-input wiz-budget" step="0.01" min="0" placeholder="0">
                </div>
                <div class="form-group">
                    <label class="form-label" for="irrigation_budget">Irrigation (KES) *</label>
                    <input type="number" id="irrigation_budget" name="irrigation_budget" value="{{ old('irrigation_budget') }}" class="form-input wiz-budget" step="0.01" min="0" placeholder="0">
                </div>
                <div class="form-group">
                    <label class="form-label" for="overhead_budget">Overhead (KES) *</label>
                    <input type="number" id="overhead_budget" name="overhead_budget" value="{{ old('overhead_budget') }}" class="form-input wiz-budget" step="0.01" min="0" placeholder="0">
                </div>
            </div>

            <div class="wiz-total" id="wizBudgetTotal">Total budget: <strong>KES 0</strong></div>
        </section>

        {{-- Footer nav --}}
        <div class="wiz-footer">
            <a href="{{ route('crop-cycles.index') }}" class="btn btn-ghost">Cancel</a>
            <button type="submit" class="btn btn-success" id="wizSubmit">Create Crop Cycle</button>
        </div>
    </form>
</div>

<style>
    /* Single scrolling form: every section visible, divided for scanning. */
    .wiz-panel { display:block; padding-top:22px; margin-top:22px; border-top:1px solid var(--border); }
    .wiz-panel[data-panel="1"] { padding-top:0; margin-top:0; border-top:0; }
    .wiz-title { font-size:16px; font-weight:600; margin:0 0 14px; display:flex; align-items:center; gap:9px; }
    .wiz-num { display:inline-flex; align-items:center; justify-content:center; width:24px; height:24px; border-radius:50%; background:var(--olive); color:#fff; font-size:12px; font-weight:700; flex:none; }
    .wiz-hint { font-size:12px; color:var(--text-muted); margin-top:4px; }

    .seg { display:inline-flex; gap:8px; margin-bottom:18px; }
    .seg-btn {
        display:inline-flex; align-items:center; gap:7px;
        padding:11px 22px; font-size:14px; font-weight:600; cursor:pointer;
        color:var(--olive); background:var(--bg-card); user-select:none;
        border:2px solid var(--olive); border-radius:var(--radius-sm);
        transition:background var(--transition), color var(--transition), box-shadow var(--transition);
    }
    .seg-btn::before {
        content:''; width:15px; height:15px; border-radius:50%;
        border:2px solid var(--olive); flex:none; transition:all var(--transition);
    }
    .seg-btn:hover { background:var(--olive-bg); }
    .seg-btn input { position:absolute; opacity:0; pointer-events:none; }
    /* Selected: solid green fill, white text — clearly the active choice. */
    .seg-btn:has(input:checked) {
        background:var(--olive); color:#fff;
        box-shadow:0 2px 6px rgba(47,107,59,0.35);
    }
    .seg-btn:has(input:checked)::before {
        border-color:#fff; background:#fff;
        box-shadow:inset 0 0 0 3px var(--olive);
    }
    .seg-btn:has(input:disabled) { opacity:.4; cursor:not-allowed; border-color:var(--border); color:var(--text-muted); }
    .seg-btn:has(input:disabled)::before { border-color:var(--text-muted); }

    .mode { display:none; }
    .mode.is-active { display:block; }

    .wiz-advanced { margin-top:14px; }
    .wiz-advanced summary { cursor:pointer; font-size:13px; font-weight:600; color:var(--accent-hover); }

    .wiz-summary { background:var(--olive-bg); border-radius:var(--radius-sm); padding:12px 14px; margin-bottom:16px; font-size:13px; color:var(--text-secondary); line-height:1.7; }
    .wiz-summary strong { color:var(--text-primary); }

    .wiz-total { margin-top:14px; padding-top:14px; border-top:1px solid var(--border); font-size:14px; color:var(--text-secondary); }
    .wiz-total strong { color:var(--olive); font-size:16px; }

    .wiz-footer { display:flex; justify-content:space-between; align-items:center; gap:12px; margin-top:24px; padding-top:16px; border-top:1px solid var(--border); }
    .wiz-invalid { border-color:#dc2626 !important; }
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var form = document.getElementById('wizForm');
    var totalSteps = 5;

    // ── Existing / New mode toggles ─────────────────────────────
    // Disable inputs in the hidden sub-panel so they're neither submitted nor
    // validated — server-side required_if keys off the *_mode value.
    function applyMode(group) {
        var mode = form.querySelector('input[name="' + group + '"]:checked');
        if (!mode) return;
        var val = mode.value; // existing | new
        var prefix = group.replace('_mode', ''); // farm | block | crop
        ['existing', 'new'].forEach(function (m) {
            var panel = form.querySelector('[data-mode="' + prefix + '-' + m + '"]');
            if (!panel) return;
            var on = (m === val);
            panel.classList.toggle('is-active', on);
            panel.querySelectorAll('input, select, textarea').forEach(function (el) {
                el.disabled = !on;
            });
        });
    }

    form.querySelectorAll('input[name$="_mode"]').forEach(function (r) {
        r.addEventListener('change', function () {
            applyMode(r.name);
            if (r.name === 'farm_mode') { refreshBlockOptions(); }
            if (r.name === 'crop_mode') { recalcHarvest(); prefillBudget(); }
            buildSummary();
        });
    });

    // ── Block options depend on the chosen farm ─────────────────
    var farmSel = document.getElementById('existing_farm_id');
    var blockSel = document.getElementById('existing_block_id');
    var blockEmpty = form.querySelector('[data-block-empty]');
    var blockExistingToggle = form.querySelector('input[name="block_mode"][value="existing"]');

    function refreshBlockOptions() {
        if (!blockSel) return;
        var farmMode = form.querySelector('input[name="farm_mode"]:checked');
        var farmId = (farmMode && farmMode.value === 'existing' && farmSel) ? farmSel.value : '';
        var visible = 0;
        Array.prototype.forEach.call(blockSel.options, function (opt) {
            if (!opt.value) return; // placeholder
            var match = farmId && opt.getAttribute('data-farm-id') === farmId;
            opt.hidden = !match;
            if (match) visible++; else if (opt.selected) blockSel.value = '';
        });
        // A brand-new farm (or none picked) has no existing blocks → force "new".
        var canUseExisting = visible > 0;
        if (blockExistingToggle) blockExistingToggle.disabled = !canUseExisting;
        if (!canUseExisting) {
            var newToggle = form.querySelector('input[name="block_mode"][value="new"]');
            if (newToggle) { newToggle.checked = true; applyMode('block_mode'); }
        }
        if (blockEmpty) blockEmpty.style.display = (!canUseExisting && farmId) ? 'block' : 'none';
    }
    if (farmSel) farmSel.addEventListener('change', refreshBlockOptions);

    // ── Expected-harvest auto-calc ──────────────────────────────
    var cropSel = document.getElementById('existing_crop_id');
    var cropDays = document.getElementById('crop_days_to_maturity');
    var planting = document.getElementById('planting_date');
    var harvest = document.getElementById('expected_harvest_date');
    var harvestTouched = {{ old('expected_harvest_date') ? 'true' : 'false' }};
    if (harvest) harvest.addEventListener('input', function () { harvestTouched = true; });

    function maturityDays() {
        var cropMode = form.querySelector('input[name="crop_mode"]:checked');
        if (cropMode && cropMode.value === 'existing' && cropSel) {
            var opt = cropSel.options[cropSel.selectedIndex];
            var d = opt ? parseInt(opt.getAttribute('data-maturity'), 10) : NaN;
            return isNaN(d) ? null : d;
        }
        if (cropDays) { var n = parseInt(cropDays.value, 10); return isNaN(n) ? null : n; }
        return null;
    }
    function recalcHarvest() {
        var days = maturityDays();
        if (!harvestTouched && planting && planting.value && days && harvest) {
            var d = new Date(planting.value + 'T00:00:00');
            d.setDate(d.getDate() + days);
            harvest.value = d.toISOString().slice(0, 10);
        }
    }
    if (cropSel) cropSel.addEventListener('change', function () { recalcHarvest(); prefillBudget(); buildSummary(); });
    if (cropDays) cropDays.addEventListener('input', recalcHarvest);
    if (planting) planting.addEventListener('change', recalcHarvest);
    ['existing_farm_id', 'existing_block_id', 'farm_name', 'block_name', 'crop_name'].forEach(function (id) {
        var el = document.getElementById(id);
        if (el) el.addEventListener('input', buildSummary);
    });

    // ── Variety options narrow to the chosen crop ───────────────
    var VARIETIES_BY_CROP = @json($varietiesByCrop);
    var cropNameInput = document.getElementById('crop_name');
    var varietyField = form.querySelector('[data-variety-field]');
    var varietyMenu = varietyField ? varietyField.querySelector('[data-combobox-menu]') : null;
    var varietyFullOptions = varietyMenu ? varietyMenu.innerHTML : '';

    function rebuildVarieties() {
        if (!varietyMenu) return;
        var crop = cropNameInput && cropNameInput.value ? cropNameInput.value.trim().toLowerCase() : '';
        var list = null;
        Object.keys(VARIETIES_BY_CROP).forEach(function (k) {
            if (k.toLowerCase() === crop) { list = VARIETIES_BY_CROP[k]; }
        });
        // Unknown / free-text crop → keep the full list rather than over-restrict.
        if (!list) { varietyMenu.innerHTML = varietyFullOptions; return; }
        varietyMenu.innerHTML = '';
        list.forEach(function (v) {
            var div = document.createElement('div');
            div.className = 'combobox-option';
            div.setAttribute('role', 'option');
            div.setAttribute('data-value', v);
            div.textContent = v;
            varietyMenu.appendChild(div);
        });
    }
    if (cropNameInput) {
        cropNameInput.addEventListener('input', rebuildVarieties);
        cropNameInput.addEventListener('change', rebuildVarieties);
    }
    rebuildVarieties();

    // ── Review summary on the final step ────────────────────────
    function textForMode(prefix, existingSel, newFieldId, label) {
        var mode = form.querySelector('input[name="' + prefix + '_mode"]:checked');
        if (!mode) return '—';
        if (mode.value === 'existing') {
            var sel = document.getElementById(existingSel);
            return (sel && sel.selectedIndex > 0) ? sel.options[sel.selectedIndex].text : '(none selected)';
        }
        var f = document.getElementById(newFieldId);
        return (f && f.value) ? f.value + ' (new)' : '(new — unnamed)';
    }
    function buildSummary() {
        var el = document.getElementById('wizSummary');
        if (!el) return;
        el.innerHTML =
            'Farm: <strong>' + textForMode('farm', 'existing_farm_id', 'farm_name') + '</strong><br>' +
            'Block: <strong>' + textForMode('block', 'existing_block_id', 'block_name') + '</strong><br>' +
            'Crop: <strong>' + textForMode('crop', 'existing_crop_id', 'crop_name') + '</strong>';
    }

    // ── Seasonal budget: prefill from the crop's template, live total ───
    var budgetInputs = Array.prototype.slice.call(form.querySelectorAll('.wiz-budget'));
    var budgetTouched = {{ (old('labour_budget') || old('input_budget') || old('irrigation_budget') || old('overhead_budget')) ? 'true' : 'false' }};
    budgetInputs.forEach(function (el) {
        el.addEventListener('input', function () { budgetTouched = true; updateBudgetTotal(); });
    });

    function valOf(name) { var el = fieldByName(name); return el ? el.value : ''; }
    function cropBudgetDefaults() {
        var mode = form.querySelector('input[name="crop_mode"]:checked');
        if (mode && mode.value === 'existing' && cropSel) {
            var opt = cropSel.options[cropSel.selectedIndex];
            if (!opt) return null;
            return { labour_budget: opt.getAttribute('data-labour'), input_budget: opt.getAttribute('data-input'),
                     irrigation_budget: opt.getAttribute('data-irrigation'), overhead_budget: opt.getAttribute('data-overhead') };
        }
        return { labour_budget: valOf('crop_default_labour_budget'), input_budget: valOf('crop_default_input_budget'),
                 irrigation_budget: valOf('crop_default_irrigation_budget'), overhead_budget: valOf('crop_default_overhead_budget') };
    }
    function prefillBudget() {
        if (budgetTouched) return; // never clobber what the user typed
        var d = cropBudgetDefaults();
        if (!d) return;
        Object.keys(d).forEach(function (id) {
            var el = document.getElementById(id);
            if (el && (!el.value || el.value === '0') && d[id] && parseFloat(d[id]) > 0) { el.value = parseFloat(d[id]); }
        });
    }
    function updateBudgetTotal() {
        var total = budgetInputs.reduce(function (sum, el) { var n = parseFloat(el.value); return sum + (isNaN(n) ? 0 : n); }, 0);
        var out = document.getElementById('wizBudgetTotal');
        if (out) out.innerHTML = 'Total budget: <strong>KES ' + total.toLocaleString() + '</strong>';
    }

    // ── Per-step validation (only enabled + visible required fields) ─
    var required = {
        1: { existing: ['existing_farm_id'], new: ['farm_name', 'farm_location', 'farm_size_acres'] },
        2: { existing: ['existing_block_id'], new: ['block_name', 'block_size_acres'] },
        3: { existing: ['existing_crop_id'], new: ['crop_name', 'crop_type'] },
        4: { always: ['season_name'] }
    };
    function fieldByName(name) { return form.querySelector('[name="' + name + '"]'); }
    function validateStep(step) {
        if (step === 5) {
            var total = budgetInputs.reduce(function (sum, el) { var n = parseFloat(el.value); return sum + (isNaN(n) ? 0 : n); }, 0);
            var bad = !(total > 0);
            budgetInputs.forEach(function (el) { el.classList.toggle('wiz-invalid', bad); });
            if (bad && budgetInputs[0]) budgetInputs[0].focus();
            return !bad;
        }
        var rules = required[step];
        var names = [];
        if (rules.always) names = rules.always;
        else {
            var mode = form.querySelector('input[name="' + ({1:'farm',2:'block',3:'crop'}[step]) + '_mode"]:checked');
            names = (mode && rules[mode.value]) ? rules[mode.value] : [];
        }
        var ok = true, firstBad = null;
        names.forEach(function (n) {
            var el = fieldByName(n);
            if (!el) return;
            var empty = !el.value || !el.value.trim();
            el.classList.toggle('wiz-invalid', empty);
            if (empty && ok) { ok = false; firstBad = el; }
        });
        if (firstBad) firstBad.focus();
        return ok;
    }

    // ── Submit: validate every section, jump to the first that fails ────
    form.addEventListener('submit', function (e) {
        for (var s = 1; s <= totalSteps; s++) {
            if (!validateStep(s)) {
                e.preventDefault();
                var panel = form.querySelector('.wiz-panel[data-panel="' + s + '"]');
                if (panel) panel.scrollIntoView({ behavior: 'smooth', block: 'center' });
                return;
            }
        }
    });

    // Init — all sections are visible at once, so prime every derived field.
    ['farm_mode', 'block_mode', 'crop_mode'].forEach(applyMode);
    refreshBlockOptions();
    buildSummary();
    prefillBudget();
    updateBudgetTotal();
});
</script>
@endsection
