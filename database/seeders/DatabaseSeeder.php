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
            // M-Pesa is held separately from cash: the float is reconciled
            // against Safaricom's statement, not counted in the till.
            ['code' => '1100', 'name' => 'M-Pesa Float',          'type' => 'asset'],
            // Sales are earned on fulfilment and collected on payment. Without
            // a receivable in between, the two would both hit cash and every
            // sale would be counted twice.
            ['code' => '1200', 'name' => 'Accounts Receivable',   'type' => 'asset'],
            ['code' => '2000', 'name' => 'Accounts Payable',      'type' => 'liability'],
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

        // Team members are pre-approved through Settings → Users rather than
        // seeded: their names and addresses are personal data and do not
        // belong in source control.

        $this->call(StableSeeder::class);
    }
}
