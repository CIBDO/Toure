<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Contrat extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'uuid', 'reference', 'numero', 'objet', 'pv_id', 'avis_id',
        'fournisseur_id', 'compte_budget_id', 'agent_id',
        'montant_initial', 'montant_actuel', 'devise',
        'date_signature', 'date_debut', 'date_fin',
        'duree_execution', 'mode_passation', 'exercice',
        'statut', 'observations', 'motif_rejet', 'status_execution', 'cloturable', 'created_by',
    ];

    protected $casts = [
        'date_signature' => 'date',
        'date_debut' => 'date',
        'date_fin' => 'date',
        'montant_initial' => 'decimal:2',
        'montant_actuel' => 'decimal:2',
        'cloturable' => 'boolean',
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

    public function pv()
    {
        return $this->belongsTo(Pv::class);
    }

    public function avis()
    {
        return $this->belongsTo(Avis::class);
    }

    public function fournisseur()
    {
        return $this->belongsTo(Fournisseur::class);
    }

    public function compteBudget()
    {
        return $this->belongsTo(CompteBudget::class, 'compte_budget_id');
    }

    public function agent()
    {
        return $this->belongsTo(User::class, 'agent_id');
    }

    public function etapes()
    {
        return $this->hasMany(ContratEtape::class)->orderBy('id');
    }

    public function engagements()
    {
        return $this->hasMany(Engagement::class);
    }

    public function avenants()
    {
        return $this->hasMany(Avenant::class)->orderBy('id');
    }

    public function ordreServices()
    {
        return $this->hasMany(OrdreService::class, 'contrat_id')->orderBy('date_emission', 'desc');
    }

    public function receptions()
    {
        return $this->hasMany(Reception::class, 'contrat_id')->orderBy('date_reception', 'desc');
    }

    public function piecesJointes()
    {
        return $this->morphMany(PieceJointe::class, 'entite');
    }

    public function documents()
    {
        return $this->morphMany(Document::class, 'documentable');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
