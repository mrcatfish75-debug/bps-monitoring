<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RkKetua extends Model
{
    protected $fillable = [
        'iku_id',
        'team_id',
        'user_id',
        'description',
        'target',
        'status',
    ];

    protected $appends = [
        'progress',
        'progress_label',
        'project_count',
        'rk_anggota_count',
        'approved_rk_anggota_count',
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

    /*
    |--------------------------------------------------------------------------
    | Progress RK Ketua
    |--------------------------------------------------------------------------
    | Flow final:
    | RK Ketua -> Project -> RK Anggota
    |
    | Daily Task tidak masuk progress utama.
    |
    | Progress RK Ketua dihitung dari rata-rata progress semua project
    | di bawah RK Ketua ini.
    |--------------------------------------------------------------------------
    */
    public function getProgressAttribute(): int
    {
        $this->loadMissing('projects.rkAnggotas.ikis');

        $totalProject = $this->projects->count();

        if ($totalProject === 0) {
            return 0;
        }

        return (int) round(
            $this->projects->avg(fn ($project) => $project->progress)
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

    public function getProjectCountAttribute(): int
    {
        $this->loadMissing('projects');

        return $this->projects->count();
    }

    public function getRkAnggotaCountAttribute(): int
    {
        $this->loadMissing('projects.rkAnggotas.ikis');

        return $this->projects
            ->flatMap(fn ($project) => $project->rkAnggotas)
            ->count();
    }

    public function getApprovedRkAnggotaCountAttribute(): int
    {
        $this->loadMissing('projects.rkAnggotas.ikis');

        return $this->projects
            ->flatMap(fn ($project) => $project->rkAnggotas)
            ->filter(fn ($rk) => $rk->is_completed)
            ->count();
    }
}