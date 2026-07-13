<div class="form-grid">
    <div class="form-group">
        <label class="form-label" for="name">Name *</label>
        <input type="text" id="name" name="name" value="{{ old('name', $guide?->name) }}" class="form-input" required>
        @error('name') <p class="form-error">{{ $message }}</p> @enderror
    </div>
    <div class="form-group">
        <label class="form-label" for="phone">Phone</label>
        <input type="text" id="phone" name="phone" value="{{ old('phone', $guide?->phone) }}" class="form-input">
    </div>
    <div class="form-group">
        <label class="form-label" for="is_active">Status</label>
        <select id="is_active" name="is_active" class="form-select">
            <option value="1" {{ old('is_active', $guide?->is_active ?? true) ? 'selected' : '' }}>Active</option>
            <option value="0" {{ !old('is_active', $guide?->is_active ?? true) ? 'selected' : '' }}>Inactive</option>
        </select>
    </div>
</div>
