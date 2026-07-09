<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Farm extends Model
{
    protected $fillable = [
        'name',
        'location',
        'size_acres',
        'latitude',
        'longitude',
    ];

    protected $casts = [
        'size_acres' => 'decimal:2',
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
    ];

    public function blocks(): HasMany
    {
        return $this->hasMany(Block::class);
    }

    public function assets(): HasMany
    {
        return $this->hasMany(Asset::class);
    }
}
