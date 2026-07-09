<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KpiSnapshot extends Model
{
    protected $fillable = [
        'snapshot_date',
        'key',
        'value',
        'unit',
        'meta',
    ];

    protected $casts = [
        'snapshot_date' => 'date',
        'value' => 'decimal:2',
        'meta' => 'array',
    ];
}
