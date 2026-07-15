<?php

namespace Database\Seeders;

use App\Models\ApprovedEmail;
use App\Models\ChartOfAccount;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // ---- Owner ----
        $password = env('SEED_ADMIN_PASSWORD', str()->random(20));

        $owner = User::create([
            'name' => 'Farm Admin',
            'email' => 'admin@trooms.co.ke',
            'role' => 'owner',
            'password' => Hash::make($password),
        ]);

        if (! env('SEED_ADMIN_PASSWORD')) {
            $this->command->warn("Generated password for {$owner->email}: {$password}");
        }

        // ---- Chart of Accounts ----
        ChartOfAccount::insert([
            ['code' => '1000', 'name' => 'Cash & Bank',           'type' => 'asset',   'created_at' => now(), 'updated_at' => now()],
            ['code' => '4000', 'name' => 'Produce Sales',         'type' => 'income',  'created_at' => now(), 'updated_at' => now()],
            ['code' => '5000', 'name' => 'Farm Operating Costs',  'type' => 'expense', 'created_at' => now(), 'updated_at' => now()],
        ]);

        // ---- Approved email for owner ----
        ApprovedEmail::create([
            'email' => $owner->email,
            'role'  => $owner->role,
            'invited_by' => $owner->id,
            'registered_at' => now(),
        ]);

        $this->call(StableSeeder::class);
        $this->call(ToolAssetSeeder::class);
    }
}
