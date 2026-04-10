<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    protected $fillable = [
        'name',
        'iku_id',
        'team_id',
        'leader_id',
        'start_date',
        'end_date',
        'status'
    ];

    public function iku()
    {
        return $this->belongsTo(Iku::class);
    }

    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    public function leader()
    {
        return $this->belongsTo(User::class, 'leader_id');
    }

    public function members()
    {
        return $this->belongsToMany(User::class, 'project_members');
    }
}
