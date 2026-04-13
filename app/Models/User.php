<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
        ];
    }

    // One user has many job descriptions
    public function jobDescriptions()
    {
        return $this->hasMany(JobDescription::class, 'created_by');
    }

    // One user has many audit logs
    public function auditLogs()
    {
        return $this->hasMany(AuditLog::class);
    }

    // Many-to-many with roles through user_roles
    public function roles()
    {
        return $this->belongsToMany(Role::class, 'user_roles');
    }

    // Helper: check if user has a specific role
    public function hasRole(string $role): bool
    {
        return $this->roles()->where('name', $role)->exists();
    }
}
