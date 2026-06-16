<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Modèle Permission - Gestion des permissions IAM
 * 
 * Ce modèle représente une permission dans le système :
 * - DEMANDES_READ, DEMANDES_WRITE, CONTRATS_EDIT, SANCTIONS_APPROVE, etc.
 * - Une permission peut être dans plusieurs rôles
 * - Les permissions sont assignées aux utilisateurs via les rôles
 */
class Permission extends Model
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
     * RELATION : Une permission peut être dans plusieurs rôles
     * 
     * Relation many-to-many via la table pivot 'permission_role'
     * 
     * Exemple :
     * $permission->roles; // Collection de Role
     */
    public function roles()
    {
        return $this->belongsToMany(Role::class, 'permission_role')
            ->withTimestamps();
    }

    /**
     * MÉTHODE UTILITAIRE : Obtenir tous les utilisateurs qui ont cette permission
     * 
     * Via leurs rôles
     * 
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function users()
    {
        return User::whereHas('roles', function ($query) {
            $query->whereHas('permissions', function ($q) {
                $q->where('permissions.id', $this->id);
            });
        })->get();
    }
}
