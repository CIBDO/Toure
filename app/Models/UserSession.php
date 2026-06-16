<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Modèle UserSession - Gestion des sessions actives
 * 
 * Ce modèle représente une session active d'un utilisateur :
 * - Token de session
 * - Utilisateur propriétaire
 * - IP et User Agent
 * - Date de dernière activité
 * - Date d'expiration
 * - Statut (active, revoked)
 */
class UserSession extends Model
{
    use HasFactory;

    /**
     * Les attributs qui peuvent être assignés en masse.
     */
    protected $fillable = [
        'user_id',
        'token',
        'ip_address',
        'user_agent',
        'last_activity_at',
        'expires_at',
        'is_active',
    ];

    /**
     * Les attributs qui doivent être castés.
     */
    protected function casts(): array
    {
        return [
            'last_activity_at' => 'datetime',
            'expires_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    /**
     * RELATION : Une session appartient à un utilisateur
     * 
     * Relation many-to-one
     * 
     * Exemple :
     * $session->user; // User
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * MÉTHODE UTILITAIRE : Vérifier si la session est expirée
     * 
     * @return bool
     */
    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * MÉTHODE UTILITAIRE : Vérifier si la session est valide (active et non expirée)
     * 
     * @return bool
     */
    public function isValid(): bool
    {
        return $this->is_active && !$this->isExpired();
    }

    /**
     * MÉTHODE UTILITAIRE : Révoquer la session
     * 
     * @return void
     */
    public function revoke(): void
    {
        $this->update(['is_active' => false]);
    }

    /**
     * MÉTHODE UTILITAIRE : Mettre à jour la dernière activité
     * 
     * @return void
     */
    public function updateActivity(): void
    {
        $this->update(['last_activity_at' => now()]);
    }

    /**
     * MÉTHODE STATIQUE : Créer une nouvelle session
     * 
     * @param int $userId ID de l'utilisateur
     * @param int $lifetimeMinutes Durée de vie de la session en minutes
     * @return UserSession
     */
    public static function createSession(int $userId, int $lifetimeMinutes = 120): self
    {
        return self::create([
            'user_id' => $userId,
            'token' => Str::random(64),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'last_activity_at' => now(),
            'expires_at' => now()->addMinutes($lifetimeMinutes),
            'is_active' => true,
        ]);
    }

    /**
     * MÉTHODE STATIQUE : Trouver une session par token
     * 
     * @param string $token Token de session
     * @return UserSession|null
     */
    public static function findByToken(string $token): ?self
    {
        return self::where('token', $token)
            ->where('is_active', true)
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->first();
    }

    /**
     * MÉTHODE STATIQUE : Révoquer toutes les sessions d'un utilisateur
     * 
     * @param int $userId ID de l'utilisateur
     * @return int Nombre de sessions révoquées
     */
    public static function revokeAllUserSessions(int $userId): int
    {
        return self::where('user_id', $userId)
            ->where('is_active', true)
            ->update(['is_active' => false]);
    }

    /**
     * MÉTHODE STATIQUE : Révoquer toutes les sessions sauf la session actuelle
     * 
     * @param int $userId ID de l'utilisateur
     * @param string $currentToken Token de la session actuelle
     * @return int Nombre de sessions révoquées
     */
    public static function revokeOtherUserSessions(int $userId, string $currentToken): int
    {
        return self::where('user_id', $userId)
            ->where('token', '!=', $currentToken)
            ->where('is_active', true)
            ->update(['is_active' => false]);
    }
}
