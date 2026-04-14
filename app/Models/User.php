<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

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

    // DELETE these two methods below — Spatie handles both automatically:
    //
    // public function roles() { ... }   → Spatie provides this via HasRoles
    // public function hasRole() { ... } → Spatie provides this via HasRoles
}
