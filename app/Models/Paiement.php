<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Paiement extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'uuid', 'engagement_id', 'reference', 'date_paiement', 'montant',
        'mode_paiement', 'banque_id', 'observation', 'statut',
        'commentaire_validation', 'created_by', 'approved_by', 'approved_at',
    ];

    protected $casts = [
        'date_paiement' => 'date',
        'approved_at'   => 'datetime',
        'montant'       => 'integer',
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

    public function engagement()
    {
        return $this->belongsTo(Engagement::class);
    }

    public function banque()
    {
        return $this->belongsTo(Banque::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function documents()
    {
        return $this->morphMany(Document::class, 'documentable');
    }
}
