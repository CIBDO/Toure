<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class DomaineActivite extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'domaines_activite';

    protected $fillable = [
        'uuid', 'code', 'libelle', 'description', 'actif', 'created_by',
    ];

    protected $casts = [
        'actif' => 'boolean',
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
        return $this->hasMany(Fournisseur::class, 'domaine_activite_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
