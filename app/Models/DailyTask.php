<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DailyTask extends Model
{
    
   // 🔥 TAMBAHKAN INI
    const STATUS_PENDING = 'pending';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'rk_anggota_id',
        'date',
        'activity',
        'output',
        'evidence_url',
        'status',
        'approved_by',
        'approved_at',
        'review_note'
    ];

    public function rkAnggota()
    {
        return $this->belongsTo(RkAnggota::class);
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}