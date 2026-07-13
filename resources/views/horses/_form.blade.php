<div class="form-grid">
    <div class="form-group">
        <label class="form-label" for="name">Name *</label>
        <input type="text" id="name" name="name" value="{{ old('name', $horse?->name) }}" class="form-input" required>
        @error('name') <p class="form-error">{{ $message }}</p> @enderror
    </div>
    <div class="form-group">
        <label class="form-label" for="breed">Breed</label>
        <input type="text" id="breed" name="breed" value="{{ old('breed', $horse?->breed) }}" class="form-input">
    </div>
    <div class="form-group">
        <label class="form-label" for="rest_minutes">Rest Period (minutes) *</label>
        <input type="number" min="0" max="1440" id="rest_minutes" name="rest_minutes" value="{{ old('rest_minutes', $horse?->rest_minutes ?? 120) }}" class="form-input" required>
        <p class="page-subtitle" style="margin-top: 4px;">Minimum rest after each ride before this horse can be assigned again. Default 120 (2 hrs).</p>
    </div>
    <div class="form-group">
        <label class="form-label" for="is_active">Status</label>
        <select id="is_active" name="is_active" class="form-select">
            <option value="1" {{ old('is_active', $horse?->is_active ?? true) ? 'selected' : '' }}>Active</option>
            <option value="0" {{ !old('is_active', $horse?->is_active ?? true) ? 'selected' : '' }}>Inactive</option>
        </select>
    </div>
</div>
<div class="form-group" style="margin-top: 16px;">
    <label class="form-label" for="notes">Notes</label>
    <textarea id="notes" name="notes" class="form-textarea" rows="2">{{ old('notes', $horse?->notes) }}</textarea>
</div>
