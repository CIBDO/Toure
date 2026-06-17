<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Depouillement extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'uuid', 'reference', 'avis_id', 'compte_budget_id', 'date_depouillement',
        'heure_depouillement', 'lieu', 'resultats', 'statut', 'observations',
        'motif_rejet', 'notification_reunion_envoyee', 'fichier_bordereau', 'created_by',
    ];

    protected $casts = [
        'date_depouillement' => 'date',
        'resultats' => 'array',
        'notification_reunion_envoyee' => 'boolean',
    ];

    protected static function boot(): void
    {
        parent::boot();
        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = Str::uuid();
            }
            if (empty($model->reference)) {
                $year = now()->year;
                $count = static::withTrashed()->whereYear('created_at', $year)->count() + 1;
                $model->reference = sprintf('OPL-%d-%03d', $year, $count);
            }
        });
    }

    public function avis()
    {
        return $this->belongsTo(Avis::class);
    }

    public function compteBudget()
    {
        return $this->belongsTo(CompteBudget::class, 'compte_budget_id');
    }

    public function pvs()
    {
        return $this->hasMany(Pv::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
