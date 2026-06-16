<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FournisseurBanque extends Model
{
    protected $table = 'fournisseur_banques';

    protected $fillable = [
        'fournisseur_id', 'banque_id', 'numero_compte', 'rib', 'swift', 'iban',
        'intitule_compte', 'principal',
    ];

    protected $casts = [
        'principal' => 'boolean',
    ];

    public function fournisseur()
    {
        return $this->belongsTo(Fournisseur::class);
    }

    public function banque()
    {
        return $this->belongsTo(Banque::class);
    }
}
