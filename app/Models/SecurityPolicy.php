<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * Modèle SecurityPolicy - Gestion des politiques de sécurité
 * 
 * Ce modèle représente une politique de sécurité du système :
 * - Longueur minimale du mot de passe
 * - Complexité requise
 * - Expiration des mots de passe
 * - Historique des mots de passe
 * - Verrouillage après X tentatives échouées
 * 
 * Structure clé-valeur pour flexibilité
 */
class SecurityPolicy extends Model
{
    use HasFactory;

    /**
     * Les attributs qui peuvent être assignés en masse.
     */
    protected $fillable = [
        'key',
        'value',
        'type',
        'description',
        'is_active',
    ];

    /**
     * Les attributs qui doivent être castés.
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /**
     * MÉTHODE STATIQUE : Obtenir la valeur d'une politique
     * 
     * Récupère la valeur d'une politique par sa clé avec mise en cache
     * 
     * @param string $key Clé de la politique
     * @param mixed $default Valeur par défaut si la politique n'existe pas
     * @return mixed
     * 
     * Exemple :
     * $minLength = SecurityPolicy::getValue('password_min_length', 8);
     */
    public static function getValue(string $key, $default = null)
    {
        return Cache::remember("security_policy_{$key}", 3600, function () use ($key, $default) {
            $policy = self::where('key', $key)
                ->where('is_active', true)
                ->first();

            if (!$policy) {
                return $default;
            }

            // Convertir selon le type
            return match ($policy->type) {
                'integer' => (int) $policy->value,
                'boolean' => filter_var($policy->value, FILTER_VALIDATE_BOOLEAN),
                'json' => json_decode($policy->value, true),
                default => $policy->value,
            };
        });
    }

    /**
     * MÉTHODE STATIQUE : Définir la valeur d'une politique
     * 
     * Crée ou met à jour une politique
     * 
     * @param string $key Clé de la politique
     * @param mixed $value Valeur de la politique
     * @param string $type Type de valeur (string, integer, boolean, json)
     * @param string|null $description Description
     * @return SecurityPolicy
     * 
     * Exemple :
     * SecurityPolicy::setValue('password_min_length', 12, 'integer');
     */
    public static function setValue(string $key, $value, string $type = 'string', ?string $description = null): self
    {
        // Convertir la valeur en string pour stockage
        $stringValue = match ($type) {
            'boolean' => $value ? '1' : '0',
            'json' => json_encode($value),
            default => (string) $value,
        };

        $policy = self::updateOrCreate(
            ['key' => $key],
            [
                'value' => $stringValue,
                'type' => $type,
                'description' => $description,
                'is_active' => true,
            ]
        );

        // Invalider le cache
        Cache::forget("security_policy_{$key}");

        return $policy;
    }

    /**
     * MÉTHODE STATIQUE : Obtenir toutes les politiques actives
     * 
     * @return \Illuminate\Support\Collection
     */
    public static function getAllActive()
    {
        return self::where('is_active', true)->get();
    }
}
