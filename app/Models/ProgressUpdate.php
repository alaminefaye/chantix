<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProgressUpdate extends Model
{
    protected $fillable = [
        'project_id',
        'user_id',
        'progress_percentage',
        'description',
        'audio_file',
        'photos',
        'videos',
        'latitude',
        'longitude',
    ];

    protected $casts = [
        'progress_percentage' => 'integer',
        'photos' => 'array',
        'videos' => 'array',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
    ];

    /**
     * Le projet concerné
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * L'utilisateur qui a créé la mise à jour
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class);
    }
}
