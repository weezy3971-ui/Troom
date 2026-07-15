<div class="alert alert-info" style="margin-bottom: 16px;">
    <strong>Projects are one-off work</strong> — construction, land refining, training and the like.
    Recurring field operations (planting, weeding, spraying) belong on the crop cycle, not here.
</div>
<div class="form-grid">
    <div class="form-group">
        <label class="form-label" for="name">Project Name *</label>
        <input type="text" id="name" name="name" value="{{ old('name', $project?->name) }}" class="form-input" required>
        @error('name') <p class="form-error">{{ $message }}</p> @enderror
    </div>
    <div class="form-group">
        <label class="form-label" for="project_type">Project Type *</label>
        <select id="project_type" name="project_type" class="form-select" required>
            @foreach(['construction' => 'Construction', 'land_prep' => 'Land refining / prep', 'training' => 'Training', 'maintenance' => 'Maintenance', 'other' => 'Other'] as $val => $lbl)
                <option value="{{ $val }}" {{ old('project_type', $project?->project_type ?? 'construction') === $val ? 'selected' : '' }}>{{ $lbl }}</option>
            @endforeach
        </select>
        @error('project_type') <p class="form-error">{{ $message }}</p> @enderror
    </div>
    <div class="form-group">
        <label class="form-label" for="code">Code *</label>
        <input type="text" id="code" name="code" value="{{ old('code', $project?->code) }}" class="form-input" placeholder="e.g. PRJ-001" required>
        @error('code') <p class="form-error">{{ $message }}</p> @enderror
    </div>
    <div class="form-group">
        <label class="form-label" for="status">Status *</label>
        <select id="status" name="status" class="form-select" required>
            @foreach(['planned' => 'Planned', 'active' => 'Active', 'completed' => 'Completed', 'cancelled' => 'Cancelled'] as $val => $lbl)
                <option value="{{ $val }}" {{ old('status', $project?->status ?? 'planned') === $val ? 'selected' : '' }}>{{ $lbl }}</option>
            @endforeach
        </select>
    </div>
    <div class="form-group">
        <label class="form-label" for="budget">Budget (KES) *</label>
        <input type="number" step="0.01" min="0" id="budget" name="budget" value="{{ old('budget', $project?->budget ?? 0) }}" class="form-input" required>
    </div>
    <div class="form-group">
        <label class="form-label" for="farm_id">Farm</label>
        <select id="farm_id" name="farm_id" class="form-select">
            <option value="">— None —</option>
            @foreach($farms as $farm)
                <option value="{{ $farm->id }}" {{ old('farm_id', $project?->farm_id) == $farm->id ? 'selected' : '' }}>{{ $farm->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="form-group">
        <label class="form-label" for="block_id">Block</label>
        <select id="block_id" name="block_id" class="form-select">
            <option value="">— None —</option>
            @foreach($blocks as $block)
                <option value="{{ $block->id }}" {{ old('block_id', $project?->block_id) == $block->id ? 'selected' : '' }}>{{ $block->name }} — {{ $block->farm->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="form-group">
        <label class="form-label" for="crop_cycle_id">Crop Cycle</label>
        <select id="crop_cycle_id" name="crop_cycle_id" class="form-select">
            <option value="">— None —</option>
            @foreach($cropCycles as $cycle)
                <option value="{{ $cycle->id }}" {{ old('crop_cycle_id', $project?->crop_cycle_id) == $cycle->id ? 'selected' : '' }}>{{ $cycle->season_name }} — {{ $cycle->block?->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="form-group">
        <label class="form-label" for="start_date">Start Date</label>
        <input type="date" id="start_date" name="start_date" value="{{ old('start_date', $project?->start_date?->toDateString()) }}" class="form-input">
    </div>
    <div class="form-group">
        <label class="form-label" for="end_date">End Date</label>
        <input type="date" id="end_date" name="end_date" value="{{ old('end_date', $project?->end_date?->toDateString()) }}" class="form-input">
        @error('end_date') <p class="form-error">{{ $message }}</p> @enderror
    </div>
</div>
<div class="form-group" style="margin-top: 16px;">
    <label class="form-label" for="description">Description</label>
    <textarea id="description" name="description" class="form-textarea" rows="3">{{ old('description', $project?->description) }}</textarea>
</div>
