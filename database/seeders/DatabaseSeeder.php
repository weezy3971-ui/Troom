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
        $owner = User::where('email', 'info@trooms.house')->first();

        if ($owner) {
            $password = env('SEED_ADMIN_PASSWORD');
            if ($password) {
                $owner->update(['password' => Hash::make($password)]);
                $this->command->info("Owner {$owner->email} already exists — password synced to SEED_ADMIN_PASSWORD.");
            } else {
                $this->command->info("Owner {$owner->email} already exists — leaving password untouched.");
            }
        } else {
            $password = env('SEED_ADMIN_PASSWORD');

            if (! $password) {
                if (app()->isProduction()) {
                    throw new \RuntimeException('SEED_ADMIN_PASSWORD must be set before seeding the owner account in production.');
                }
                $password = str()->random(20);
            }

            $owner = User::create([
                'name' => 'Admin',
                'email' => 'info@trooms.house',
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

        // ---- Pre-approved team members — awaiting self-registration with
        // their own chosen password, same as the owner had to approve them
        // manually for on the original database. ----
        foreach ([
            ['email' => 'matthew@trooms.house', 'role' => 'owner'],
            ['email' => 'maryanne.nduati@trooms.house', 'role' => 'owner'],
            ['email' => 'albert.maera@trooms.house', 'role' => 'horticulture_manager'],
            ['email' => 'george.mwangi@trooms.house', 'role' => 'sales_officer'],
        ] as $invite) {
            ApprovedEmail::firstOrCreate(
                ['email' => $invite['email']],
                ['role' => $invite['role'], 'invited_by' => $owner->id]
            );
        }

        $this->call(StableSeeder::class);
    }
}
