<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ActivityLog extends Model
{
    use HasFactory;

    protected $table = 'activity_logs';

    protected $fillable = [
        'user_id',
        'action',
        'model',
        'model_id',
        'description'
    ];

    /**
     * RELATION: siapa yang melakukan aksi
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * ACCESSOR: format nama action
     */
    public function getActionLabelAttribute()
    {
        return match($this->action) {
            'create' => 'Membuat',
            'update' => 'Mengupdate',
            'delete' => 'Menghapus',
            'approve' => 'Menyetujui',
            'reject' => 'Menolak',
            default => ucfirst($this->action),
        };
    }

    /**
     * ACCESSOR: format waktu
     */
    public function getCreatedAtFormattedAttribute()
    {
        return $this->created_at->format('d M Y H:i');
    }

    /**
     * SCOPE: filter berdasarkan model
     */
    public function scopeByModel($query, $model)
    {
        return $query->where('model', $model);
    }

    /**
     * SCOPE: filter berdasarkan user
     */
    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * SCOPE: filter berdasarkan action
     */
    public function scopeByAction($query, $action)
    {
        return $query->where('action', $action);
    }
}