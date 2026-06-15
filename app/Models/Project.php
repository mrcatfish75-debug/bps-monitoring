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
        'status',
    ];

    protected $appends = [
        'progress',
        'progress_label',
        'rk_anggota_count',
        'approved_rk_anggota_count',
        'completed_rk_anggota_count',
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

    /*
    |--------------------------------------------------------------------------
    | Progress Project
    |--------------------------------------------------------------------------
    | Flow final:
    | Project -> RK Anggota
    |
    | RK Anggota adalah unit pekerjaan.
    | Satu RK Anggota boleh dikerjakan bersama oleh beberapa anggota.
    |
    | Daily Task tidak masuk perhitungan progress utama.
    | Daily Task hanya untuk monitoring aktivitas.
    |
    | Progress Project dihitung dari:
    | RK Anggota approved / total RK Anggota dalam project * 100
    |--------------------------------------------------------------------------
    */
    public function getProgressAttribute(): int
    {
        $this->loadMissing('rkAnggotas.ikis');

        $totalRk = $this->rkAnggotas->count();

        if ($totalRk === 0) {
            return 0;
        }

        return (int) round(
            $this->rkAnggotas->avg(fn ($rk) => $rk->progress)
        );
    }

    public function getProgressLabelAttribute(): string
    {
        if ($this->progress <= 0) {
            return 'Belum Berjalan';
        }

        if ($this->progress >= 100) {
            return 'Selesai';
        }

        return 'Berjalan';
    }

    public function getRkAnggotaCountAttribute(): int
    {
        $this->loadMissing('rkAnggotas');

        return $this->rkAnggotas->count();
    }

    public function getApprovedRkAnggotaCountAttribute(): int
    {
        $this->loadMissing('rkAnggotas.ikis');

        return $this->rkAnggotas
            ->filter(fn ($rk) => $rk->is_completed)
            ->count();
    }

    public function getCompletedRkAnggotaCountAttribute(): int
    {
        return $this->approved_rk_anggota_count;
    }
}