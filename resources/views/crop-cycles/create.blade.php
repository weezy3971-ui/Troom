@extends('layouts.app')
@section('title', 'New Crop Cycle')

@section('content')
<x-crumb-nav />
<div class="page-header"><h1 class="page-title">New Crop Cycle</h1></div>

<div class="card" style="max-width: 700px;">
    <form action="{{ route('crop-cycles.store') }}" method="POST">
        @csrf
        <div class="form-grid">
            <div class="form-group">
                <label class="form-label" for="block_id">Block *</label>
                <select id="block_id" name="block_id" class="form-select" required>
                    <option value="">Select a block</option>
                    @foreach($blocks as $block)
                        <option value="{{ $block->id }}" {{ old('block_id') == $block->id ? 'selected' : '' }}>{{ $block->name }} ({{ $block->farm->name }})</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label class="form-label" for="crop_id">Crop *</label>
                <select id="crop_id" name="crop_id" class="form-select" required>
                    <option value="">Select a crop</option>
                    @foreach($crops as $crop)
                        <option value="{{ $crop->id }}" data-maturity="{{ $crop->days_to_maturity }}" {{ old('crop_id') == $crop->id ? 'selected' : '' }}>{{ $crop->name }} {{ $crop->variety ? '('.$crop->variety.')' : '' }}</option>
                    @endforeach
                </select>
                <p id="maturity-hint" style="font-size:12px; color:var(--text-muted); margin-top:4px; display:none;"></p>
            </div>
            <div class="form-group">
                <label class="form-label" for="crop_cycle_template_id">Cycle Template *</label>
                <select id="crop_cycle_template_id" name="crop_cycle_template_id" class="form-select" required>
                    <option value="">Select a template</option>
                    @foreach($templates as $template)
                        <option value="{{ $template->id }}" data-crop="{{ $template->crop_id }}" data-days="{{ $template->total_cycle_days }}" {{ old('crop_cycle_template_id') == $template->id ? 'selected' : '' }}>{{ $template->label() }}</option>
                    @endforeach
                </select>
                <p style="font-size:12px; color:var(--text-muted); margin-top:4px;">
                    The plan this cycle follows. Its schedule points become dated tasks once the cycle is active.
                    @if($templates->isEmpty())
                        <a href="{{ route('crop-cycle-templates.create') }}">Create one first.</a>
                    @endif
                </p>
            </div>
            <div class="form-group">
                <label class="form-label" for="season_name">Season Name *</label>
                <input type="text" id="season_name" name="season_name" value="{{ old('season_name') }}" class="form-input" required placeholder="e.g. Q3 2026, Long Rains 2026">
            </div>
            <div class="form-group">
                <label class="form-label" for="planting_date">Planting Date</label>
                <input type="date" id="planting_date" name="planting_date" value="{{ old('planting_date') }}" class="form-input">
            </div>
            <div class="form-group">
                <label class="form-label" for="expected_harvest_date">Expected Harvest Date</label>
                <input type="date" id="expected_harvest_date" name="expected_harvest_date" value="{{ old('expected_harvest_date') }}" class="form-input">
                <p style="font-size:12px; color:var(--text-muted); margin-top:4px;">Auto-calculated from the crop's maturity when you set a planting date — you can override it.</p>
            </div>
        </div>
        <div style="display: flex; gap: 12px; margin-top: 8px;">
            <button type="submit" class="btn btn-primary">Create Crop Cycle</button>
            <a href="{{ route('crop-cycles.index') }}" class="btn btn-ghost">Cancel</a>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var cropSel = document.getElementById('crop_id');
    var tplSel = document.getElementById('crop_cycle_template_id');
    var planting = document.getElementById('planting_date');
    var harvest = document.getElementById('expected_harvest_date');
    var hint = document.getElementById('maturity-hint');
    var harvestTouched = false;

    harvest.addEventListener('input', function () { harvestTouched = true; });

    // The template's cycle length is the plan of record, so it wins over the
    // crop's nominal maturity when both are known.
    function cycleDays() {
        var tpl = tplSel.options[tplSel.selectedIndex];
        var t = tpl ? parseInt(tpl.getAttribute('data-days'), 10) : NaN;
        if (!isNaN(t)) return t;

        var opt = cropSel.options[cropSel.selectedIndex];
        var d = opt ? parseInt(opt.getAttribute('data-maturity'), 10) : NaN;
        return isNaN(d) ? null : d;
    }

    // Narrow the template list to the chosen crop, keeping crop-agnostic
    // templates (no crop_id) always available.
    function filterTemplates() {
        var crop = cropSel.value;
        var cleared = false;

        Array.prototype.forEach.call(tplSel.options, function (opt) {
            if (!opt.value) return;
            var owner = opt.getAttribute('data-crop');
            var show = !crop || !owner || owner === crop;
            opt.hidden = !show;
            if (!show && opt.selected) { cleared = true; }
        });

        if (cleared) { tplSel.value = ''; }
    }

    function recalc() {
        var days = cycleDays();
        if (days) {
            hint.style.display = 'block';
            hint.textContent = 'Cycle runs about ' + days + ' days.';
        } else {
            hint.style.display = 'none';
        }
        // Only auto-fill if the user hasn't manually edited the harvest date.
        if (!harvestTouched && planting.value && days) {
            var d = new Date(planting.value + 'T00:00:00');
            d.setDate(d.getDate() + days);
            harvest.value = d.toISOString().slice(0, 10);
        }
    }

    cropSel.addEventListener('change', function () { filterTemplates(); recalc(); });
    tplSel.addEventListener('change', recalc);
    planting.addEventListener('change', recalc);
    filterTemplates();
    recalc();
});
</script>
@endsection
