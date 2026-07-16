<?php

namespace Database\Seeders;

use App\Models\Outgrower;
use Illuminate\Database\Seeder;

class OutgrowerSeeder extends Seeder
{
    public function run(): void
    {
        $outgrowers = [
            ['name' => 'Peter Kamau',    'phone' => '0722 345 678', 'location' => 'Naivasha',  'notes' => 'Reliable supplier, grows French beans and snow peas',       'specialization' => 'French beans, snow peas',     'reliability_rating' => 5, 'is_active' => true],
            ['name' => 'Grace Wanjiku',  'phone' => '0733 456 789', 'location' => 'Timau',      'notes' => 'Specialises in baby corn and chillies',                     'specialization' => 'Baby corn, chillies',         'reliability_rating' => 4, 'is_active' => true],
            ['name' => 'Joseph Ochieng', 'phone' => '0711 567 890', 'location' => 'Nanyuki',    'notes' => 'Large-scale; can cover 500 kg+ shortfalls at short notice', 'specialization' => 'Mixed vegetables',            'reliability_rating' => 4, 'is_active' => true],
            ['name' => 'Mary Chebet',    'phone' => '0700 678 901', 'location' => 'Kericho',    'notes' => null,                                                        'specialization' => 'Herbs, spring onions',        'reliability_rating' => 3, 'is_active' => true],
            ['name' => 'Samuel Mutua',   'phone' => '0745 789 012', 'location' => 'Machakos',   'notes' => 'Seasonal — available Jan to June only',                     'specialization' => 'Tomatoes, capsicum',          'reliability_rating' => null, 'is_active' => false],
            ['name' => 'Alice Njeri',    'phone' => null,            'location' => 'Limuru',     'notes' => 'Small plot, good quality herbs',                             'specialization' => 'Coriander, rosemary, thyme', 'reliability_rating' => 5, 'is_active' => true],
        ];

        foreach ($outgrowers as $row) {
            Outgrower::updateOrCreate(['name' => $row['name']], $row);
        }
    }
}
