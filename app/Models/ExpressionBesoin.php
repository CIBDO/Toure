<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class ExpressionBesoin extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'expressions_besoin';

    protected $fillable = [
        'uuid', 'code', 'libelle', 'description', 'unite_defaut',
        'domaine_activite_id', 'actif', 'created_by',
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

    public function domaineActivite()
    {
        return $this->belongsTo(DomaineActivite::class, 'domaine_activite_id');
    }

    public function avisItems()
    {
        return $this->hasMany(AvisItem::class, 'expression_besoin_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
