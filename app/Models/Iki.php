<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Iki extends Model
{
    public const STATUS_DRAFT = 'draft';
    public const STATUS_SUBMITTED = 'submitted';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'rk_anggota_id',
        'description',
        'target',
        'unit',
        'status',
        'final_evidence',
        'submitted_at',
        'approved_at',
        'approved_by',
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
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function rkAnggota()
    {
        return $this->belongsTo(RkAnggota::class);
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
    | Progress IKI
    |--------------------------------------------------------------------------
    | IKI adalah unit approval utama.
    | IKI dianggap selesai jika sudah approved oleh Ketua/Admin.
    |--------------------------------------------------------------------------
    */

    public function getProgressAttribute(): int
    {
        return $this->isApproved() ? 100 : 0;
    }

    public function getIsCompletedAttribute(): bool
    {
        return $this->isApproved();
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
    */

    public function getDailyTaskCountAttribute(): int
    {
        $this->loadMissing('dailyTasks');

        return $this->dailyTasks->count();
    }

    public function hasDailyTasks(): bool
    {
        $this->loadMissing('dailyTasks');

        return $this->dailyTasks->isNotEmpty();
    }
}