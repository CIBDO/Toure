<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class OrdreService extends Model
{
    use HasFactory, SoftDeletes;

    public const TYPE_DEMARRAGE = 'demarrage';
    public const TYPE_SUSPENSION = 'suspension';
    public const TYPE_REPRISE = 'reprise';
    public const TYPE_ARRET = 'arret';
    public const TYPE_MODIFICATION = 'modification';
    public const TYPE_AUTRE = 'autre';

    public const IMPACT_NONE = 'none';
    public const IMPACT_EXTEND = 'extend';
    public const IMPACT_REDUCE = 'reduce';

    public const STATUT_DRAFT = 'draft';
    public const STATUT_SUBMITTED = 'submitted';
    public const STATUT_APPROVED = 'approved';
    public const STATUT_REJECTED = 'rejected';
    public const STATUT_EXECUTED = 'executed';
    public const STATUT_ARCHIVED = 'archived';

    protected $table = 'ordre_services';

    protected $fillable = [
        'uuid', 'contrat_id', 'numero', 'type_os', 'objet', 'description',
        'date_emission', 'date_effet', 'impact_delai', 'delai_jours',
        'statut', 'commentaire_validation',
        'issued_by', 'approved_by', 'approved_at', 'executed_at', 'created_by',
    ];

    protected $casts = [
        'date_emission' => 'date',
        'date_effet' => 'date',
        'approved_at' => 'datetime',
        'executed_at' => 'datetime',
        'delai_jours' => 'integer',
    ];

    protected static function boot(): void
    {
        parent::boot();
        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }

    public function contrat()
    {
        return $this->belongsTo(Contrat::class);
    }

    public function documents()
    {
        return $this->morphMany(Document::class, 'documentable');
    }

    public function issuedBy()
    {
        return $this->belongsTo(User::class, 'issued_by');
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isDraft(): bool
    {
        return $this->statut === self::STATUT_DRAFT;
    }

    public function isPendingValidation(): bool
    {
        return $this->statut === self::STATUT_SUBMITTED;
    }

    public function isApproved(): bool
    {
        return $this->statut === self::STATUT_APPROVED;
    }

    public function isExecuted(): bool
    {
        return $this->statut === self::STATUT_EXECUTED;
    }

    public static function typeOptions(): array
    {
        return [
            self::TYPE_DEMARRAGE => 'Démarrage',
            self::TYPE_SUSPENSION => 'Suspension',
            self::TYPE_REPRISE => 'Reprise',
            self::TYPE_ARRET => 'Arrêt',
            self::TYPE_MODIFICATION => 'Modification (instructions complémentaires)',
            self::TYPE_AUTRE => 'Autre',
        ];
    }

    public static function impactDelaiOptions(): array
    {
        return [
            self::IMPACT_NONE => 'Aucun',
            self::IMPACT_EXTEND => 'Prolonger',
            self::IMPACT_REDUCE => 'Réduire',
        ];
    }

    public static function statutOptions(): array
    {
        return [
            self::STATUT_DRAFT => 'Brouillon',
            self::STATUT_SUBMITTED => 'Soumis',
            self::STATUT_APPROVED => 'Approuvé',
            self::STATUT_REJECTED => 'Rejeté',
            self::STATUT_EXECUTED => 'Exécuté',
            self::STATUT_ARCHIVED => 'Archivé',
        ];
    }
}
