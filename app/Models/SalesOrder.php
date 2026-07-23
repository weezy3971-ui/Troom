<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class SalesOrder extends Model
{
    protected $fillable = [
        'invoice_number',
        'customer_id',
        'crop_id',
        'order_date',
        'requested_quantity',
        'total_amount',
        'amount_paid',
        'payment_status',
        'delivered_quantity',
        'rejected_quantity',
        'returned_quantity',
        'amount_repaid',
        'status',
        'delivery_date',
    ];

    protected $casts = [
        'order_date' => 'date',
        'delivery_date' => 'date',
        'requested_quantity' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'amount_paid' => 'decimal:2',
        'delivered_quantity' => 'decimal:2',
        'rejected_quantity' => 'decimal:2',
        'returned_quantity' => 'decimal:2',
        'amount_repaid' => 'decimal:2',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function crop(): BelongsTo
    {
        return $this->belongsTo(Crop::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(SalesOrderLine::class);
    }

    public function dispatch(): HasOne
    {
        return $this->hasOne(Dispatch::class);
    }

    public function payments(): MorphMany
    {
        return $this->morphMany(Payment::class, 'payable');
    }

    /**
     * Turn the order into an invoice: freeze its line value as the amount owed
     * and give it a number the customer can quote when paying. Idempotent —
     * re-invoicing an order keeps its original number.
     */
    public function issueInvoice(): void
    {
        $this->total_amount = $this->orderValue();

        if (empty($this->invoice_number)) {
            $this->invoice_number = 'INV-'.str_pad((string) $this->id, 6, '0', STR_PAD_LEFT);
        }

        $this->save();
        $this->refreshPaymentStatus();
    }

    /**
     * Recompute what has been paid from the payments actually on record.
     * Derived rather than incremented, so a voided payment corrects the order
     * without anyone having to remember to subtract it.
     */
    public function refreshPaymentStatus(): void
    {
        $paid = (float) $this->payments()->active()->sum('amount');
        $total = (float) $this->total_amount;

        $this->amount_paid = $paid;
        $this->payment_status = match (true) {
            $paid <= 0 => 'unpaid',
            // Rounded to the cent: floating point should never leave an order
            // that has been settled in full sitting at "partial".
            round($paid, 2) >= round($total, 2) && $total > 0 => 'paid',
            default => 'partial',
        };

        $this->save();
    }

    /** What the customer still owes. Never negative — overpayment is not a debt. */
    public function balanceDue(): float
    {
        return max(0, (float) $this->total_amount - (float) $this->amount_paid);
    }

    public function isInvoiced(): bool
    {
        return ! empty($this->invoice_number);
    }

    /**
     * Total quality-passed quantity allocated to this order.
     */
    public function allocatedQuantity(): float
    {
        return (float) $this->lines->sum('quantity');
    }

    /**
     * Order value from its allocated lines.
     */
    public function orderValue(): float
    {
        return (float) $this->lines->sum(fn ($line) => $line->lineTotal());
    }

    /**
     * Business rule: an order_at_risk alert fires when a delivery date is
     * approaching (within 7 days) without sufficient quality-passed lot
     * quantity allocated.
     */
    public function isAtRisk(): bool
    {
        if (in_array($this->status, ['fulfilled', 'cancelled', 'dispatched'])) {
            return false;
        }

        if (! $this->delivery_date) {
            return false;
        }

        // "Approaching" means the delivery date is in the future but within 7 days.
        $approaching = $this->delivery_date->isFuture()
            && $this->delivery_date->isBefore(now()->addDays(7));

        $underAllocated = $this->allocatedQuantity() < (float) $this->requested_quantity;

        return $approaching && $underAllocated;
    }

    /**
     * Share of delivered produce that was rejected (0–1). Returns null when
     * nothing has been delivered yet.
     */
    public function rejectRate(): ?float
    {
        $delivered = (float) $this->delivered_quantity;

        if ($delivered <= 0) {
            return null;
        }

        return (float) $this->rejected_quantity / $delivered;
    }
}
