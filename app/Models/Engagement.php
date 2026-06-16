<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Engagement extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'uuid', 'contrat_id', 'numero', 'date_engagement', 'exercice',
        'compte_budget_id', 'montant_engage', 'statut',
        'commentaire_validation', 'created_by', 'approved_by', 'approved_at',
    ];

    protected $casts = [
        'date_engagement' => 'date',
        'approved_at'     => 'datetime',
        'montant_engage'  => 'integer',
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

    public function compteBudget()
    {
        return $this->belongsTo(CompteBudget::class, 'compte_budget_id');
    }

    public function paiements()
    {
        return $this->hasMany(Paiement::class);
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
}
