<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Détails de votre transfert</title>
    <style>
        body { font-family: Arial, Helvetica, sans-serif; line-height: 1.6; background:#f8f9fa; color:#343a40; padding:20px; }
        h1 { color:#6366F1; margin-bottom: 6px; }
        .logo_name { color:#6366F1; font-weight:900; }
        table { width:100%; border-collapse:collapse; margin-top:20px; background:#fff; }
        table, th, td { border:1px solid #dee2e6; }
        th, td { padding:10px; text-align:left; }
        th { background:#f1f1f1; width: 35%; }
        p { margin:10px 0; }
        .footer { margin-top:30px; font-size:12px; color:#6c757d; }
    </style>
</head>
<body>
    <h1>Détails du transfert</h1>
    <p>
        Bonjour
        {{ optional($transfert->user)->nom_complet
            ?? optional($transfert->user)->name
            ?? optional($transfert->user)->email
            ?? '' }} ,
    </p>

    <p>Voici les détails de votre transfert :</p>

    @php
        $devSrc = optional($transfert->deviseSource)->tag ?? 'EUR';
        $devDst = optional($transfert->deviseCible)->tag ?? 'GNF';
        $benef  = optional($transfert->beneficiaire);
        $benefNomComplet = trim(($benef->prenom ?? '').' '.($benef->nom ?? '')) ?: ($benef->nom_complet ?? '—');
        $taux  = optional($transfert->tauxEchange)->taux;
    @endphp

    <table>
        {{--  <tr>
            <th>Montant envoyé</th>
            <td>{{ number_format((float)$transfert->montant_euro, 2, ',', ' ') }} {{ $devSrc }}</td>
        </tr>  --}}
         <tr>
            <th>Total débité</th>
            <td><strong>{{ number_format((float)($transfert->total ?? ($transfert->total_eur + ($transfert->frais ?? 0))), 2, ',', ' ') }} {{ $devSrc }}</strong></td>
        </tr>
{{--          
        <tr>
            <th>Frais</th>
            <td>{{ number_format((float)($transfert->frais ?? 0), 2, ',', ' ') }} {{ $devSrc }}</td>
        </tr>  --}}
        <tr>
            <th>Bénéficiaire</th>
            <td>
                {{ $benefNomComplet }}<br>
                {{ $benef->phone ?? '—' }}
            </td>
        </tr>
        <tr>
            <th>Montant à récupérer</th>
            <td>{{ number_format((float)$transfert->montant_gnf, 0, ',', ' ') }} {{ $devDst }}</td>
        </tr>
       
        <tr>
            <th>Code de retrait</th>
            <td>{{ $transfert->code }}</td>
        </tr>
        <tr>
            <th>Date d’envoi</th>
            <td>{{ optional($transfert->created_at)->format('d/m/Y H:i') }}</td>
        </tr>
    </table>

    <p>Merci d’utiliser notre service de transfert.</p>
    <p>Cordialement,</p>
    <p>L’équipe <span class="logo_name">DSPAY</span></p>

    <p class="footer">
        Pour toute question, contactez notre support client :<br>
        Téléphone : +33 7 58 85 50 39<br>
        Email : <a href="mailto:contact@dspay.com">contact@dspay.com</a>
    </p>
</body>
</html>
