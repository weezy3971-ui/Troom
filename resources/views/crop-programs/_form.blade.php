<div class="form-grid">
    <div class="form-group">
        <label class="form-label" for="crop_id">Crop *</label>
        <select id="crop_id" name="crop_id" class="form-select" required>
            <option value="">— Select crop —</option>
            @foreach($crops as $crop)
                <option value="{{ $crop->id }}" {{ old('crop_id', $program?->crop_id) == $crop->id ? 'selected' : '' }}>{{ $crop->name }}@if($crop->variety) — {{ $crop->variety }}@endif</option>
            @endforeach
        </select>
        @error('crop_id') <p class="form-error">{{ $message }}</p> @enderror
    </div>
    <div class="form-group">
        <label class="form-label" for="name">Program Name *</label>
        <input type="text" id="name" name="name" value="{{ old('name', $program?->name) }}" class="form-input" placeholder="e.g. French Bean standard protocol" required>
        @error('name') <p class="form-error">{{ $message }}</p> @enderror
    </div>
    <div class="form-group" style="grid-column: 1 / -1;">
        <label class="form-label" for="description">Description</label>
        <input type="text" id="description" name="description" value="{{ old('description', $program?->description) }}" class="form-input">
    </div>
    <div class="form-group">
        <label class="form-label" for="is_active">Status</label>
        <select id="is_active" name="is_active" class="form-select">
            <option value="1" {{ old('is_active', $program?->is_active ?? true) ? 'selected' : '' }}>Active (used for scheduling)</option>
            <option value="0" {{ !old('is_active', $program?->is_active ?? true) ? 'selected' : '' }}>Inactive</option>
        </select>
    </div>
</div>
