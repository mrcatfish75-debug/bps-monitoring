<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Iku extends Model
{
    protected $fillable = [
    'code',
    'name',
    'description'
];
public function projects()
    {
        return $this->hasMany(Project::class);
    }
}
