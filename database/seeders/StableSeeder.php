<?php

namespace Database\Seeders;

use App\Models\Guide;
use App\Models\Horse;
use Illuminate\Database\Seeder;

class StableSeeder extends Seeder
{
    public function run(): void
    {
        $horses = [
            ['name' => 'Thunder',  'breed' => 'Arabian',        'rest_minutes' => 120],
            ['name' => 'Bella',    'breed' => 'Thoroughbred',   'rest_minutes' => 120],
            ['name' => 'Shadow',   'breed' => 'Friesian',       'rest_minutes' => 90],
            ['name' => 'Duke',     'breed' => 'Quarter Horse',  'rest_minutes' => 150],
            ['name' => 'Misty',    'breed' => 'Connemara Pony', 'rest_minutes' => 60],
        ];
        foreach ($horses as $h) {
            Horse::firstOrCreate(['name' => $h['name']], $h + ['is_active' => true]);
        }

        $guides = [
            ['name' => 'Sam Kariuki', 'phone' => '0712 000111'],
            ['name' => 'Faith Otieno', 'phone' => '0723 000222'],
            ['name' => 'Brian Muli', 'phone' => '0734 000333'],
        ];
        foreach ($guides as $g) {
            Guide::firstOrCreate(['name' => $g['name']], $g + ['is_active' => true]);
        }
    }
}
