<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Bordereau d'envoi — {{ $depouillement->reference }}</title>
    <style>
        body { font-family: 'dejavu sans', sans-serif; font-size: 12px; color: #333; margin: 0; padding: 20px; }
        .header { text-align: center; border-bottom: 2px solid #1a237e; padding-bottom: 15px; margin-bottom: 20px; }
        .header h1 { color: #1a237e; font-size: 16px; margin: 5px 0; }
        .header h2 { color: #333; font-size: 14px; margin: 5px 0; }
        .section { margin-bottom: 20px; }
        .section-title { background: #1a237e; color: white; padding: 6px 12px; font-weight: bold; font-size: 12px; margin-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        table th { background: #e8eaf6; padding: 6px 10px; text-align: left; border: 1px solid #c5cae9; font-size: 11px; }
        table td { padding: 6px 10px; border: 1px solid #e0e0e0; font-size: 11px; }
        .label { font-weight: bold; width: 30%; }
        .attributaire { background: #e8f5e9; font-weight: bold; }
        .footer { margin-top: 40px; border-top: 1px solid #ccc; padding-top: 15px; }
        .signatures { margin-top: 30px; }
        .signature-box { display: inline-block; text-align: center; width: 30%; vertical-align: top; }
        .signature-line { border-top: 1px solid #333; margin-top: 50px; padding-top: 5px; font-size: 11px; }
        .contenu-box { background: #f5f5f5; border: 1px solid #e0e0e0; padding: 12px; line-height: 1.6; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
    </style>
</head>
<body>

<div class="header">
    <h1>CAISSE NATIONALE D'ASSURANCE MALADIE (CANAM)</h1>
    <h2>BORDEREAU D'ENVOI — OUVERTURE DES PLIS</h2>
    <p>Référence : <strong>{{ $depouillement->reference }}</strong></p>
</div>

<div class="section">
    <div class="section-title">INFORMATIONS GÉNÉRALES</div>
    <table>
        <tr>
            <td class="label">Avis de référence</td>
            <td>{{ $depouillement->avis?->reference ?? 'N/A' }}</td>
            <td class="label">Mode de passation</td>
            <td>{{ $depouillement->avis?->mode_passation ?? 'N/A' }}</td>
        </tr>
        <tr>
            <td class="label">Objet du marché</td>
            <td colspan="3">{{ $depouillement->avis?->objet ?? 'N/A' }}</td>
        </tr>
        <tr>
            <td class="label">Date d'ouverture</td>
            <td>{{ $depouillement->date_depouillement?->format('d/m/Y') ?? 'N/A' }}</td>
            <td class="label">Heure d'ouverture</td>
            <td>{{ $depouillement->heure_depouillement ? substr($depouillement->heure_depouillement, 0, 5) : 'N/A' }}</td>
        </tr>
        <tr>
            <td class="label">Lieu</td>
            <td>{{ $depouillement->lieu ?? 'N/A' }}</td>
            <td class="label">N° compte budgétaire</td>
            <td>
                @if($depouillement->compteBudget)
                    {{ $depouillement->compteBudget->code }} — {{ $depouillement->compteBudget->libelle }}
                @else
                    N/A
                @endif
            </td>
        </tr>
        <tr>
            <td class="label">Exercice</td>
            <td>{{ $depouillement->avis?->exercice ?? 'N/A' }}</td>
            <td class="label">Nombre de plis reçus</td>
            <td><strong>{{ $resultats->count() }}</strong></td>
        </tr>
    </table>
</div>

@if($resultats->isNotEmpty())
<div class="section">
    <div class="section-title">RELEVÉ DES PLIS REÇUS</div>
    <table>
        <thead>
            <tr>
                <th class="text-center">Plis</th>
                <th>Fournisseur / Soumissionnaire</th>
                <th>Pièces fournies</th>
                <th class="text-right">Montant (CFA)</th>
                <th class="text-center">Note technique</th>
                <th class="text-center">Note financière</th>
                <th class="text-center">Attributaire</th>
            </tr>
        </thead>
        <tbody>
            @foreach($resultats as $r)
            @php
                $isAttributaire = !empty($r['attributaire']) || !empty($r['retenu']);
            @endphp
            <tr class="{{ $isAttributaire ? 'attributaire' : '' }}">
                <td class="text-center">{{ $r['rang'] ?? $loop->iteration }}</td>
                <td>{{ $r['fournisseur_nom'] ?? $r['fournisseur'] ?? 'N/A' }}</td>
                <td>{{ $r['pieces_fournies'] ?? '-' }}</td>
                <td class="text-right">{{ isset($r['montant']) ? number_format($r['montant'], 0, ',', ' ') : '-' }}</td>
                <td class="text-center">{{ $r['note_technique'] ?? ($r['note'] ?? '-') }}</td>
                <td class="text-center">{{ $r['note_financiere'] ?? '-' }}</td>
                <td class="text-center">{{ $isAttributaire ? 'OUI' : 'NON' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif

@if($attributaire)
<div class="section">
    <div class="section-title">ATTRIBUTAIRE RETENU</div>
    <table>
        <tr>
            <td class="label">Raison sociale</td>
            <td colspan="3"><strong>{{ $attributaire['fournisseur_nom'] ?? $attributaire['fournisseur'] ?? 'N/A' }}</strong></td>
        </tr>
        <tr>
            <td class="label">Montant proposé</td>
            <td>{{ isset($attributaire['montant']) ? number_format($attributaire['montant'], 0, ',', ' ') . ' CFA' : 'N/A' }}</td>
            <td class="label">Note technique</td>
            <td>{{ $attributaire['note_technique'] ?? ($attributaire['note'] ?? 'N/A') }}</td>
        </tr>
    </table>
</div>
@endif

@if($depouillement->observations)
<div class="section">
    <div class="section-title">OBSERVATIONS</div>
    <div class="contenu-box">{{ $depouillement->observations }}</div>
</div>
@endif

<div class="footer">
    <p>Fait à Bamako, le {{ $depouillement->date_depouillement?->format('d/m/Y') ?? now()->format('d/m/Y') }}</p>
    <div class="signatures">
        <div class="signature-box">
            <div class="signature-line">Le Président de la Commission</div>
        </div>
        <div class="signature-box">
            <div class="signature-line">Le Secrétaire</div>
        </div>
        <div class="signature-box">
            <div class="signature-line">Le Directeur des Marchés</div>
        </div>
    </div>
    <p style="text-align:center; color:#999; font-size:10px; margin-top:30px;">
        Document généré le {{ now()->format('d/m/Y à H:i') }} — CANAM Contract Manager
    </p>
</div>

</body>
</html>
