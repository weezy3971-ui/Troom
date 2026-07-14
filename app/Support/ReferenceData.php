<?php

namespace App\Support;

use App\Models\Block;
use App\Models\Crop;
use App\Models\HarvestBatch;
use App\Models\InventoryItem;
use App\Models\PackhouseLot;

/**
 * Curated suggestion lists for combobox inputs (native <datalist>).
 *
 * Each list is a curated set of common values UNIONed with the distinct values
 * already stored in the database, so anything a user typed once shows up as a
 * suggestion next time — while the input still accepts free text.
 */
class ReferenceData
{
    /** Common crop varieties grown in the Kenyan horticulture market, keyed by crop name. */
    public const VARIETIES = [
        'French Bean'        => ['Samantha', 'Serengeti', 'Amy', 'Julia', 'Teresa', 'Star 2054', 'Boston', 'Vernandon', 'Monel', 'Paulista', 'Claudia'],
        'Capsicum'           => ['California Wonder', 'Yolo Wonder', 'Commandant F1', 'Admiral F1', 'Green Gold', 'Maxibell', 'Golden Gift'],
        'Collard Greens'     => ['Thousand Headed', 'Marrow Stem', 'Georgia', 'Mfalme F1', 'Sukuma Siku', 'Southern Georgia'],
        'Spinach'            => ['Fordhook Giant', 'New Zealand', 'Ford Hook', 'Viroflay', 'King of Denmark'],
        'African Nightshade' => ['Giant Nightshade', 'Olevolosi', 'Managu', 'Nduma', 'Ex-Hai'],
    ];

    private const CROP_TYPES = ['Vegetable', 'Fruit', 'Flower', 'Herb', 'Legume', 'Cereal', 'Tuber', 'Fodder'];

    private const SOIL_TYPES = ['Clay loam', 'Sandy loam', 'Loam', 'Sandy', 'Clay', 'Silt', 'Volcanic', 'Red earth', 'Black cotton'];

    private const PACKAGING_TYPES = ['4kg carton', '4kg export carton', '5kg vented carton', '2kg punnet', '10kg crate', '250g pack', '500g pack', 'Loose'];

    private const INVENTORY_CATEGORIES = ['fertilizer', 'chemical', 'seed', 'packaging', 'fuel', 'ppe', 'tools', 'spare_parts', 'other'];

    private const UNITS = ['kg', 'litre', 'unit', 'bag', 'crate', 'carton', 'gram', 'ml', 'piece', 'roll'];

    private const QUALITY_GRADES = ['Grade A', 'Grade B', 'Grade C', 'Reject'];

    /**
     * Merge a curated list with distinct DB values, drop blanks, unique, sorted.
     *
     * @param  array<int,string>  $curated
     * @param  array<int,string|null>  $stored
     * @return array<int,string>
     */
    private static function merge(array $curated, array $stored): array
    {
        return collect($curated)
            ->merge($stored)
            ->map(fn ($v) => is_string($v) ? trim($v) : $v)
            ->filter()
            ->unique()
            ->sort(SORT_NATURAL | SORT_FLAG_CASE)
            ->values()
            ->all();
    }

    /** All known varieties (flat), curated ∪ stored. */
    public static function varieties(): array
    {
        $curated = collect(self::VARIETIES)->flatten()->all();

        return self::merge($curated, Crop::query()->pluck('variety')->all());
    }

    /** Curated variety map keyed by crop name (for per-crop filtering in JS). */
    public static function varietiesByCrop(): array
    {
        $map = self::VARIETIES;

        // Fold any stored varieties into their crop's list.
        Crop::query()->select('name', 'variety')->get()
            ->each(function ($crop) use (&$map) {
                if ($crop->variety) {
                    $map[$crop->name] = collect($map[$crop->name] ?? [])
                        ->push($crop->variety)->unique()->values()->all();
                }
            });

        return $map;
    }

    public static function cropNames(): array
    {
        return self::merge(array_keys(self::VARIETIES), Crop::query()->pluck('name')->all());
    }

    public static function cropTypes(): array
    {
        return self::merge(self::CROP_TYPES, Crop::query()->pluck('crop_type')->all());
    }

    public static function soilTypes(): array
    {
        return self::merge(self::SOIL_TYPES, Block::query()->pluck('soil_type')->all());
    }

    public static function packagingTypes(): array
    {
        return self::merge(self::PACKAGING_TYPES, PackhouseLot::query()->pluck('packaging_type')->all());
    }

    public static function inventoryCategories(): array
    {
        return self::merge(self::INVENTORY_CATEGORIES, InventoryItem::query()->pluck('category')->all());
    }

    public static function units(): array
    {
        return self::merge(self::UNITS, InventoryItem::query()->pluck('unit')->all());
    }

    public static function qualityGrades(): array
    {
        return self::merge(self::QUALITY_GRADES, HarvestBatch::query()->pluck('quality_grade')->all());
    }
}
