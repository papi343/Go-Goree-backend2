<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Go Gorée</title>
</head>
<body style="font-family: Arial, sans-serif; color: #1f2937;">
    <h2>Bonjour {{ $prenom }},</h2>

    @if ($invitation)
        <p>Un compte <strong>contrôleur</strong> vient d'être créé pour vous sur la plateforme Go Gorée.</p>
        <p>Pour l'activer, définissez votre mot de passe en cliquant sur le lien ci-dessous :</p>
    @else
        <p>Vous avez demandé la réinitialisation de votre mot de passe.</p>
        <p>Cliquez sur le lien ci-dessous pour en définir un nouveau :</p>
    @endif

    <p>
        <a href="{{ $lien }}" style="background:#0d9488;color:#fff;padding:10px 18px;border-radius:6px;text-decoration:none;">
            Définir mon mot de passe
        </a>
    </p>

    <p style="font-size: 13px; color:#6b7280;">
        Ou copiez ce lien : <br>{{ $lien }}
    </p>

    <p style="font-size: 13px; color:#6b7280;">
        Jeton (pour tests/API) : <code>{{ $token }}</code>
    </p>

    <p style="font-size: 13px; color:#6b7280;">
        Ce lien expire dans {{ $expireMinutes }} minutes. Si vous n'êtes pas à l'origine de cette demande, ignorez cet email.
    </p>

    <p>— L'équipe Go Gorée</p>
</body>
</html>
