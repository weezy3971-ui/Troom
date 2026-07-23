@csrf
<div class="form-grid">
    <div class="form-group">
        <label class="form-label" for="crop_name">Crop Name *</label>
        <input type="text" id="crop_name" name="crop_name" class="form-input" required
               value="{{ old('crop_name', $template->crop_name ?? '') }}" placeholder="e.g. Tomato">
    </div>
    <div class="form-group">
        <label class="form-label" for="variety">Variety</label>
        <input type="text" id="variety" name="variety" class="form-input"
               value="{{ old('variety', $template->variety ?? '') }}" placeholder="e.g. Roma">
    </div>
    <div class="form-group">
        <label class="form-label" for="crop_id">Linked Crop</label>
        <select id="crop_id" name="crop_id" class="form-select">
            <option value="">Not linked to a specific crop</option>
            @foreach($crops as $crop)
                <option value="{{ $crop->id }}" {{ old('crop_id', $template->crop_id ?? '') == $crop->id ? 'selected' : '' }}>
                    {{ $crop->name }}{{ $crop->variety ? ' ('.$crop->variety.')' : '' }}
                </option>
            @endforeach
        </select>
        <p style="font-size:12px; color:var(--text-muted); margin-top:4px;">Linking narrows the template list when creating a cycle for that crop.</p>
    </div>
    <div class="form-group">
        <label class="form-label" for="total_cycle_days">Total Cycle Days *</label>
        <input type="number" id="total_cycle_days" name="total_cycle_days" class="form-input" required min="1" max="1000"
               value="{{ old('total_cycle_days', $template->total_cycle_days ?? '') }}" placeholder="e.g. 90">
        <p style="font-size:12px; color:var(--text-muted); margin-top:4px;">Planting to final harvest. Sets the expected harvest date on every cycle running this template.</p>
    </div>
    <div class="form-group" style="grid-column: 1 / -1;">
        <label class="form-label" for="description">Description</label>
        <textarea id="description" name="description" class="form-input" rows="3"
                  placeholder="Notes on the plan — soil, spacing, anything the agronomist should know.">{{ old('description', $template->description ?? '') }}</textarea>
    </div>
    <div class="form-group">
        <label class="form-label">
            <input type="checkbox" name="is_active" value="1" {{ old('is_active', $template->is_active ?? true) ? 'checked' : '' }}>
            Active — offered when starting a new cycle
        </label>
    </div>
</div>
