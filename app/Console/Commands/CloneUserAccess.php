<?php

namespace App\Console\Commands;

use App\Models\ApprovedEmail;
use App\Models\User;
use App\Support\ActivityLogger;
use Illuminate\Console\Command;

/**
 * Provision a new email with the same role and password as an existing
 * account — used to hand owner access to a new address without forcing a
 * fresh self-registration or password reset. Safe to re-run: it only
 * updates the target user's role/password if they already exist.
 */
class CloneUserAccess extends Command
{
    protected $signature = 'users:clone-access
        {email : The new email to grant access to}
        {--from=admin@trooms.co.ke : The existing account to copy role and password from}
        {--name= : Display name for the new account (defaults to the source account\'s name)}';

    protected $description = 'Grant a new email the same role and password as an existing user';

    public function handle(): int
    {
        $targetEmail = strtolower(trim($this->argument('email')));
        $sourceEmail = strtolower(trim($this->option('from')));

        $source = User::whereRaw('LOWER(email) = ?', [$sourceEmail])->first();

        if (! $source) {
            $this->error("No existing user found with email {$sourceEmail}.");

            return self::FAILURE;
        }

        $target = User::updateOrCreate(
            ['email' => $targetEmail],
            [
                'name' => $this->option('name') ?: $source->name,
                'password' => $source->password,
                'role' => $source->role,
                'is_active' => true,
            ]
        );

        ApprovedEmail::updateOrCreate(
            ['email' => $targetEmail],
            [
                'role' => $source->role,
                'invited_by' => $source->id,
                'registered_at' => now(),
            ]
        );

        ActivityLogger::log('cloned_access', $target, "Granted {$targetEmail} the same access as {$sourceEmail}");

        $this->info("{$targetEmail} now has {$source->roleLabel()} access with {$sourceEmail}'s current password.");

        return self::SUCCESS;
    }
}
