<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PV {{ $pv->reference }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #333; margin: 0; padding: 20px; }
        .header { text-align: center; border-bottom: 2px solid #1a237e; padding-bottom: 15px; margin-bottom: 20px; }
        .header h1 { color: #1a237e; font-size: 16px; margin: 5px 0; }
        .header h2 { color: #333; font-size: 14px; margin: 5px 0; }
        .badge { display: inline-block; padding: 3px 10px; border-radius: 4px; font-size: 11px; font-weight: bold; }
        .badge-attribution { background: #e8f5e9; color: #2e7d32; border: 1px solid #2e7d32; }
        .badge-infructueux { background: #fff3e0; color: #e65100; border: 1px solid #e65100; }
        .badge-annulation { background: #fce4ec; color: #c62828; border: 1px solid #c62828; }
        .section { margin-bottom: 20px; }
        .section-title { background: #1a237e; color: white; padding: 6px 12px; font-weight: bold; font-size: 12px; margin-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        table th { background: #e8eaf6; padding: 6px 10px; text-align: left; border: 1px solid #c5cae9; font-size: 11px; }
        table td { padding: 6px 10px; border: 1px solid #e0e0e0; font-size: 11px; }
        .label { font-weight: bold; width: 35%; }
        .montant { font-size: 14px; font-weight: bold; color: #1a237e; }
        .footer { margin-top: 40px; border-top: 1px solid #ccc; padding-top: 15px; }
        .signatures { display: flex; justify-content: space-between; margin-top: 30px; }
        .signature-box { text-align: center; width: 30%; }
        .signature-line { border-top: 1px solid #333; margin-top: 40px; padding-top: 5px; font-size: 11px; }
        .contenu-box { background: #f5f5f5; border: 1px solid #e0e0e0; padding: 12px; border-radius: 4px; line-height: 1.6; }
    </style>
</head>
<body>

<div class="header">
    <h1>CAISSE NATIONALE D'ASSURANCE MALADIE (CANAM)</h1>
    <h2>PROCÈS-VERBAL D'{{ strtoupper($pv->type_pv === 'attribution' ? 'ATTRIBUTION' : ($pv->type_pv === 'infructueux' ? 'APPEL D\'OFFRES INFRUCTUEUX' : 'ANNULATION')) }}</h2>
    <p>Référence : <strong>{{ $pv->reference }}</strong> &nbsp;&nbsp;
    <span class="badge badge-{{ $pv->type_pv }}">{{ strtoupper($pv->type_pv) }}</span></p>
</div>

<div class="section">
    <div class="section-title">INFORMATIONS GÉNÉRALES</div>
    <table>
        <tr>
            <td class="label">Référence PV</td>
            <td>{{ $pv->reference }}</td>
            <td class="label">Date du PV</td>
            <td>{{ $pv->date_pv?->format('d/m/Y') }}</td>
        </tr>
        <tr>
            <td class="label">Avis de référence</td>
            <td>{{ $pv->avis?->reference }}</td>
            <td class="label">Mode de passation</td>
            <td>{{ $pv->avis?->mode_passation }}</td>
        </tr>
        <tr>
            <td class="label">Objet du marché</td>
            <td colspan="3">{{ $pv->avis?->objet }}</td>
        </tr>
        <tr>
            <td class="label">Exercice</td>
            <td>{{ $pv->avis?->exercice }}</td>
            <td class="label">Statut</td>
            <td><strong>{{ strtoupper($pv->statut) }}</strong></td>
        </tr>
        @if($pv->avis?->delai || $pv->avis?->date_publication)
        <tr>
            @if($pv->avis?->delai)
            <td class="label">Délai d'exécution (jours)</td>
            <td>{{ $pv->avis->delai }}</td>
            @else
            <td class="label"></td>
            <td></td>
            @endif
            @if($pv->avis?->date_publication)
            <td class="label">Date de publication</td>
            <td>{{ $pv->avis->date_publication->format('d/m/Y') }}</td>
            @else
            <td class="label"></td>
            <td></td>
            @endif
        </tr>
        @endif
    </table>
</div>

@if($pv->type_pv === 'attribution' && $pv->fournisseurAttributaire)
<div class="section">
    <div class="section-title">ATTRIBUTAIRE DU MARCHÉ</div>
    <table>
        <tr>
            <td class="label">Raison sociale</td>
            <td><strong>{{ $pv->fournisseurAttributaire->raison_sociale }}</strong></td>
            <td class="label">NIF</td>
            <td>{{ $pv->fournisseurAttributaire->nif ?? 'N/A' }}</td>
        </tr>
        <tr>
            <td class="label">RC</td>
            <td>{{ $pv->fournisseurAttributaire->rc ?? 'N/A' }}</td>
            <td class="label">Représentant</td>
            <td>{{ $pv->fournisseurAttributaire->representant ?? 'N/A' }}</td>
        </tr>
        <tr>
            <td class="label">Adresse</td>
            <td colspan="3">{{ $pv->fournisseurAttributaire->adresse }}, {{ $pv->fournisseurAttributaire->ville }}</td>
        </tr>
    </table>
    @if($pv->montant_retenu)
    <p class="montant">Montant retenu : {{ number_format($pv->montant_retenu, 0, ',', ' ') }} {{ 'GNF' }}</p>
    @endif
</div>
@endif

@if($pv->avis && $pv->avis->items->count() > 0)
<div class="section">
    <div class="section-title">FOURNITURES / PRESTATIONS</div>
    <table>
        <thead>
            <tr>
                <th>N°</th>
                <th>Désignation</th>
                <th>Quantité</th>
                <th>Unité</th>
            </tr>
        </thead>
        <tbody>
            @foreach($pv->avis->items as $item)
            <tr>
                <td>{{ $item->ordre }}</td>
                <td>{{ $item->designation }}</td>
                <td>{{ $item->quantite }}</td>
                <td>{{ $item->unite ?? '-' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif

@if($pv->contenu)
<div class="section">
    <div class="section-title">DÉCISION DE LA COMMISSION</div>
    <div class="contenu-box">{{ $pv->contenu }}</div>
</div>
@endif

@if($pv->observations)
<div class="section">
    <div class="section-title">OBSERVATIONS</div>
    <div class="contenu-box">{{ $pv->observations }}</div>
</div>
@endif

<div class="footer">
    <p>Fait à Bamako, le {{ $pv->date_pv?->format('d/m/Y') }}</p>
    <div class="signatures">
        <div class="signature-box">
            <div class="signature-line">Le Président de la Commission</div>
        </div>
        <div class="signature-box">
            <div class="signature-line">Le Secrétaire</div>
        </div>
        <div class="signature-box">
            <div class="signature-line">Le Directeur Général CANAM</div>
        </div>
    </div>
    <p style="text-align:center; color:#999; font-size:10px; margin-top:30px;">
        Document généré le {{ now()->format('d/m/Y à H:i') }} - CANAM Contract Manager
    </p>
</div>

</body>
</html>
