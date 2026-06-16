<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class CompteBudget extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'comptes_budget';

    protected $fillable = [
        'uuid', 'code', 'libelle', 'exercice',
        'montant_alloue', 'montant_engage', 'montant_disponible',
        'description', 'actif', 'created_by',
    ];

    protected $casts = [
        'actif' => 'boolean',
        'montant_alloue' => 'decimal:2',
        'montant_engage' => 'decimal:2',
        'montant_disponible' => 'decimal:2',
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

    public function contrats()
    {
        return $this->hasMany(Contrat::class, 'compte_budget_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
