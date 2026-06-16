<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Rapport Synthèse Contrats - CANAM</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #333; margin: 0; padding: 20px; }
        .header { text-align: center; border-bottom: 2px solid #1a237e; padding-bottom: 10px; margin-bottom: 15px; }
        .header h1 { color: #1a237e; font-size: 14px; margin: 3px 0; }
        .kpi { display: flex; flex-wrap: wrap; gap: 15px; margin-bottom: 15px; }
        .kpi-box { background: #e8eaf6; padding: 8px 12px; border-radius: 4px; min-width: 140px; }
        .kpi-box strong { display: block; font-size: 13px; color: #1a237e; }
        .section-title { background: #1a237e; color: white; padding: 5px 10px; font-weight: bold; font-size: 11px; margin: 10px 0 5px; }
        table { width: 100%; border-collapse: collapse; font-size: 10px; }
        table th { background: #c5cae9; padding: 5px 8px; text-align: left; border: 1px solid #9fa8da; }
        table td { padding: 5px 8px; border: 1px solid #e0e0e0; }
        .montant { text-align: right; }
        .footer { margin-top: 20px; border-top: 1px solid #ccc; padding-top: 10px; font-size: 9px; color: #666; text-align: center; }
    </style>
</head>
<body>
<div class="header">
    <h1>CAISSE NATIONALE D'ASSURANCE MALADIE (CANAM)</h1>
    <p>Rapport Synthèse Contrats — Généré le {{ now()->format('d/m/Y à H:i') }}</p>
</div>

@php $ind = $indicators ?? []; @endphp
{{-- <div class="kpi">
    <div class="kpi-box">Nombre total contrats <strong>{{ $ind['nombre_total_contrats'] ?? 0 }}</strong></div>
    <div class="kpi-box">Montant total (XOF) <strong>{{ number_format($ind['montant_total_contrats'] ?? 0, 0, ',', ' ') }}</strong></div>
    <div class="kpi-box">Total engagé <strong>{{ number_format($ind['total_engage'] ?? 0, 0, ',', ' ') }}</strong></div>
    <div class="kpi-box">Total payé <strong>{{ number_format($ind['total_paye'] ?? 0, 0, ',', ' ') }}</strong></div>
    <div class="kpi-box">Reste à payer <strong>{{ number_format($ind['reste_a_payer'] ?? 0, 0, ',', ' ') }}</strong></div>
</div> --}}

@if(!empty($repartition_par_statut))
<div class="section-title">Répartition par statut</div>
<table>
    <thead>
        <tr>
            <th>Statut</th>
            <th>Nombre</th>
            <th class="montant">Montant</th>
        </tr>
    </thead>
    <tbody>
        @foreach($repartition_par_statut as $r)
        <tr>
            <td>{{ $r['statut'] ?? '-' }}</td>
            <td>{{ $r['count'] ?? 0 }}</td>
            <td class="montant">{{ number_format($r['montant'] ?? 0, 0, ',', ' ') }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
@endif

<div class="section-title">Détail des contrats</div>
<table>
    <thead>
        <tr>
            <th>Référence</th>
            <th>Objet</th>
            <th>Fournisseur</th>
            <th class="montant">Montant initial</th>
            <th>Statut</th>
            <th>Exercice</th>
            <th>Date signature</th>
        </tr>
    </thead>
    <tbody>
        @foreach($data ?? [] as $c)
        <tr>
            <td>{{ $c->reference ?? $c->numero ?? '-' }}</td>
            <td>{{ Str::limit($c->objet ?? '-', 40) }}</td>
            <td>{{ $c->fournisseur->raison_sociale ?? '-' }}</td>
            <td class="montant">{{ number_format($c->montant_initial ?? 0, 0, ',', ' ') }}</td>
            <td>{{ $c->statut ?? '-' }}</td>
            <td>{{ $c->exercice ?? '-' }}</td>
            <td>{{ $c->date_signature ? $c->date_signature->format('d/m/Y') : '-' }}</td>
        </tr>
        @endforeach
    </tbody>
</table>

<div class="footer">CANAM Contract Manager — Rapport Synthèse Contrats</div>
</body>
</html>
