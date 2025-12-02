<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProjectStatusHistory extends Model
{
    protected $fillable = [
        'project_id',
        'user_id',
        'old_status',
        'new_status',
        'reason',
    ];

    /**
     * Le projet
     */
    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * L'utilisateur qui a fait le changement
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
