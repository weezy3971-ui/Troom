<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * An external website the system takes crop information from.
 *
 * One row per source SITE (keyed on domain), not per cited page — removing a
 * source is a decision about a publisher, and `references` records the
 * individual pages we quote from it.
 *
 * Rows appear on their own: the register is synced from the citations in the
 * curated planner programs, so a source can't be in use without being listed.
 * `status` is therefore just in-use vs removed — a removed source is withheld
 * from the planner and stays behind only so the sync can't resurrect it.
 */
class InformationSource extends Model
{
    /** Categories: what the source is used FOR. */
    public const CATEGORIES = [
        'crop_cycle' => 'Crop Cycle & Planting Plans',
        'agronomy' => 'Agronomy, Pests & Diseases',
        'seed_variety' => 'Seed & Variety Data',
        'compliance' => 'Compliance, Export & Residue Limits',
        'soil_lab' => 'Soil & Laboratory Testing',
        'market' => 'Market & Price Information',
        'other' => 'Other',
    ];

    protected $fillable = [
        'name',
        'domain',
        'url',
        'category',
        'purpose',
        'status',
        'references',
        'removed_at',
        'removed_by',
        'added_by',
    ];

    protected $casts = [
        'references' => 'array',
        'removed_at' => 'datetime',
    ];

    public function removedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'removed_by');
    }

    public function addedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'added_by');
    }

    public function scopeInUse(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    public function scopeRemoved(Builder $query): Builder
    {
        return $query->where('status', 'removed');
    }

    public function isRemoved(): bool
    {
        return $this->status === 'removed';
    }

    /**
     * The bare host of a URL, lowercased and without `www.` — the key a source
     * is identified by, so http/https and deep links collapse together.
     */
    public static function domainFrom(string $url): string
    {
        $host = parse_url(trim($url), PHP_URL_HOST) ?: trim($url);

        return Str::lower(Str::before(preg_replace('#^www\.#i', '', trim($host, '/')), '/'));
    }

    /**
     * Whether a URL may still be shown. Unknown domains pass: the register is
     * synced from the code, so "not listed" means "not yet synced", not
     * "rejected" — only an explicit removal blocks a source.
     */
    public static function allows(string $url): bool
    {
        return ! static::query()
            ->removed()
            ->where('domain', static::domainFrom($url))
            ->exists();
    }

    public function categoryLabel(): string
    {
        return self::CATEGORIES[$this->category] ?? $this->category;
    }

    /** Pages we actually quote from this source. */
    public function citedPages(): array
    {
        return $this->references ?? [];
    }

    /** Whether the planner still cites this source in code. */
    public function isCitedInCode(): bool
    {
        return count($this->citedPages()) > 0;
    }
}
