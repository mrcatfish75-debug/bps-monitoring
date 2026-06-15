<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DailyTask extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'rk_anggota_id',
        'iki_id',
        'date',
        'activity',
        'output',
        'evidence_url',
        'status',
        'approved_by',
        'approved_at',
        'review_note',
    ];

    protected $casts = [
        'date' => 'date',
        'approved_at' => 'datetime',
    ];

    protected $appends = [
        'status_label',
        'is_pending',
        'is_approved',
        'is_rejected',
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

    public function iki()
    {
        return $this->belongsTo(Iki::class);
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /*
    |--------------------------------------------------------------------------
    | Status Helpers
    |--------------------------------------------------------------------------
    | Daily Task hanya untuk monitoring aktivitas.
    | Daily Task tidak masuk perhitungan progress utama IKU/RK Ketua/Project.
    |--------------------------------------------------------------------------
    */

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
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
            self::STATUS_PENDING => 'Menunggu Review',
            self::STATUS_APPROVED => 'Disetujui',
            self::STATUS_REJECTED => 'Ditolak',
            default => 'Tidak Diketahui',
        };
    }

    public function getIsPendingAttribute(): bool
    {
        return $this->isPending();
    }

    public function getIsApprovedAttribute(): bool
    {
        return $this->isApproved();
    }

    public function getIsRejectedAttribute(): bool
    {
        return $this->isRejected();
    }

    /*
    |--------------------------------------------------------------------------
    | Workflow Helpers
    |--------------------------------------------------------------------------
    */

    public function canBeReviewed(): bool
    {
        return $this->isPending();
    }

    public function canBeEdited(): bool
    {
        return $this->isPending() || $this->isRejected();
    }
}