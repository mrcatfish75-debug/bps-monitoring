<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    protected $fillable = [
        'name',
        'rk_ketua_id',
        'team_id',
        'leader_id',
        'start_date',
        'end_date',
        'status'
    ];



    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    public function leader()
    {
        return $this->belongsTo(User::class, 'leader_id');
    }

    public function members()
    {
        return $this->belongsToMany(User::class, 'project_members');
    }

        public function rkKetua()
    {
        return $this->belongsTo(RkKetua::class);
    }

    public function rkAnggotas()
    {
        return $this->hasMany(RkAnggota::class);
    }

    public function getProgressAttribute()
{
    $total = $this->rkAnggotas->count();

    if ($total === 0) {
        return 0;
    }

    $approved = $this->rkAnggotas
        ->where('status', RkAnggota::STATUS_APPROVED)
        ->count();

    return round(($approved / $total) * 100);
}

}
