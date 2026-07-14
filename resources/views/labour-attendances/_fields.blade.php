@php $a = $attendance ?? null; @endphp
<div class="form-grid">
    <div class="form-group">
        <label class="form-label" for="attendance_date">Date *</label>
        <input type="date" id="attendance_date" name="attendance_date" value="{{ old('attendance_date', optional($a?->attendance_date)->toDateString() ?? now()->toDateString()) }}" class="form-input" required>
    </div>
    <div class="form-group">
        <label class="form-label" for="worker_id">Worker (roster)</label>
        <select id="worker_id" name="worker_id" class="form-select" data-worker-select>
            <option value="">— Ad-hoc / type name below —</option>
            @foreach($workers as $worker)
                <option value="{{ $worker->id }}" data-name="{{ $worker->name }}" {{ old('worker_id', $a?->worker_id) == $worker->id ? 'selected' : '' }}>{{ $worker->name }} ({{ ucfirst($worker->worker_type ?? 'casual') }})</option>
            @endforeach
        </select>
        @error('worker_id') <p class="form-error">{{ $message }}</p> @enderror
    </div>
    <div class="form-group">
        <label class="form-label" for="worker_name">Worker Name *</label>
        <input type="text" id="worker_name" name="worker_name" value="{{ old('worker_name', $a?->worker_name) }}" class="form-input" required>
        @error('worker_name') <p class="form-error">{{ $message }}</p> @enderror
    </div>
    <div class="form-group">
        <label class="form-label" for="task">Task *</label>
        <input type="text" id="task" name="task" value="{{ old('task', $a?->task) }}" class="form-input" placeholder="e.g. Weeding" required>
    </div>
    <div class="form-group">
        <label class="form-label" for="block_id">Block</label>
        <select id="block_id" name="block_id" class="form-select">
            <option value="">— None —</option>
            @foreach($blocks as $block)
                <option value="{{ $block->id }}" {{ old('block_id', $a?->block_id) == $block->id ? 'selected' : '' }}>{{ $block->name }} — {{ $block->farm->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="form-group">
        <label class="form-label" for="crop_cycle_id">Crop Cycle</label>
        <select id="crop_cycle_id" name="crop_cycle_id" class="form-select">
            <option value="">— None —</option>
            @foreach($cropCycles as $cycle)
                <option value="{{ $cycle->id }}" {{ old('crop_cycle_id', $a?->crop_cycle_id) == $cycle->id ? 'selected' : '' }}>{{ $cycle->season_name }} — {{ $cycle->block->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="form-group">
        <label class="form-label" for="pay_basis">Pay Basis *</label>
        <select id="pay_basis" name="pay_basis" class="form-select" data-pay-basis required>
            <option value="hourly" {{ old('pay_basis', $a?->pay_basis ?? 'hourly') === 'hourly' ? 'selected' : '' }}>Hourly (permanent staff)</option>
            <option value="target" {{ old('pay_basis', $a?->pay_basis) === 'target' ? 'selected' : '' }}>Target / piece-rate (casuals)</option>
        </select>
        @error('pay_basis') <p class="form-error">{{ $message }}</p> @enderror
    </div>
</div>

{{-- Hourly fields --}}
<div class="form-grid" data-basis-hourly>
    <div class="form-group">
        <label class="form-label" for="checked_in_at">Checked In</label>
        <input type="datetime-local" id="checked_in_at" name="checked_in_at" value="{{ old('checked_in_at', optional($a?->checked_in_at)->format('Y-m-d\TH:i')) }}" class="form-input">
        @error('checked_in_at') <p class="form-error">{{ $message }}</p> @enderror
    </div>
    <div class="form-group">
        <label class="form-label" for="checked_out_at">Checked Out</label>
        <input type="datetime-local" id="checked_out_at" name="checked_out_at" value="{{ old('checked_out_at', optional($a?->checked_out_at)->format('Y-m-d\TH:i')) }}" class="form-input">
        @error('checked_out_at') <p class="form-error">{{ $message }}</p> @enderror
    </div>
    <div class="form-group">
        <label class="form-label" for="hours_worked">Hours Worked</label>
        <input type="number" step="0.1" id="hours_worked" name="hours_worked" value="{{ old('hours_worked', $a?->hours_worked) }}" class="form-input" min="0" placeholder="Auto from check-in/out">
        <p class="page-subtitle" style="margin:6px 0 0;">Leave blank to compute from check-in/out, or enter hours directly.</p>
        @error('hours_worked') <p class="form-error">{{ $message }}</p> @enderror
    </div>
    <div class="form-group">
        <label class="form-label" for="rate">Rate (KES/hour) *</label>
        <input type="number" step="0.01" id="rate" name="rate" value="{{ old('rate', $a?->rate) }}" class="form-input" min="0">
        @error('rate') <p class="form-error">{{ $message }}</p> @enderror
    </div>
</div>

{{-- Target / piece-rate fields --}}
<div class="form-grid" data-basis-target hidden>
    <div class="form-group">
        <label class="form-label" for="target_unit">Target Unit *</label>
        <input type="text" id="target_unit" name="target_unit" value="{{ old('target_unit', $a?->target_unit) }}" class="form-input" placeholder="e.g. beds, crates, kg, lines">
        @error('target_unit') <p class="form-error">{{ $message }}</p> @enderror
    </div>
    <div class="form-group">
        <label class="form-label" for="target_qty">Target Qty</label>
        <input type="number" step="0.01" id="target_qty" name="target_qty" value="{{ old('target_qty', $a?->target_qty) }}" class="form-input" min="0" placeholder="Assigned (optional)">
    </div>
    <div class="form-group">
        <label class="form-label" for="qty_completed">Qty Completed *</label>
        <input type="number" step="0.01" id="qty_completed" name="qty_completed" value="{{ old('qty_completed', $a?->qty_completed) }}" class="form-input" min="0">
        @error('qty_completed') <p class="form-error">{{ $message }}</p> @enderror
    </div>
    <div class="form-group">
        <label class="form-label" for="rate_per_unit">Rate per Unit (KES) *</label>
        <input type="number" step="0.01" id="rate_per_unit" name="rate_per_unit" value="{{ old('rate_per_unit', $a?->rate_per_unit) }}" class="form-input" min="0" placeholder="e.g. 120 per bed">
        @error('rate_per_unit') <p class="form-error">{{ $message }}</p> @enderror
    </div>
</div>

<p class="page-subtitle" style="margin: 12px 0;">Cost is computed automatically — hours × rate for hourly, or qty completed × rate per unit for target work — and auto-allocated.</p>

<script>
    (function () {
        var basis = document.querySelector('[data-pay-basis]');
        var hourly = document.querySelector('[data-basis-hourly]');
        var target = document.querySelector('[data-basis-target]');
        var workerSelect = document.querySelector('[data-worker-select]');
        var nameInput = document.getElementById('worker_name');

        function toggle() {
            var isTarget = basis.value === 'target';
            hourly.hidden = isTarget;
            target.hidden = !isTarget;
        }
        if (basis) { basis.addEventListener('change', toggle); toggle(); }

        if (workerSelect && nameInput) {
            workerSelect.addEventListener('change', function () {
                var opt = workerSelect.options[workerSelect.selectedIndex];
                var name = opt ? opt.getAttribute('data-name') : '';
                if (name) nameInput.value = name;
            });
        }
    })();
</script>
