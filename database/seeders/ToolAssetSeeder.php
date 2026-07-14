<?php

namespace Database\Seeders;

use App\Models\Asset;
use App\Models\Farm;
use Illuminate\Database\Seeder;

class ToolAssetSeeder extends Seeder
{
    /**
     * Small hand tools that get checked out to workers (jembe, panga, etc.),
     * so the asset check-out register has something to track out of the box.
     */
    public function run(): void
    {
        $farm = Farm::orderBy('id')->first();
        if (! $farm) {
            return;
        }

        $tools = ['Jembe (Hoe) #1', 'Jembe (Hoe) #2', 'Panga (Machete) #1', 'Wheelbarrow #1', 'Knapsack Sprayer #1', 'Watering Can #1'];

        foreach ($tools as $name) {
            Asset::firstOrCreate(
                ['name' => $name],
                ['farm_id' => $farm->id, 'type' => 'equipment', 'status' => 'operational']
            );
        }
    }
}
