<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'role', 'is_active'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * All assignable roles, mapped to human-readable labels.
     */
    public const ROLES = [
        'owner' => 'Owner (super-admin)',
        'md' => 'Managing Director',
        'horticulture_manager' => 'Horticulture Manager',
        'agronomist' => 'Agronomist',
        'farm_supervisor' => 'Farm Supervisor',
        'finance_officer' => 'Finance Officer',
        'sales_officer' => 'Sales Officer',
        'storekeeper' => 'Storekeeper',
        'quality_officer' => 'Quality Officer',
        'packhouse_supervisor' => 'Packhouse Supervisor',
        'driver' => 'Driver',
        'stable_manager' => 'Stable Manager',
    ];

    /**
     * Human-readable label for this user's role.
     */
    public function roleLabel(): string
    {
        return self::ROLES[$this->role] ?? ucwords(str_replace('_', ' ', (string) $this->role));
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    /**
     * The owner is a super-admin with unrestricted access.
     */
    public function isOwner(): bool
    {
        return $this->role === 'owner';
    }

    /**
     * Activity records attributed to this user.
     */
    public function activityLogs()
    {
        return $this->hasMany(ActivityLog::class);
    }
}
