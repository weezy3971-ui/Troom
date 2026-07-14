<div class="form-grid">
    <div class="form-group">
        <label class="form-label" for="name">Name *</label>
        <input type="text" id="name" name="name" value="{{ old('name', $worker?->name) }}" class="form-input" required>
        @error('name') <p class="form-error">{{ $message }}</p> @enderror
    </div>
    <div class="form-group">
        <label class="form-label" for="worker_type">Worker Type *</label>
        <select id="worker_type" name="worker_type" class="form-select" required>
            <option value="casual" {{ old('worker_type', $worker?->worker_type ?? 'casual') === 'casual' ? 'selected' : '' }}>Casual (target / piece-rate)</option>
            <option value="permanent" {{ old('worker_type', $worker?->worker_type) === 'permanent' ? 'selected' : '' }}>Permanent (time-based)</option>
        </select>
        @error('worker_type') <p class="form-error">{{ $message }}</p> @enderror
    </div>
    <div class="form-group">
        <label class="form-label" for="national_id">National ID</label>
        <input type="text" id="national_id" name="national_id" value="{{ old('national_id', $worker?->national_id) }}" class="form-input">
        @error('national_id') <p class="form-error">{{ $message }}</p> @enderror
    </div>
    <div class="form-group">
        <label class="form-label" for="employee_no">Employee No.</label>
        <input type="text" id="employee_no" name="employee_no" value="{{ old('employee_no', $worker?->employee_no) }}" class="form-input">
        @error('employee_no') <p class="form-error">{{ $message }}</p> @enderror
    </div>
    <div class="form-group">
        <label class="form-label" for="phone">Phone</label>
        <input type="text" id="phone" name="phone" value="{{ old('phone', $worker?->phone) }}" class="form-input">
    </div>
    <div class="form-group">
        <label class="form-label" for="pay_phone">Pay Phone (M-Pesa)</label>
        <input type="text" id="pay_phone" name="pay_phone" value="{{ old('pay_phone', $worker?->pay_phone) }}" class="form-input" placeholder="Optional — reserved for future pay-out">
        @error('pay_phone') <p class="form-error">{{ $message }}</p> @enderror
    </div>
    <div class="form-group">
        <label class="form-label" for="default_rate">Default Rate (KES/hour) *</label>
        <input type="number" step="0.01" min="0" id="default_rate" name="default_rate" value="{{ old('default_rate', $worker?->default_rate ?? 0) }}" class="form-input" required>
    </div>
    <div class="form-group">
        <label class="form-label" for="is_active">Status</label>
        <select id="is_active" name="is_active" class="form-select">
            <option value="1" {{ old('is_active', $worker?->is_active ?? true) ? 'selected' : '' }}>Active</option>
            <option value="0" {{ !old('is_active', $worker?->is_active ?? true) ? 'selected' : '' }}>Inactive</option>
        </select>
    </div>
</div>
