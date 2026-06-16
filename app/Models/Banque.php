<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Banque extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'uuid', 'code', 'libelle', 'sigle', 'adresse',
        'telephone', 'email', 'actif', 'created_by',
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

    public function fournisseurBanques()
    {
        return $this->hasMany(FournisseurBanque::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
