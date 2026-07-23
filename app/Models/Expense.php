<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\Storage;

class Expense extends Model
{
    /**
     * Free-form field costs, selected from a controlled list so reporting
     * stays consistent even though the underlying spend is very varied
     * (tools, fuel, casual labour paid in cash, fines, etc).
     */
    public const CATEGORIES = [
        'tools_equipment',
        'fuel',
        'transport',
        'labour_casual',
        'repairs_maintenance',
        'fines_permits',
        'utilities',
        'other',
    ];

    public const PAYMENT_MODES = [
        'cash',
        'mpesa',
        'bank_transfer',
        'cheque',
        'card',
        'other',
    ];

    protected $fillable = [
        'category',
        'vendor_id',
        'voucher_number',
        'amount',
        'payment_mode',
        'expense_date',
        'description',
        'receipt_path',
        'farm_id',
        'block_id',
        'land_preparation_id',
        'logged_by',
    ];

    protected $casts = [
        'expense_date' => 'date',
        'amount' => 'decimal:2',
    ];

    public function farm(): BelongsTo
    {
        return $this->belongsTo(Farm::class);
    }

    /**
     * The land-preparation round this cost paid for, when it is prep spend.
     * Without it, land-prep costs land on the books attached to nothing but a
     * block, which is what left them outside any stage of the planting.
     */
    public function landPreparation(): BelongsTo
    {
        return $this->belongsTo(LandPreparation::class);
    }

    public function block(): BelongsTo
    {
        return $this->belongsTo(Block::class);
    }

    /**
     * Who was paid. Optional, because plenty of spend has no vendor behind it
     * (fuel bought at a pump, a casual's lunch) — but an expense that will be
     * settled by M-Pesa B2C needs one, since the payout goes to their number.
     */
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function logger(): BelongsTo
    {
        return $this->belongsTo(User::class, 'logged_by');
    }

    public function mpesaTransactions(): MorphMany
    {
        return $this->morphMany(MpesaTransaction::class, 'payable');
    }

    public function isDisbursed(): bool
    {
        return $this->mpesaTransactions()->where('status', 'success')->exists();
    }

    public function receiptUrl(): ?string
    {
        return $this->receipt_path ? Storage::disk('public')->url($this->receipt_path) : null;
    }

    /**
     * Expenses carry receipts/amounts that shouldn't be quietly rewritten
     * long after the fact. Once a full day has passed since it was logged,
     * it can no longer be edited or deleted — only viewed.
     */
    public function isLocked(): bool
    {
        return $this->created_at->addDay()->isPast();
    }

    /**
     * Trooms's own proof of paying the vendor — the outbound mirror of the
     * customer receipt on Payment. Requires a vendor: a voucher without a
     * payee is meaningless, since its whole purpose is proof of who was paid.
     * Idempotent, like SalesOrder::issueInvoice() — re-issuing keeps the
     * original number rather than minting a second one.
     */
    public function issueVoucher(): void
    {
        if (empty($this->voucher_number)) {
            $this->voucher_number = 'PV-'.str_pad((string) $this->id, 6, '0', STR_PAD_LEFT);
            $this->save();
        }
    }

    public function isVouchered(): bool
    {
        return ! empty($this->voucher_number);
    }
}
