<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class PieceJointe extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'pieces_jointes';

    protected $fillable = [
        'uuid', 'entite_type', 'entite_id',
        'nom_original', 'nom_stockage', 'chemin',
        'type_mime', 'taille', 'categorie', 'description', 'created_by',
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

    public function entite()
    {
        return $this->morphTo();
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
