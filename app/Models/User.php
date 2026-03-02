<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'avatar',
        'birth_date',
        'is_active',
        'case_manager_id',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'birth_date'        => 'date',
        'is_active'         => 'boolean',
        'last_login_at'     => 'datetime',
    ];

    public function roles()
    {
        return $this->belongsToMany(Role::class);
    }

    public function hasRole(string $role): bool
    {
        return $this->roles()->where('name', $role)->exists();
    }

    public function caseManager()
    {
        return $this->belongsTo(User::class, 'case_manager_id');
    }

    public function hasAnyRole($roles): bool
    {
        if (is_string($roles)) {
            $roles = [$roles];
        }
        return $this->roles()->whereIn('name', (array) $roles)->exists();
    }

    public function isAdmin(): bool
    {
        return $this->hasRole('admin');
    }

    public function isCaseManager(): bool
    {
        return $this->hasRole('case_manager');
    }

    public function isClient(): bool
    {
        return $this->hasRole('client');
    }

    public function primaryRole(): ?string
    {
        return $this->roles->first()?->name;
    }


    /**
     * Los clientes asignados a este case manager (un case manager → muchos clientes)
     */
    public function clients()
    {
        return $this->hasMany(User::class, 'case_manager_id');
    }

    // ─── Relación Citas ────────────────────────────────────────

    public function appointmentsAsManager()
    {
        return $this->hasMany(Appointment::class, 'case_manager_id');
    }

    public function appointmentsAsClient()
    {
        return $this->hasMany(Appointment::class, 'client_id');
    }

    public function getAvatarUrlAttribute(): string
    {
        return asset('storage/' . $this->avatar);
    }
}
