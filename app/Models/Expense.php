<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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

    protected $fillable = [
        'category',
        'amount',
        'expense_date',
        'description',
        'farm_id',
        'block_id',
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

    public function block(): BelongsTo
    {
        return $this->belongsTo(Block::class);
    }

    public function logger(): BelongsTo
    {
        return $this->belongsTo(User::class, 'logged_by');
    }
}
