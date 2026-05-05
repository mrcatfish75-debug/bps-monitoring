<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /*
    |--------------------------------------------------------------------------
    | Role Constants
    |--------------------------------------------------------------------------
    | Pakai constant supaya role tidak typo di controller/model lain.
    |--------------------------------------------------------------------------
    */
    public const ROLE_ADMIN = 'admin';
    public const ROLE_KEPALA = 'kepala';
    public const ROLE_KETUA = 'ketua';
    public const ROLE_ANGGOTA = 'anggota';

    /*
    |--------------------------------------------------------------------------
    | Mass Assignable Attributes
    |--------------------------------------------------------------------------
    | IMPORTANT:
    | - plain_password sengaja DIHAPUS dari fillable.
    | - Jangan simpan password plaintext di database.
    | - Password tetap boleh diisi lewat "password", dan akan otomatis di-hash
    |   karena casts() memakai 'password' => 'hashed'.
    |--------------------------------------------------------------------------
    */
    protected $fillable = [
        'name',
        'nip',
        'email',
        'password',
        'role',
        'team_id',
        'is_default_password',
    ];

    /*
    |--------------------------------------------------------------------------
    | Hidden Attributes
    |--------------------------------------------------------------------------
    | Semua field sensitif wajib disembunyikan dari array/json response.
    |--------------------------------------------------------------------------
    */
    protected $hidden = [
        'password',
        'plain_password',
        'remember_token',
    ];

    /*
    |--------------------------------------------------------------------------
    | Attribute Casts
    |--------------------------------------------------------------------------
    | password => hashed membuat password otomatis di-hash saat disimpan.
    |--------------------------------------------------------------------------
    */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_default_password' => 'boolean',
            'team_id' => 'integer',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function teams()
    {
        return $this->belongsToMany(Team::class, 'team_members');
    }

    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Role Helpers
    |--------------------------------------------------------------------------
    */

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function isKepala(): bool
    {
        return $this->role === self::ROLE_KEPALA;
    }

    public function isKetua(): bool
    {
        return $this->role === self::ROLE_KETUA;
    }

    public function isAnggota(): bool
    {
        return $this->role === self::ROLE_ANGGOTA;
    }

    public function isOperationalUser(): bool
    {
        return in_array($this->role, [
            self::ROLE_ANGGOTA,
            self::ROLE_KETUA,
        ], true);
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    public function scopeOperational($query)
    {
        return $query->whereIn('role', [
            self::ROLE_ANGGOTA,
            self::ROLE_KETUA,
        ]);
    }

    public function scopeTeamAssignable($query)
    {
        return $query->whereIn('role', [
            self::ROLE_KETUA,
            self::ROLE_ANGGOTA,
        ]);
    }

    public function scopeProjectAssignable($query)
    {
        return $query->whereIn('role', [
            self::ROLE_ANGGOTA,
            self::ROLE_KETUA,
        ]);
    }
}