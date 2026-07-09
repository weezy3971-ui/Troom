<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ChartOfAccount extends Model
{
    protected $table = 'chart_of_accounts';

    protected $fillable = [
        'code',
        'name',
        'type',
    ];

    public function ledgerEntries(): HasMany
    {
        return $this->hasMany(LedgerEntry::class, 'account_id');
    }

    public function balance(): float
    {
        $debit = (float) $this->ledgerEntries()->sum('debit');
        $credit = (float) $this->ledgerEntries()->sum('credit');

        // Assets and expenses are debit-normal; the rest are credit-normal.
        return in_array($this->type, ['asset', 'expense'])
            ? $debit - $credit
            : $credit - $debit;
    }
}
