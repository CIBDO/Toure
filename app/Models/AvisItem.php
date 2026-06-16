<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AvisItem extends Model
{
    protected $table = 'avis_items';

    protected $fillable = [
        'avis_id', 'expression_besoin_id', 'ordre', 'designation', 'description_detaillee',
        'quantite', 'unite', 'lieu',
    ];

    protected $casts = [
        'quantite' => 'decimal:2',
    ];

    public function avis()
    {
        return $this->belongsTo(Avis::class);
    }

    public function expressionBesoin()
    {
        return $this->belongsTo(ExpressionBesoin::class, 'expression_besoin_id');
    }
}
