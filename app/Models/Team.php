<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Models\RkKetua;
use App\Models\Project;

class Team extends Model
{
    protected $fillable = [
        'name',
        'leader_id',
    ];

    /*
    |--------------------------------------------------------------------------
    | Leader / Ketua Tim
    |--------------------------------------------------------------------------
    | Team hanya punya satu ketua.
    */
    public function leader()
    {
        return $this->belongsTo(User::class, 'leader_id');
    }

    /*
    |--------------------------------------------------------------------------
    | RK Ketua
    |--------------------------------------------------------------------------
    | Satu team bisa punya banyak RK Ketua.
    */
    public function rkKetuas()
    {
        return $this->hasMany(RkKetua::class, 'team_id');
    }

    /*
    |--------------------------------------------------------------------------
    | Projects
    |--------------------------------------------------------------------------
    | Satu team bisa punya banyak project.
    | Project tetap mengikuti team_id dari RK Ketua.
    */
    public function projects()
    {
        return $this->hasMany(Project::class, 'team_id');
    }

    /*
    |--------------------------------------------------------------------------
    | Legacy Members
    |--------------------------------------------------------------------------
    | Relasi ini hanya untuk kompatibilitas data lama.
    | Jangan dipakai lagi sebagai sumber anggota kerja.
    | Anggota kerja final berasal dari project_members.
    */
    public function members()
    {
        return $this->belongsToMany(User::class, 'team_members');
    }
}