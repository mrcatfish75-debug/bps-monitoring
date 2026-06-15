<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    public const TYPE_INFO = 'info';
    public const TYPE_SUCCESS = 'success';
    public const TYPE_WARNING = 'warning';
    public const TYPE_DANGER = 'danger';

    public const TYPE_IKI_SUBMITTED = 'iki_submitted';
    public const TYPE_IKI_APPROVED = 'iki_approved';
    public const TYPE_IKI_REJECTED = 'iki_rejected';
    public const TYPE_PROJECT_ASSIGNED = 'project_assigned';

    protected $fillable = [
        'user_id',
        'title',
        'message',
        'type',
        'url',
        'is_read',
        'read_at',
    ];

    protected $casts = [
        'is_read' => 'boolean',
        'read_at' => 'datetime',
    ];

    protected $appends = [
        'type_label',
        'icon',
        'color_class',
        'time_label',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function markAsRead(): void
    {
        if (!$this->is_read) {
            $this->update([
                'is_read' => true,
                'read_at' => now(),
            ]);
        }
    }

    public function getTypeLabelAttribute(): string
    {
        return match ($this->type) {
            self::TYPE_IKI_SUBMITTED => 'Review IKI',
            self::TYPE_IKI_APPROVED => 'IKI Disetujui',
            self::TYPE_IKI_REJECTED => 'IKI Revisi',
            self::TYPE_PROJECT_ASSIGNED => 'Project Baru',
            self::TYPE_SUCCESS => 'Berhasil',
            self::TYPE_WARNING => 'Perhatian',
            self::TYPE_DANGER => 'Penting',
            default => 'Informasi',
        };
    }

    public function getIconAttribute(): string
    {
        return match ($this->type) {
            self::TYPE_IKI_SUBMITTED => 'file-clock',
            self::TYPE_IKI_APPROVED => 'badge-check',
            self::TYPE_IKI_REJECTED => 'circle-alert',
            self::TYPE_PROJECT_ASSIGNED => 'folder-plus',
            self::TYPE_SUCCESS => 'check-circle-2',
            self::TYPE_WARNING => 'alert-triangle',
            self::TYPE_DANGER => 'circle-alert',
            default => 'bell',
        };
    }

    public function getColorClassAttribute(): string
    {
        return match ($this->type) {
            self::TYPE_IKI_SUBMITTED => 'bg-blue-50 text-blue-700 border-blue-100',
            self::TYPE_IKI_APPROVED => 'bg-emerald-50 text-emerald-700 border-emerald-100',
            self::TYPE_IKI_REJECTED => 'bg-red-50 text-red-700 border-red-100',
            self::TYPE_PROJECT_ASSIGNED => 'bg-violet-50 text-violet-700 border-violet-100',
            self::TYPE_SUCCESS => 'bg-emerald-50 text-emerald-700 border-emerald-100',
            self::TYPE_WARNING => 'bg-amber-50 text-amber-700 border-amber-100',
            self::TYPE_DANGER => 'bg-red-50 text-red-700 border-red-100',
            default => 'bg-slate-50 text-slate-700 border-slate-100',
        };
    }

    public function getTimeLabelAttribute(): string
    {
        return $this->created_at
            ? $this->created_at->diffForHumans()
            : '-';
    }

    public static function sendToUser(
        int $userId,
        string $title,
        string $message,
        ?string $url = null,
        string $type = self::TYPE_INFO
    ): self {
        return self::create([
            'user_id' => $userId,
            'title' => $title,
            'message' => $message,
            'type' => $type,
            'url' => $url,
            'is_read' => false,
            'read_at' => null,
        ]);
    }
}