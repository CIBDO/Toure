<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Pv extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'pvs';

    protected $fillable = [
        'uuid', 'reference', 'depouillement_id', 'avis_id',
        'fournisseur_attributaire_id', 'date_pv', 'type_pv',
        'montant_retenu', 'nb_soumission', 'contenu', 'statut',
        'fichier_pdf', 'fichier_pv_signe', 'date_signature',
        'observations', 'motif_rejet', 'created_by',
    ];

    protected $casts = [
        'date_pv'        => 'date',
        'date_signature' => 'datetime',
        'montant_retenu' => 'decimal:2',
        'nb_soumission'  => 'integer',
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

    public function depouillement()
    {
        return $this->belongsTo(Depouillement::class);
    }

    public function avis()
    {
        return $this->belongsTo(Avis::class);
    }

    public function fournisseurAttributaire()
    {
        return $this->belongsTo(Fournisseur::class, 'fournisseur_attributaire_id');
    }

    public function contrats()
    {
        return $this->hasMany(Contrat::class, 'pv_id');
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
