<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RkKetuaTemplate extends Model
{
    use HasFactory;

    protected $table = 'rk_ketua_templates';

    protected $fillable = [
        'description',
        'category',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /*
    |--------------------------------------------------------------------------
    | Scope: Active Templates
    |--------------------------------------------------------------------------
    | Dipakai untuk mengambil hanya template RK Ketua yang aktif.
    |--------------------------------------------------------------------------
    */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /*
    |--------------------------------------------------------------------------
    | Accessor: Category Label
    |--------------------------------------------------------------------------
    | Jika category kosong, tampilkan label default agar aman di Blade/JS.
    |--------------------------------------------------------------------------
    */
    
    public function getCategoryLabelAttribute(): string
    {
        return $this->category ?: 'RK Ketua';
    }
}