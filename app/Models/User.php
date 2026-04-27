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

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
        protected $fillable = [
        'name',
        'nip',
        'email',
        'password',
        'role',
        'team_id',
        'plain_password',
        'is_default_password'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

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
        ];
    }

    public function teams()
    {
        return $this->belongsToMany(Team::class, 'team_members');
    }

        public function team()
    {
        return $this->belongsTo(Team::class);
    }

    public function isKetua()
    {
        return in_array($this->role, ['ketua', 'ketua_tim']);
    }

    public function isKepala()
    {
        return in_array($this->role, ['kepala', 'kepala_bps']);
    }

    public function isAnggota()
    {
        return $this->role === 'anggota';
    }

    public function isAdmin()
    {
        return $this->role === 'admin';
    }

    public function scopeTeamAssignable($query)
    {
        return $query->whereIn('role', ['ketua_tim', 'anggota']);
    }

    public function notifications()
    {
        return $this->hasMany(\App\Models\Notification::class);
    }
    }
