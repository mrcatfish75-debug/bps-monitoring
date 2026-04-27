<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RkKetua extends Model
{
    protected $fillable = [
        'iku_id',
        'team_id',
        'user_id',
        'description'
    ];

    public function iku()
    {
        return $this->belongsTo(Iku::class);
    }

    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    public function ketua()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function projects()
    {
        return $this->hasMany(Project::class);
    }

    // 🔥 PROGRESS AUTO
    public function getProgressAttribute()
{
    $rkAnggotas = $this->projects->flatMap->rkAnggotas;

    $total = $rkAnggotas->count();

    if ($total === 0) {
        return 0;
    }

    $approved = $rkAnggotas
        ->where('status', RkAnggota::STATUS_APPROVED)
        ->count();

    return round(($approved / $total) * 100);
}
}