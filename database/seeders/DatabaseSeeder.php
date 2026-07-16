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
        $owner = User::where('email', 'admin@trooms.co.ke')->first();

        if ($owner) {
            $this->command->info("Owner {$owner->email} already exists — leaving password untouched.");
        } else {
            $password = env('SEED_ADMIN_PASSWORD');

            if (! $password) {
                if (app()->isProduction()) {
                    throw new \RuntimeException('SEED_ADMIN_PASSWORD must be set before seeding the owner account in production.');
                }
                $password = str()->random(20);
            }

            $owner = User::create([
                'name' => 'Farm Admin',
                'email' => 'admin@trooms.co.ke',
                'role' => 'owner',
                'password' => Hash::make($password),
            ]);

            if (! env('SEED_ADMIN_PASSWORD')) {
                $this->command->warn("Generated password for {$owner->email}: {$password}");
            }
        }

        // ---- Chart of Accounts ----
        foreach ([
            ['code' => '1000', 'name' => 'Cash & Bank',           'type' => 'asset'],
            ['code' => '4000', 'name' => 'Produce Sales',         'type' => 'income'],
            ['code' => '5000', 'name' => 'Farm Operating Costs',  'type' => 'expense'],
        ] as $account) {
            ChartOfAccount::firstOrCreate(['code' => $account['code']], $account);
        }

        // ---- Approved email for owner ----
        ApprovedEmail::firstOrCreate(
            ['email' => $owner->email],
            [
                'role' => $owner->role,
                'invited_by' => $owner->id,
                'registered_at' => now(),
            ]
        );

        $this->call(StableSeeder::class);
    }
}
