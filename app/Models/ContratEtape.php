<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContratEtape extends Model
{
    protected $table = 'contrat_etapes';

    protected $fillable = [
        'contrat_id', 'type_etape', 'date_prevue', 'date_limite', 'date_effective',
        'statut', 'commentaire', 'piece_jointe', 'updated_by',
    ];

    protected $casts = [
        'date_prevue'    => 'date',
        'date_limite'    => 'date',
        'date_effective' => 'date',
    ];

    public function isEnRetard(): bool
    {
        return $this->date_limite
            && !in_array($this->statut, ['completed'])
            && now()->gt($this->date_limite);
    }

    public function contrat()
    {
        return $this->belongsTo(Contrat::class);
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
