<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Rapport Situation Financière - CANAM</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #333; margin: 0; padding: 20px; }
        .header { text-align: center; border-bottom: 2px solid #1a237e; padding-bottom: 10px; margin-bottom: 15px; }
        .header h1 { color: #1a237e; font-size: 14px; margin: 3px 0; }
        .section-title { background: #2e7d32; color: white; padding: 5px 10px; font-weight: bold; font-size: 11px; margin: 10px 0 5px; }
        table { width: 100%; border-collapse: collapse; font-size: 10px; }
        table th { background: #c8e6c9; padding: 5px 8px; text-align: left; border: 1px solid #a5d6a7; }
        table td { padding: 5px 8px; border: 1px solid #e0e0e0; }
        .montant { text-align: right; }
        .footer { margin-top: 20px; border-top: 1px solid #ccc; padding-top: 10px; font-size: 9px; color: #666; text-align: center; }
    </style>
</head>
<body>
<div class="header">
    <h1>CAISSE NATIONALE D'ASSURANCE MALADIE (CANAM)</h1>
    <p>Rapport Situation Financière — Généré le {{ now()->format('d/m/Y à H:i') }}</p>
</div>

<div class="section-title">Détail des contrats</div>
<table>
    <thead>
        <tr>
            <th>Référence</th>
            <th>Fournisseur</th>
            <th class="montant">Montant initial</th>
            <th class="montant">Montant actuel</th>
            <th>Statut</th>
        </tr>
    </thead>
    <tbody>
        @foreach($data ?? [] as $c)
        <tr>
            <td>{{ $c->reference ?? $c->numero ?? '-' }}</td>
            <td>{{ $c->fournisseur->raison_sociale ?? '-' }}</td>
            <td class="montant">{{ number_format($c->montant_initial ?? 0, 0, ',', ' ') }}</td>
            <td class="montant">{{ number_format($c->montant_actuel ?? $c->montant_initial ?? 0, 0, ',', ' ') }}</td>
            <td>{{ $c->statut ?? '-' }}</td>
        </tr>
        @endforeach
    </tbody>
</table>

<div class="footer">CANAM Contract Manager — Rapport Situation Financière</div>
</body>
</html>
