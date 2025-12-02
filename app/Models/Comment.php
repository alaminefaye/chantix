<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Comment extends Model
{
    protected $fillable = [
        'project_id',
        'user_id',
        'parent_id',
        'content',
        'mentioned_users',
        'attachments',
        'is_read',
    ];

    protected $casts = [
        'mentioned_users' => 'array',
        'attachments' => 'array',
        'is_read' => 'boolean',
    ];

    /**
     * Le projet
     */
    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * L'utilisateur qui a créé le commentaire
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Le commentaire parent (pour les réponses)
     */
    public function parent()
    {
        return $this->belongsTo(Comment::class, 'parent_id');
    }

    /**
     * Les réponses à ce commentaire
     */
    public function replies()
    {
        return $this->hasMany(Comment::class, 'parent_id')->orderBy('created_at', 'asc');
    }

    /**
     * Extraire les mentions (@username) du contenu
     */
    public function extractMentions()
    {
        preg_match_all('/@(\w+)/', $this->content, $matches);
        return $matches[1] ?? [];
    }

    /**
     * Remplacer les mentions par des liens HTML
     */
    public function getFormattedContentAttribute()
    {
        $content = htmlspecialchars($this->content);
        
        // Si des utilisateurs sont mentionnés, les remplacer
        if ($this->mentioned_users && count($this->mentioned_users) > 0) {
            $users = User::whereIn('id', $this->mentioned_users)->get()->keyBy('id');
            
            // Remplacer les mentions dans le contenu
            foreach ($users as $user) {
                $content = str_replace('@' . $user->name, '<span class="badge bg-info">@' . htmlspecialchars($user->name) . '</span>', $content);
            }
        }
        
        return $content;
    }

    /**
     * Scope pour les commentaires principaux (sans parent)
     */
    public function scopeMain($query)
    {
        return $query->whereNull('parent_id');
    }

    /**
     * Scope pour un projet
     */
    public function scopeForProject($query, $projectId)
    {
        return $query->where('project_id', $projectId);
    }
}
