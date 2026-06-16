<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

/**
 * Modèle AuditLog - Gestion des logs d'audit
 * 
 * Ce modèle représente un événement d'audit dans le système :
 * - Qui a fait l'action (actor_user_id)
 * - Quelle action (CREATE, UPDATE, DELETE, LOGIN, etc.)
 * - Sur quel objet (objet_type, objet_id)
 * - Quand et depuis où (ip, user_agent)
 * - Anciennes et nouvelles valeurs (ancienne_valeur, nouvelle_valeur)
 * - Commentaire optionnel (commentaire)
 */
class AuditLog extends Model
{
    use HasFactory;

    /**
     * Les attributs qui peuvent être assignés en masse.
     */
    protected $fillable = [
        'actor_user_id',
        'action',
        'objet_type',
        'objet_id',
        'payload_json',
        'ancienne_valeur',
        'nouvelle_valeur',
        'commentaire',
        'ip',
        'user_agent',
    ];

    /**
     * Les attributs qui doivent être castés.
     */
    protected function casts(): array
    {
        return [
            'payload_json' => 'array', // Laravel convertit automatiquement JSON en array
            'ancienne_valeur' => 'array', // Valeurs avant modification
            'nouvelle_valeur' => 'array', // Valeurs après modification
        ];
    }

    /**
     * RELATION : Un log d'audit appartient à un utilisateur (acteur)
     * 
     * Relation many-to-one (nullable car certaines actions peuvent être publiques)
     * 
     * Exemple :
     * $auditLog->actor; // User ou null
     */
    public function actor()
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }

    /**
     * MÉTHODE UTILITAIRE : Obtenir l'objet concerné par le log
     * 
     * Utilise le polymorphisme pour récupérer l'objet
     * 
     * @return Model|null
     */
    public function objet()
    {
        if (!$this->objet_type || !$this->objet_id) {
            return null;
        }

        return $this->objet_type::find($this->objet_id);
    }

    /**
     * MÉTHODE STATIQUE : Créer un log d'audit (ancienne méthode pour compatibilité)
     * 
     * Méthode helper pour faciliter la création de logs
     * 
     * @param string $action Action effectuée
     * @param string|null $objetType Type d'objet
     * @param int|null $objetId ID de l'objet
     * @param array|null $payload Données supplémentaires
     * @param int|null $actorUserId ID de l'utilisateur acteur
     * @return AuditLog
     */
    public static function log(
        string $action,
        ?string $objetType = null,
        ?int $objetId = null,
        ?array $payload = null,
        ?int $actorUserId = null
    ): self {
        return self::create([
            'actor_user_id' => $actorUserId ?? Auth::id(),
            'action' => $action,
            'objet_type' => $objetType,
            'objet_id' => $objetId,
            'payload_json' => $payload,
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    /**
     * MÉTHODE STATIQUE : Créer un log d'audit avec anciennes et nouvelles valeurs
     * 
     * Méthode améliorée pour capturer les changements
     * 
     * @param string $action Action effectuée (CREATE, UPDATE, DELETE, etc.)
     * @param string|null $objetType Type d'objet
     * @param int|null $objetId ID de l'objet
     * @param array|null $ancienneValeur Valeurs avant modification
     * @param array|null $nouvelleValeur Valeurs après modification
     * @param string|null $commentaire Commentaire optionnel
     * @param array|null $payload Données supplémentaires (pour compatibilité)
     * @param int|null $actorUserId ID de l'utilisateur acteur
     * @return AuditLog
     * 
     * Exemple :
     * AuditLog::logChange('UPDATE', 'User', $user->id, $user->getOriginal(), $user->getChanges(), 'Mise à jour du statut');
     */
    /**
     * MÉTHODE STATIQUE : Alias simplifié pour logChange
     */
    public static function logAction(
        string $action,
        ?string $objetType = null,
        ?int $objetId = null,
        ?array $ancienneValeur = null,
        ?array $nouvelleValeur = null
    ): self {
        return self::logChange($action, $objetType, $objetId, $ancienneValeur, $nouvelleValeur);
    }

    public static function logChange(
        string $action,
        ?string $objetType = null,
        ?int $objetId = null,
        ?array $ancienneValeur = null,
        ?array $nouvelleValeur = null,
        ?string $commentaire = null,
        ?array $payload = null,
        ?int $actorUserId = null
    ): self {
        return self::create([
            'actor_user_id' => $actorUserId ?? Auth::id(),
            'action' => $action,
            'objet_type' => $objetType,
            'objet_id' => $objetId,
            'payload_json' => $payload,
            'ancienne_valeur' => $ancienneValeur,
            'nouvelle_valeur' => $nouvelleValeur,
            'commentaire' => $commentaire,
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}
