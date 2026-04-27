<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RkAnggota extends Model
{
    const STATUS_DRAFT = 'draft';
    const STATUS_SUBMITTED = 'submitted';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'project_id',
        'user_id',
        'description',
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

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function dailyTasks()
    {
        return $this->hasMany(DailyTask::class);
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    // RK Anggota selesai hanya jika sudah approved oleh Ketua
    public function getProgressAttribute()
    {
        return $this->status === self::STATUS_APPROVED ? 100 : 0;
    }

    public function isEditable()
    {
        return in_array($this->status, [
            self::STATUS_DRAFT,
            self::STATUS_REJECTED,
        ]);
    }

    public function canSubmit()
    {
        return in_array($this->status, [
            self::STATUS_DRAFT,
            self::STATUS_REJECTED,
        ]);
    }

    public function canBeReviewed()
    {
        return $this->status === self::STATUS_SUBMITTED;
    }
}