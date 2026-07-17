<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'phone', 'password', 'role', 'is_active'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * All assignable roles, mapped to human-readable labels.
     */
    public const ROLES = [
        'owner' => 'Admin',
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
     * Seniority order, lowest number = most senior. Drives who may edit,
     * deactivate, or view activity for whom: a role may never act on an
     * account whose role outranks (has a lower number than) its own.
     */
    public const ROLE_RANK = [
        'owner' => 0,
        'md' => 1,
        'horticulture_manager' => 2,
        'agronomist' => 3,
        'farm_supervisor' => 4,
        'finance_officer' => 5,
        'sales_officer' => 6,
        'storekeeper' => 7,
        'quality_officer' => 8,
        'packhouse_supervisor' => 9,
        'driver' => 10,
        'stable_manager' => 11,
    ];

    /**
     * This role's seniority rank (lower = more senior). Unknown roles rank
     * last, so they can never outrank a recognized role.
     */
    public function rank(): int
    {
        return self::ROLE_RANK[$this->role] ?? PHP_INT_MAX;
    }

    /**
     * Whether this user is senior to the given user (or role name) and so
     * may act on their account. A role never outranks itself.
     */
    public function outranks(User|string $other): bool
    {
        $otherRank = $other instanceof User
            ? $other->rank()
            : (self::ROLE_RANK[$other] ?? PHP_INT_MAX);

        return $this->rank() < $otherRank;
    }

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
