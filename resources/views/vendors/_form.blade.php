@php $v = $vendor ?? null; @endphp

<div class="form-grid">
    <div class="form-group">
        <label class="form-label" for="name">Name *</label>
        <input type="text" id="name" name="name" value="{{ old('name', $v?->name) }}" class="form-input" required>
    </div>
    <div class="form-group">
        <label class="form-label" for="type">Type *</label>
        <select id="type" name="type" class="form-input" required>
            @foreach(\App\Models\Vendor::TYPES as $type)
                <option value="{{ $type }}" @selected(old('type', $v?->type) === $type)>
                    {{ ucwords(str_replace('_', ' ', $type)) }}
                </option>
            @endforeach
        </select>
    </div>
    <div class="form-group">
        <label class="form-label" for="phone">M-Pesa Phone</label>
        <input type="text" id="phone" name="phone" value="{{ old('phone', $v?->phone) }}" class="form-input" placeholder="0712 345 678">
        <p class="form-hint">Where B2C payouts are sent. Saved as 2547… — check it carefully, a payout to a wrong number cannot be recalled.</p>
    </div>
    <div class="form-group">
        <label class="form-label" for="email">Email</label>
        <input type="email" id="email" name="email" value="{{ old('email', $v?->email) }}" class="form-input">
    </div>
    <div class="form-group">
        <label class="form-label" for="kra_pin">KRA PIN</label>
        <input type="text" id="kra_pin" name="kra_pin" value="{{ old('kra_pin', $v?->kra_pin) }}" class="form-input" placeholder="P000000000X">
    </div>
</div>

<div class="form-group">
    <label class="form-label" for="notes">Notes</label>
    <textarea id="notes" name="notes" class="form-textarea">{{ old('notes', $v?->notes) }}</textarea>
</div>

<div class="form-group">
    <label class="form-label" style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
        <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $v?->is_active ?? true))>
        Active
    </label>
    <p class="form-hint">Inactive vendors stay on past expenses but cannot be paid.</p>
</div>
