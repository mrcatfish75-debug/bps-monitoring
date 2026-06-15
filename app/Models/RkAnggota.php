<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RkAnggota extends Model
{
    public const STATUS_DRAFT = 'draft';
    public const STATUS_SUBMITTED = 'submitted';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'project_id',
        'user_id',
        'description',
        'target',
        'status',
        'submitted_at',
        'approved_at',
        'approved_by',
        'final_evidence',
        'rejection_note',
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
        'approved_at' => 'datetime',
    ];

    protected $appends = [
        'progress',
        'status_label',
        'is_completed',
        'is_editable',
        'can_submit',
        'can_be_reviewed',
        'daily_task_count',
        'iki_count',
        'approved_iki_count',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function ikis()
    {
        return $this->hasMany(Iki::class);
    }

    public function dailyTasks()
    {
        return $this->hasMany(DailyTask::class);
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /*
    |--------------------------------------------------------------------------
    | Progress RK Anggota
    |--------------------------------------------------------------------------
    | Flow final:
    | RK Anggota adalah unit pekerjaan.
    |
    | Daily Task tidak masuk perhitungan progress utama.
    | Daily Task hanya monitoring aktivitas.
    |
    | RK Anggota dianggap selesai hanya jika sudah approved.
    |--------------------------------------------------------------------------
    */

    public function getProgressAttribute(): int
    {
        $this->loadMissing('ikis');

        $totalIki = $this->ikis->count();

        if ($totalIki === 0) {
            return 0;
        }

        $approvedIki = $this->ikis
            ->where('status', Iki::STATUS_APPROVED)
            ->count();

        return (int) round(($approvedIki / $totalIki) * 100);
    }

    public function getIsCompletedAttribute(): bool
    {
        $this->loadMissing('ikis');

        return $this->ikis->count() > 0
            && $this->ikis->every(fn ($iki) => $iki->status === Iki::STATUS_APPROVED);
    }

    /*
    |--------------------------------------------------------------------------
    | Status Helpers
    |--------------------------------------------------------------------------
    */

    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function isSubmitted(): bool
    {
        return $this->status === self::STATUS_SUBMITTED;
    }

    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    public function isRejected(): bool
    {
        return $this->status === self::STATUS_REJECTED;
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_DRAFT => 'Draft',
            self::STATUS_SUBMITTED => 'Menunggu Review',
            self::STATUS_APPROVED => 'Disetujui',
            self::STATUS_REJECTED => 'Ditolak',
            default => 'Tidak Diketahui',
        };
    }

    /*
    |--------------------------------------------------------------------------
    | Workflow Helpers
    |--------------------------------------------------------------------------
    */

    public function isEditable(): bool
    {
        return in_array($this->status, [
            self::STATUS_DRAFT,
            self::STATUS_REJECTED,
        ], true);
    }

    public function canSubmit(): bool
    {
        return in_array($this->status, [
            self::STATUS_DRAFT,
            self::STATUS_REJECTED,
        ], true);
    }

    public function canBeReviewed(): bool
    {
        return $this->status === self::STATUS_SUBMITTED;
    }

    public function getIsEditableAttribute(): bool
    {
        return $this->isEditable();
    }

    public function getCanSubmitAttribute(): bool
    {
        return $this->canSubmit();
    }

    public function getCanBeReviewedAttribute(): bool
    {
        return $this->canBeReviewed();
    }

    /*
    |--------------------------------------------------------------------------
    | Monitoring Helpers
    |--------------------------------------------------------------------------
    | Daily Task hanya monitoring, bukan progress utama.
    |--------------------------------------------------------------------------
    */

    public function getDailyTaskCountAttribute(): int
    {
        $this->loadMissing('dailyTasks');

        return $this->dailyTasks->count();
    }

    public function getIkiCountAttribute(): int
    {
        $this->loadMissing('ikis');

        return $this->ikis->count();
    }

    public function getApprovedIkiCountAttribute(): int
    {
        $this->loadMissing('ikis');

        return $this->ikis
            ->where('status', Iki::STATUS_APPROVED)
            ->count();
    }

    public function hasDailyTasks(): bool
    {
        $this->loadMissing('dailyTasks');

        return $this->dailyTasks->isNotEmpty();
    }
}