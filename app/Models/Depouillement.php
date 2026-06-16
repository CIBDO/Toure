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
        'uuid', 'reference', 'avis_id', 'date_depouillement',
        'lieu', 'resultats', 'statut', 'observations', 'motif_rejet', 'created_by',
    ];

    protected $casts = [
        'date_depouillement' => 'date',
        'resultats' => 'array',
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

    public function avis()
    {
        return $this->belongsTo(Avis::class);
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
