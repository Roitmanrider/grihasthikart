<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'role', 'assigned_store_id', 'staff_roles', 'additional_permissions', 'denied_permissions', 'staff_active', 'staff_approved_at', 'staff_approved_by'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

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
            'staff_roles' => 'array',
            'additional_permissions' => 'array',
            'denied_permissions' => 'array',
            'staff_active' => 'boolean',
            'staff_approved_at' => 'datetime',
        ];
    }

    public function assignedStore()
    {
        return $this->belongsTo(StockLocation::class, 'assigned_store_id');
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === 'SUPER_ADMIN';
    }

    public function isStoreManager(): bool
    {
        return $this->role === 'STORE_MANAGER';
    }

    public function isCartFollowUpEmployee(): bool
    {
        return $this->role === 'CART_FOLLOW_UP_EMPLOYEE'
            || in_array('CART_FOLLOW_UP_EMPLOYEE', $this->staff_roles ?? [], true);
    }

    public function hasStaffRole(string $role): bool
    {
        return $this->role === $role || in_array($role, $this->staff_roles ?? [], true);
    }

    public function staffNotifications()
    {
        return $this->hasMany(StaffNotification::class, 'recipient_user_id');
    }
}
