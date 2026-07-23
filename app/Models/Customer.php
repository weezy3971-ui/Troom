<?php

namespace App\Models;

use App\Services\SmsService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    protected $fillable = [
        'name',
        'contact',
        'phone',
        'contract_terms',
        'price_list',
    ];

    public function salesOrders(): HasMany
    {
        return $this->hasMany(SalesOrder::class);
    }

    /**
     * Stored normalised (2547XXXXXXXX) because this is the number an STK Push
     * prompt is sent to, not just something to read off the screen.
     */
    public function setPhoneAttribute(?string $value): void
    {
        $this->attributes['phone'] = SmsService::normalizePhone($value);
    }
}
