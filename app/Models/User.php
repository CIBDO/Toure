<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

/**
 * Modèle User - Gestion des utilisateurs IAM
 * 
 * Ce modèle représente un utilisateur du système avec :
 * - Identité (nom, prenom, email, telephone)
 * - Sécurité (password, statut, type_compte)
 * - Relations avec les rôles et permissions
 *
 * type_compte : CANAM | CONTRAT | SYSTEME
 */
class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    /**
     * Les attributs qui peuvent être assignés en masse.
     * 
     * Ces champs peuvent être remplis via User::create() ou $user->fill()
     */
    protected $fillable = [
        'name', // Champ existant pour compatibilité
        'nom',
        'prenom',
        'email',
        'telephone',
        'fonction',
        'unite_service',
        'region',
        'avatar', // Photo de profil (champ existant Vuexy)
        'password',
        'statut',
        'type_compte',
        'last_login_at',
        'failed_login_attempts',
        'locked_until',
        'must_change_password',
    ];

    /**
     * Les attributs qui doivent être cachés lors de la sérialisation.
     * 
     * Ces champs ne seront jamais retournés dans les réponses JSON
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Les attributs qui doivent être castés.
     * 
     * Laravel convertit automatiquement ces types
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'last_login_at' => 'datetime',
            'locked_until' => 'datetime',
            'must_change_password' => 'boolean',
        ];
    }

    /**
     * RELATION : Un utilisateur peut avoir plusieurs rôles
     * 
     * Relation many-to-many via la table pivot 'role_user'
     * 
     * Exemple d'utilisation :
     * $user->roles()->attach($roleId);
     * $user->roles()->detach($roleId);
     * $user->roles; // Collection de Role
     */
    public function roles()
    {
        return $this->belongsToMany(Role::class, 'role_user')
            ->withTimestamps();
    }

    /**
     * RELATION : Un utilisateur peut avoir plusieurs méthodes MFA
     * 
     * Relation one-to-many
     * 
     * Exemple :
     * $user->mfaMethods()->where('actif', true)->get();
     */
    public function mfaMethods()
    {
        return $this->hasMany(MfaMethod::class);
    }

    /**
     * RELATION : Un utilisateur peut avoir plusieurs logs d'audit (en tant qu'acteur)
     * 
     * Relation one-to-many
     * 
     * Exemple :
     * $user->auditLogs()->latest()->get();
     */
    public function auditLogs()
    {
        return $this->hasMany(AuditLog::class, 'actor_user_id');
    }

    /**
     * RELATION : Un utilisateur peut avoir plusieurs sessions actives
     * 
     * Relation one-to-many
     * 
     * Exemple :
     * $user->sessions()->where('is_active', true)->get();
     */
    public function sessions()
    {
        return $this->hasMany(UserSession::class);
    }

    /**
     * MÉTHODE UTILITAIRE : Vérifier si l'utilisateur a un rôle spécifique
     * 
     * @param string $roleCode Code du rôle (ex: 'ADMIN_DM')
     * @return bool
     * 
     * Exemple :
     * if ($user->hasRole('ADMIN_DM')) { ... }
     */
    public function hasRole(string $roleCode): bool
    {
        return $this->roles()->where('code', $roleCode)->exists();
    }

    /**
     * MÉTHODE UTILITAIRE : Vérifier si l'utilisateur a une permission spécifique
     * 
     * Cette méthode vérifie si l'utilisateur a la permission via l'un de ses rôles
     * 
     * @param string $permissionCode Code de la permission (ex: 'DEMANDES_READ')
     * @return bool
     * 
     * Exemple :
     * if ($user->hasPermission('DEMANDES_WRITE')) { ... }
     */
    public function hasPermission(string $permissionCode): bool
    {
        return $this->roles()
            ->whereHas('permissions', function ($query) use ($permissionCode) {
                $query->where('code', $permissionCode);
            })
            ->exists();
    }

    /**
     * MÉTHODE UTILITAIRE : Obtenir toutes les permissions de l'utilisateur
     * 
     * Récupère toutes les permissions via tous les rôles de l'utilisateur
     * 
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAllPermissions()
    {
        return Permission::whereHas('roles', function ($query) {
            $query->whereHas('users', function ($q) {
                $q->where('users.id', $this->id);
            });
        })->get();
    }

    /**
     * MÉTHODE UTILITAIRE : Obtenir le nom complet
     * 
     * @return string
     */
    public function getFullNameAttribute(): string
    {
        return trim($this->nom . ' ' . $this->prenom);
    }

    /**
     * MÉTHODE UTILITAIRE : Vérifier si l'utilisateur est actif
     * 
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->statut === 'ACTIF';
    }

    /**
     * MÉTHODE UTILITAIRE : Suspendre l'utilisateur
     */
    public function suspend(): void
    {
        $this->update(['statut' => 'SUSPENDU']);
    }

    /**
     * MÉTHODE UTILITAIRE : Activer l'utilisateur
     */
    public function activate(): void
    {
        $this->update(['statut' => 'ACTIF']);
    }

    /**
     * MÉTHODE UTILITAIRE : Désactiver l'utilisateur
     */
    public function deactivate(): void
    {
        $this->update(['statut' => 'DESACTIVE']);
    }

    /**
     * MÉTHODE UTILITAIRE : Vérifier si le compte est verrouillé
     * 
     * @return bool
     */
    public function isLocked(): bool
    {
        return $this->locked_until && $this->locked_until->isFuture();
    }

    /**
     * MÉTHODE UTILITAIRE : Incrémenter le nombre de tentatives échouées
     * 
     * @param int $maxAttempts Nombre maximum de tentatives avant verrouillage
     * @param int $lockoutMinutes Durée du verrouillage en minutes
     * @return void
     */
    public function incrementFailedLoginAttempts(int $maxAttempts = 5, int $lockoutMinutes = 30): void
    {
        $this->increment('failed_login_attempts');

        // Si le nombre de tentatives atteint le maximum, verrouiller le compte
        if ($this->failed_login_attempts >= $maxAttempts) {
            $this->lockAccount($lockoutMinutes);
        }
    }

    /**
     * MÉTHODE UTILITAIRE : Verrouiller le compte
     * 
     * @param int $minutes Durée du verrouillage en minutes
     * @return void
     */
    public function lockAccount(int $minutes = 30): void
    {
        $this->update([
            'locked_until' => now()->addMinutes($minutes),
        ]);
    }

    /**
     * MÉTHODE UTILITAIRE : Déverrouiller le compte
     * 
     * @return void
     */
    public function unlockAccount(): void
    {
        $this->update([
            'failed_login_attempts' => 0,
            'locked_until' => null,
        ]);
    }

    /**
     * MÉTHODE UTILITAIRE : Réinitialiser les tentatives échouées (après connexion réussie)
     * 
     * @return void
     */
    public function resetFailedLoginAttempts(): void
    {
        $this->update(['failed_login_attempts' => 0]);
    }
}
