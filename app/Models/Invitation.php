<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Carbon\Carbon;

class Invitation extends Model
{
    protected $fillable = [
        'company_id',
        'invited_by',
        'role_id',
        'email',
        'token',
        'status',
        'expires_at',
        'accepted_at',
        'message',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'accepted_at' => 'datetime',
    ];

    /**
     * L'entreprise
     */
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * L'utilisateur qui a envoyé l'invitation
     */
    public function inviter()
    {
        return $this->belongsTo(User::class, 'invited_by');
    }

    /**
     * Le rôle assigné
     */
    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    /**
     * Générer un token unique
     */
    public static function generateToken()
    {
        do {
            $token = Str::random(64);
        } while (self::where('token', $token)->exists());

        return $token;
    }

    /**
     * Vérifier si l'invitation est expirée
     */
    public function isExpired()
    {
        return $this->expires_at->isPast();
    }

    /**
     * Vérifier si l'invitation est valide
     */
    public function isValid()
    {
        return $this->status === 'pending' && !$this->isExpired();
    }

    /**
     * Marquer comme acceptée
     */
    public function markAsAccepted()
    {
        $this->status = 'accepted';
        $this->accepted_at = now();
        $this->save();
    }

    /**
     * Marquer comme annulée
     */
    public function markAsCancelled()
    {
        $this->status = 'cancelled';
        $this->save();
    }

    /**
     * Scope pour les invitations en attente
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending')
            ->where('expires_at', '>', now());
    }

    /**
     * Scope pour une entreprise
     */
    public function scopeForCompany($query, $companyId)
    {
        return $query->where('company_id', $companyId);
    }
}
