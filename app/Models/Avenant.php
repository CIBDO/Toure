<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Avenant extends Model
{
    use HasFactory, SoftDeletes;

    public const TYPE_MONTANT = 'montant';
    public const TYPE_DELAI = 'delai';
    public const TYPE_OBJET = 'objet';
    public const TYPE_MIXTE = 'mixte';

    public const STATUT_DRAFT = 'draft';
    public const STATUT_SUBMITTED = 'submitted';
    public const STATUT_APPROVED = 'approved';
    public const STATUT_REJECTED = 'rejected';

    protected $fillable = [
        'uuid', 'contrat_id', 'numero', 'type_avenant',
        'montant_variation', 'ancien_montant', 'nouveau_montant',
        'ancienne_date_fin', 'nouvelle_date_fin', 'prolongation_jours',
        'ancienne_description_objet', 'nouvelle_description_objet',
        'justification', 'date_signature', 'statut', 'commentaire_validation',
        'created_by', 'approved_by', 'approved_at',
    ];

    protected $casts = [
        'date_signature' => 'date',
        'ancienne_date_fin' => 'date',
        'nouvelle_date_fin' => 'date',
        'approved_at' => 'datetime',
        'ancien_montant' => 'integer',
        'nouveau_montant' => 'integer',
        'montant_variation' => 'integer',
    ];

    protected static function boot(): void
    {
        parent::boot();
        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = Str::uuid();
            }
        });
    }

    public function contrat()
    {
        return $this->belongsTo(Contrat::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function isApproved(): bool
    {
        return $this->statut === self::STATUT_APPROVED;
    }

    public function isDraft(): bool
    {
        return $this->statut === self::STATUT_DRAFT;
    }

    public function isPendingValidation(): bool
    {
        return $this->statut === self::STATUT_SUBMITTED;
    }

    public static function typeOptions(): array
    {
        return [
            self::TYPE_MONTANT => 'Modification du montant',
            self::TYPE_DELAI => 'Modification du délai',
            self::TYPE_OBJET => 'Modification de l\'objet',
            self::TYPE_MIXTE => 'Mixte (montant + délai et/ou objet)',
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
