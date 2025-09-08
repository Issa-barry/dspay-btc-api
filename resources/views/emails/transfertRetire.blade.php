<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Retrait du transfert effectué</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            background-color: #f8f9fa;
            color: #343a40;
            padding: 20px;
        }
        h1 {
            color: #007bff;
        }
        .logo_name {
            color: #007bff;
            font-weight: 900;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        table, th, td {
            border: 1px solid #dee2e6;
        }
        th, td {
            padding: 10px;
            text-align: left;
        }
        th {
            background-color: #f1f1f1;
        }
        p {
            margin: 10px 0;
        }
        .footer {
            margin-top: 30px;
            font-size: 12px;
            color: #6c757d;
        }
    </style>
</head>
<body>
    <h1>Retrait !</h1>

    <p>Bonjour {{ $transfert->expediteur_nom_complet }} ,</p>

    <p>{{ $transfert->receveur_nom_complet }} , a effectué le retrait de votre transfert.</p>

    <p><strong>Détails du transfert :</strong></p>
    
    <table> 
        <tr>
            <th>Montant envoyé :</th>
            <td>{{ $transfert->montant_expediteur }} {{ $transfert->deviseSource->tag }}</td>
        </tr>
        <tr>
            <th>Montant retiré :</th>
            <td>{{ $transfert->montant_receveur }} {{ $transfert->deviseCible->tag }}</td>
        </tr>
        <tr>
            <th>Quartier de retrait :</th>
            <td>{{ $transfert->quartier }}</td>
        </tr>
        <tr>
            <th>Code du transfert :</th>
            <td>{{ $transfert->code }}</td>
        </tr>
    </table>

    <p>Nous vous remercions d'avoir utilisé notre service de transfert.</p>

    <p>Cordialement,</p>
    <p>L'équipe de Transfert <span class="logo_name">FELLO</span></p>

    <p class="footer">
        Pour toute question, contactez notre support client :<br>
        Téléphone : +33 7 58 85 50 39<br>
        Email : <a href="mailto:contact@fello-consulting.fr">contact@fello-consulting.fr</a>
    </p>
</body>
</html>
