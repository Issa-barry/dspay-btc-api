<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transfert Annulé</title>
</head>
<body>
    <h2>Bonjour {{ $transfert->expediteur_prenom }},</h2>
    <p>
        Nous vous informons que votre transfert avec le code <strong>{{ $transfert->code }}</strong> a été annulé.
    </p>
    <p>
        Si vous n'êtes pas à l'origine de cette action, veuillez nous contacter immédiatement.
    </p>
    <p>
        <a href="{{ url('/dashboard/transferts') }}" style="background-color: #008CBA; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px;">
            Voir mes transferts
        </a>
    </p>
    <p>
        Merci de votre confiance.<br>
        `L'équipe Support`.
    </p>
</body>
</html>
