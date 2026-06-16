<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

class Fournisseur extends Model
{
    use HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'uuid', 'code', 'civilite', 'qualite_fonction',
        'raison_sociale', 'sigle', 'nif', 'rc',
        'telephone', 'fax', 'email', 'adresse', 'ville', 'region', 'pays',
        'representant', 'fonction_representant',
        'domaine_activite_id', 'modes_passation', 'duree_min', 'duree_max',
        'statut', 'observations', 'created_by',
    ];

    protected $casts = [
        'modes_passation' => 'array',
        'duree_min' => 'integer',
        'duree_max' => 'integer',
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

    public function domaineActivite()
    {
        return $this->belongsTo(DomaineActivite::class, 'domaine_activite_id');
    }

    public function banques()
    {
        return $this->hasMany(FournisseurBanque::class);
    }

    public function avis()
    {
        return $this->belongsToMany(Avis::class, 'avis_fournisseurs')
            ->withPivot(['date_invitation', 'a_soumis', 'date_soumission'])
            ->withTimestamps();
    }

    public function contrats()
    {
        return $this->hasMany(Contrat::class);
    }

    public function pvs()
    {
        return $this->hasMany(Pv::class, 'fournisseur_attributaire_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
