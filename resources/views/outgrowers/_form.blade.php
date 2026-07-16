<div class="form-grid">
    <div class="form-group">
        <label class="form-label" for="name">Name *</label>
        <input type="text" id="name" name="name" value="{{ old('name', $outgrower?->name) }}" class="form-input" required>
        @error('name') <p class="form-error">{{ $message }}</p> @enderror
    </div>
    <div class="form-group">
        <label class="form-label" for="phone">Phone</label>
        <input type="text" id="phone" name="phone" value="{{ old('phone', $outgrower?->phone) }}" class="form-input">
    </div>
    <div class="form-group">
        <label class="form-label" for="location">Location</label>
        <input type="text" id="location" name="location" value="{{ old('location', $outgrower?->location) }}" class="form-input">
    </div>
    <div class="form-group">
        <label class="form-label" for="is_active">Status</label>
        <select id="is_active" name="is_active" class="form-select">
            <option value="1" {{ old('is_active', $outgrower?->is_active ?? true) ? 'selected' : '' }}>Active</option>
            <option value="0" {{ !old('is_active', $outgrower?->is_active ?? true) ? 'selected' : '' }}>Inactive</option>
        </select>
    </div>
    <div class="form-group">
        <label class="form-label" for="specialization">Specialization</label>
        <input type="text" id="specialization" name="specialization" value="{{ old('specialization', $outgrower?->specialization) }}" class="form-input" placeholder="e.g. French beans, snow peas">
    </div>
    <div class="form-group">
        <label class="form-label" for="reliability_rating">Reliability Rating</label>
        <select id="reliability_rating" name="reliability_rating" class="form-select">
            <option value="" {{ old('reliability_rating', $outgrower?->reliability_rating) === null ? 'selected' : '' }}>Not rated</option>
            @for($i = 5; $i >= 1; $i--)
                <option value="{{ $i }}" {{ (int) old('reliability_rating', $outgrower?->reliability_rating) === $i ? 'selected' : '' }}>{{ str_repeat('★', $i) }}{{ str_repeat('☆', 5 - $i) }} ({{ $i }})</option>
            @endfor
        </select>
    </div>
    <div class="form-group" style="grid-column: 1 / -1;">
        <label class="form-label" for="notes">Notes</label>
        <textarea id="notes" name="notes" class="form-input" rows="3" style="resize: vertical;">{{ old('notes', $outgrower?->notes) }}</textarea>
    </div>
</div>
