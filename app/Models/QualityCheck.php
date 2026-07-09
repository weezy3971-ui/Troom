<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QualityCheck extends Model
{
    protected $fillable = [
        'packhouse_lot_id',
        'check_date',
        'parameters',
        'result',
        'inspector_id',
        'photo_path',
    ];

    protected $casts = [
        'check_date' => 'date',
        'parameters' => 'array',
    ];

    public function packhouseLot(): BelongsTo
    {
        return $this->belongsTo(PackhouseLot::class);
    }

    public function inspector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'inspector_id');
    }
}
