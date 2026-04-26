<?php

namespace App\Models;

use App\Notifications\ResetPasswordNotification;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    protected $guard_name = 'web';

    protected $fillable = [
        'name',
        'email',
        'password',
        'google_id',
        'avatar',
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
        return $this->hasMany(JobDescription::class, 'user_id');
    }

    // One user has many audit logs
    public function auditLogs()
    {
        return $this->hasMany(AuditLog::class);
    }

    public function resumes()
    {
        return $this->hasMany(Resume::class, 'uploaded_by');
    }

    // DELETE these two methods below — Spatie handles both automatically:
    //
    // public function roles() { ... }   → Spatie provides this via HasRoles
    // public function hasRole() { ... } → Spatie provides this via HasRoles

    // “When this user needs a password reset email, use my custom notification class.”
    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new ResetPasswordNotification($token));
    }
}
