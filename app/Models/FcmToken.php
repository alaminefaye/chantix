<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FcmToken extends Model
{
    protected $fillable = [
        'user_id',
        'token',
        'device_id',
        'device_type',
        'device_name',
        'is_active',
        'last_used_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'last_used_at' => 'datetime',
    ];

    /**
     * L'utilisateur propriétaire du token
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Marquer le token comme utilisé
     */
    public function markAsUsed()
    {
        $this->update(['last_used_at' => now()]);
    }

    /**
     * Désactiver le token
     */
    public function deactivate()
    {
        $this->update(['is_active' => false]);
    }

    /**
     * Activer le token
     */
    public function activate()
    {
        $this->update(['is_active' => true, 'last_used_at' => now()]);
    }

    /**
     * Scope pour les tokens actifs
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope pour un utilisateur
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }
}
