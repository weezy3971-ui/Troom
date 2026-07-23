<?php

namespace App\Models;

use App\Services\SmsService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Someone the farm pays: a supplier, a service provider, a transporter.
 *
 * `phone` is the M-Pesa destination for a B2C disbursement, so it is stored
 * normalised (2547XXXXXXXX) rather than as typed — a payout to a mistyped
 * number is not recoverable.
 */
class Vendor extends Model
{
    public const TYPES = [
        'supplier',
        'service_provider',
        'transporter',
        'landlord',
        'other',
    ];

    protected $fillable = [
        'name',
        'type',
        'phone',
        'email',
        'kra_pin',
        'notes',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }

    /**
     * Normalise on write so every read is already a valid MSISDN — an
     * unparseable number is stored null rather than kept in a form no
     * payment API would accept.
     */
    public function setPhoneAttribute(?string $value): void
    {
        $this->attributes['phone'] = SmsService::normalizePhone($value);
    }

    /** Whether this vendor can actually receive an M-Pesa payout. */
    public function isPayable(): bool
    {
        return $this->is_active && ! empty($this->phone);
    }

    public function typeLabel(): string
    {
        return ucwords(str_replace('_', ' ', $this->type));
    }
}
