<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Modèle MfaMethod - Gestion de l'authentification multi-facteurs
 * 
 * Ce modèle représente une méthode MFA pour un utilisateur :
 * - TOTP (Time-based One-Time Password)
 * - SMS
 * - EMAIL
 */
class MfaMethod extends Model
{
    use HasFactory;

    /**
     * Les attributs qui peuvent être assignés en masse.
     */
    protected $fillable = [
        'user_id',
        'type',
        'secret',
        'destination',
        'actif',
    ];

    /**
     * Les attributs qui doivent être castés.
     */
    protected function casts(): array
    {
        return [
            'actif' => 'boolean',
        ];
    }

    /**
     * RELATION : Une méthode MFA appartient à un utilisateur
     * 
     * Relation many-to-one
     * 
     * Exemple :
     * $mfaMethod->user; // User
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * MÉTHODE UTILITAIRE : Activer la méthode MFA
     */
    public function activate(): void
    {
        $this->update(['actif' => true]);
    }

    /**
     * MÉTHODE UTILITAIRE : Désactiver la méthode MFA
     */
    public function deactivate(): void
    {
        $this->update(['actif' => false]);
    }

    /**
     * MÉTHODE UTILITAIRE : Vérifier si la méthode est active
     * 
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->actif === true;
    }
}
