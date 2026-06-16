<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReceptionItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'reception_id', 'contrat_item_id', 'label',
        'quantite_prevue', 'quantite_recue', 'conforme', 'observation',
    ];

    protected $casts = [
        'quantite_prevue' => 'decimal:4',
        'quantite_recue' => 'decimal:4',
        'conforme' => 'boolean',
    ];

    public function reception()
    {
        return $this->belongsTo(Reception::class);
    }
}
