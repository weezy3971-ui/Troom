<?php

namespace Database\Seeders;

use App\Models\Worker;
use Illuminate\Database\Seeder;

class WorkerSeeder extends Seeder
{
    /**
     * Field labourers who take on project tasks and check out tools.
     */
    public function run(): void
    {
        $workers = [
            ['name' => 'Peter Kamau',   'phone' => '0711 100200', 'default_rate' => 120],
            ['name' => 'Mary Wanjiku',  'phone' => '0722 200300', 'default_rate' => 120],
            ['name' => 'John Otieno',   'phone' => '0733 300400', 'default_rate' => 130],
            ['name' => 'Grace Achieng', 'phone' => '0700 400500', 'default_rate' => 120],
            ['name' => 'Daniel Mutua',  'phone' => '0755 500600', 'default_rate' => 150],
            ['name' => 'Esther Nduta',  'phone' => '0788 600700', 'default_rate' => 120],
        ];

        foreach ($workers as $w) {
            Worker::firstOrCreate(['name' => $w['name']], $w + ['is_active' => true]);
        }
    }
}
