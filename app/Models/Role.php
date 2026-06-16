<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Modèle Role - Gestion des rôles IAM
 * 
 * Ce modèle représente un rôle dans le système :
 * - ADMIN_DM, SUP_DM, AGENT_DM, CONTRAT_USER, AUDIT_READ, etc.
 * - Un rôle peut être assigné à plusieurs utilisateurs
 * - Un rôle peut avoir plusieurs permissions
 */
class Role extends Model
{
    use HasFactory;

    /**
     * Les attributs qui peuvent être assignés en masse.
     */
    protected $fillable = [
        'code',
        'libelle',
    ];

    /**
     * RELATION : Un rôle peut être assigné à plusieurs utilisateurs
     * 
     * Relation many-to-many via la table pivot 'role_user'
     * 
     * Exemple :
     * $role->users()->attach($userId);
     * $role->users; // Collection de User
     */
    public function users()
    {
        return $this->belongsToMany(User::class, 'role_user')
            ->withTimestamps();
    }

    /**
     * RELATION : Un rôle peut avoir plusieurs permissions
     * 
     * Relation many-to-many via la table pivot 'permission_role'
     * 
     * Exemple :
     * $role->permissions()->attach($permissionId);
     * $role->permissions; // Collection de Permission
     */
    public function permissions()
    {
        return $this->belongsToMany(Permission::class, 'permission_role')
            ->withTimestamps();
    }

    /**
     * MÉTHODE UTILITAIRE : Vérifier si le rôle a une permission
     * 
     * @param string $permissionCode Code de la permission
     * @return bool
     */
    public function hasPermission(string $permissionCode): bool
    {
        return $this->permissions()->where('code', $permissionCode)->exists();
    }

    /**
     * MÉTHODE UTILITAIRE : Assigner une permission au rôle
     * 
     * @param string|int $permissionId Code ou ID de la permission
     */
    public function assignPermission($permissionId): void
    {
        if (is_string($permissionId)) {
            $permission = Permission::where('code', $permissionId)->first();
            if ($permission) {
                $this->permissions()->syncWithoutDetaching([$permission->id]);
            }
        } else {
            $this->permissions()->syncWithoutDetaching([$permissionId]);
        }
    }

    /**
     * MÉTHODE UTILITAIRE : Retirer une permission du rôle
     * 
     * @param string|int $permissionId Code ou ID de la permission
     */
    public function revokePermission($permissionId): void
    {
        if (is_string($permissionId)) {
            $permission = Permission::where('code', $permissionId)->first();
            if ($permission) {
                $this->permissions()->detach($permission->id);
            }
        } else {
            $this->permissions()->detach($permissionId);
        }
    }
}
