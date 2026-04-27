<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Iku extends Model
{
    protected $fillable = [
        'name',
        'year',
        'satuan',
        'target'
    ];

    public function rkKetuas()
    {
        return $this->hasMany(RkKetua::class);
    }

    // Progress dihitung dari RK Anggota yang sudah approved
    public function getProgressAttribute()
    {
        $rkAnggotas = $this->rkKetuas
            ->flatMap->projects
            ->flatMap->rkAnggotas;

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