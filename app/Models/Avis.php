<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Avis extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'avis';

    protected $fillable = [
        'uuid', 'reference', 'objet', 'mode_passation',
        'article_pour', 'article_relatif', 'exercice',
        'duree', 'delai', 'date_limite_depot', 'date_ouverture_plis', 'date_publication',
        'statut', 'observations', 'motif_rejet', 'created_by',
    ];

    protected $casts = [
        'date_limite_depot' => 'date',
        'date_ouverture_plis' => 'date',
        'date_publication' => 'date',
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

    public function fournisseurs()
    {
        return $this->belongsToMany(Fournisseur::class, 'avis_fournisseurs')
            ->withPivot(['date_invitation', 'a_soumis', 'date_soumission'])
            ->withTimestamps();
    }

    public function items()
    {
        return $this->hasMany(AvisItem::class)->orderBy('ordre');
    }

    public function depouillements()
    {
        return $this->hasMany(Depouillement::class);
    }

    public function pvs()
    {
        return $this->hasMany(Pv::class);
    }

    public function contrats()
    {
        return $this->hasMany(Contrat::class);
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
