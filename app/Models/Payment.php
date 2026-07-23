<?php

namespace App\Models;

use App\Services\SmsService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Money received, and the receipt issued for it.
 *
 * A payment is written once and never edited: correcting one means voiding it
 * and recording a fresh payment, so a receipt number that has been handed to a
 * customer always refers to the same amount. `amount` and `method` are
 * therefore fillable only at creation — see PaymentController, which offers no
 * update route at all.
 */
class Payment extends Model
{
    public const METHODS = [
        'cash',
        'mpesa',
        'bank_transfer',
        'cheque',
        'other',
    ];

    public const METHOD_LABELS = [
        'cash' => 'Cash',
        'mpesa' => 'M-Pesa',
        'bank_transfer' => 'Bank Transfer',
        'cheque' => 'Cheque',
        'other' => 'Other',
    ];

    /** Prefix for the receipt series. Horse rides run their own RCP- series. */
    private const RECEIPT_PREFIX = 'RCT-';

    protected $fillable = [
        'payable_type',
        'payable_id',
        'customer_id',
        'method',
        'amount',
        'paid_at',
        'reference',
        'payer_phone',
        'notes',
        'received_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'paid_at' => 'date',
        'voided_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        // The receipt number is the row id, zero-padded, so the series is
        // gapless by construction and needs no counter to keep in sync. The id
        // only exists after the insert, hence the two steps: a throwaway unique
        // placeholder satisfies the unique index for the instant in between.
        static::creating(function (Payment $payment) {
            if (empty($payment->receipt_number)) {
                $payment->receipt_number = 'PENDING-'.uniqid();
            }
        });

        static::created(function (Payment $payment) {
            if (str_starts_with($payment->receipt_number, 'PENDING-')) {
                $payment->receipt_number = self::RECEIPT_PREFIX
                    .str_pad((string) $payment->id, 6, '0', STR_PAD_LEFT);
                $payment->saveQuietly();
            }
        });
    }

    public function payable(): MorphTo
    {
        return $this->morphTo();
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function receivedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    public function voidedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'voided_by');
    }

    /** Voided payments stay on the record but count for nothing. */
    public function scopeActive($query)
    {
        return $query->whereNull('voided_at');
    }

    public function isVoided(): bool
    {
        return $this->voided_at !== null;
    }

    public function setPayerPhoneAttribute(?string $value): void
    {
        $this->attributes['payer_phone'] = SmsService::normalizePhone($value);
    }

    public function methodLabel(): string
    {
        // ucwords alone renders "mpesa" as "Mpesa", which is wrong on a
        // document a customer keeps.
        return self::METHOD_LABELS[$this->method]
            ?? ucwords(str_replace('_', ' ', $this->method));
    }
}
