<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Iku extends Model
{
    protected $fillable = [
        'code',
        'name',
        'description',
        'year',
        'satuan',
        'target',
    ];

    protected $appends = [
        'progress',
        'progress_label',
    ];

    public function rkKetuas()
    {
        return $this->hasMany(RkKetua::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Progress IKU
    |--------------------------------------------------------------------------
    | Flow final:
    | IKU -> RK Ketua -> Project -> RK Anggota
    |
    | Daily Task tidak masuk perhitungan progress utama.
    |
    | Progress IKU dihitung dari rata-rata progress semua RK Ketua
    | yang berada di bawah IKU ini.
    |--------------------------------------------------------------------------
    */
    public function getProgressAttribute(): int
    {
        $this->loadMissing('rkKetuas.projects.rkAnggotas.ikis');

        $totalRkKetua = $this->rkKetuas->count();

        if ($totalRkKetua === 0) {
            return 0;
        }

        return (int) round(
            $this->rkKetuas->avg(fn ($rkKetua) => $rkKetua->progress)
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

    public function getRkKetuaCountAttribute(): int
    {
        $this->loadMissing('rkKetuas');

        return $this->rkKetuas->count();
    }

    public function getProjectCountAttribute(): int
    {
        $this->loadMissing('rkKetuas.projects');

        return $this->rkKetuas
            ->flatMap(fn ($rkKetua) => $rkKetua->projects)
            ->count();
    }

    public function getRkAnggotaCountAttribute(): int
    {
        $this->loadMissing('rkKetuas.projects.rkAnggotas.ikis');

        return $this->rkKetuas
            ->flatMap(fn ($rkKetua) => $rkKetua->projects)
            ->flatMap(fn ($project) => $project->rkAnggotas)
            ->count();
    }

    public function getApprovedRkAnggotaCountAttribute(): int
    {
        $this->loadMissing('rkKetuas.projects.rkAnggotas.ikis');

        return $this->rkKetuas
            ->flatMap(fn ($rkKetua) => $rkKetua->projects)
            ->flatMap(fn ($project) => $project->rkAnggotas)
            ->filter(fn ($rk) => $rk->is_completed)
            ->count();
    }
}