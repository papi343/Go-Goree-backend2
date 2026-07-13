<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Nouvelle demande de résidence</title>
</head>
<body style="font-family: Arial, sans-serif; color: #1f2937;">
    <h2>Nouvelle demande de résidence</h2>

    <p>Une nouvelle demande de résidence vient d'être soumise et attend votre validation.</p>

    <table cellpadding="6" style="border-collapse: collapse;">
        <tr><td><strong>Demandeur</strong></td><td>{{ $nomComplet }}</td></tr>
        <tr><td><strong>Email</strong></td><td>{{ $email }}</td></tr>
        <tr><td><strong>Carte d'identité</strong></td><td>{{ $demande->carte_identite }}</td></tr>
        <tr><td><strong>Résidence</strong></td><td>{{ $demande->residence }}</td></tr>
        <tr><td><strong>Référence demande</strong></td><td>{{ $demande->id }}</td></tr>
        <tr><td><strong>Soumise le</strong></td><td>{{ $demande->created_at }}</td></tr>
    </table>

    <p>Connectez-vous à l'espace administrateur pour valider ou refuser cette demande.</p>

    <p>— L'équipe Go Gorée</p>
</body>
</html>
