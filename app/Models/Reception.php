<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Reception extends Model
{
    use HasFactory, SoftDeletes;

    public const TYPE_PROVISOIRE = 'provisoire';
    public const TYPE_PARTIELLE = 'partielle';
    public const TYPE_DEFINITIVE = 'definitive';

    public const STATUT_CONFORMITE_CONFORME = 'conforme';
    public const STATUT_CONFORMITE_NON_CONFORME = 'non_conforme';
    public const STATUT_CONFORMITE_AVEC_RESERVES = 'conforme_avec_reserves';

    public const STATUT_DRAFT = 'draft';
    public const STATUT_SUBMITTED = 'submitted';
    public const STATUT_APPROVED = 'approved';
    public const STATUT_REJECTED = 'rejected';

    protected $fillable = [
        'uuid', 'contrat_id', 'numero', 'type_reception', 'date_reception',
        'lieu_reception', 'responsable_reception', 'constatations', 'reserves',
        'statut_conformite', 'quantite_receptionnee', 'taux_execution',
        'statut', 'commentaire_validation', 'approved_by', 'approved_at', 'created_by',
    ];

    protected $casts = [
        'date_reception' => 'date',
        'approved_at' => 'datetime',
        'quantite_receptionnee' => 'decimal:2',
        'taux_execution' => 'decimal:2',
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

    public function receptionItems()
    {
        return $this->hasMany(ReceptionItem::class)->orderBy('id');
    }

    public function documents()
    {
        return $this->morphMany(Document::class, 'documentable');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function isDraft(): bool
    {
        return $this->statut === self::STATUT_DRAFT;
    }

    public function isApproved(): bool
    {
        return $this->statut === self::STATUT_APPROVED;
    }

    public function isPendingValidation(): bool
    {
        return $this->statut === self::STATUT_SUBMITTED;
    }

    public function isDefinitive(): bool
    {
        return $this->type_reception === self::TYPE_DEFINITIVE;
    }

    public static function typeReceptionOptions(): array
    {
        return [
            self::TYPE_PROVISOIRE => 'Provisoire',
            self::TYPE_PARTIELLE => 'Partielle',
            self::TYPE_DEFINITIVE => 'Définitive',
        ];
    }

    public static function statutConformiteOptions(): array
    {
        return [
            self::STATUT_CONFORMITE_CONFORME => 'Conforme',
            self::STATUT_CONFORMITE_NON_CONFORME => 'Non conforme',
            self::STATUT_CONFORMITE_AVEC_RESERVES => 'Conforme avec réserves',
        ];
    }

    public static function statutOptions(): array
    {
        return [
            self::STATUT_DRAFT => 'Brouillon',
            self::STATUT_SUBMITTED => 'Soumis',
            self::STATUT_APPROVED => 'Approuvé',
            self::STATUT_REJECTED => 'Rejeté',
        ];
    }
}
