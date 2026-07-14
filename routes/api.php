<?php

use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    require __DIR__.'/api/v1/auth.php';
    require __DIR__.'/api/v1/users.php';
    require __DIR__.'/api/v1/residents.php';
    require __DIR__.'/api/v1/billetterie.php';
    require __DIR__.'/api/v1/voyages.php';
    require __DIR__.'/api/v1/portefeuille.php';
    require __DIR__.'/api/v1/fraude.php';
    require __DIR__.'/api/v1/notifications.php';
    require __DIR__.'/api/v1/analytics.php';
});

// Le webhook PayDunya n'est PAS require ici, volontairement : ce fichier
// (routes/api.php) est celui passé en tant que api: __DIR__.'/../routes/api.php'
// dans bootstrap/app.php -> withRouting(), et Laravel enveloppe
// AUTOMATIQUEMENT tout ce fichier dans Route::prefix('api')->middleware('api').
// Si on requirait webhooks/paydunya.php ici, l'URL deviendrait
// /api/webhooks/paydunya au lieu de /webhooks/paydunya, et surtout on ne
// pourrait plus garantir "aucun groupe de middleware" comme l'exige
// docs/api.md §5bis.4/5bis.1 (ligne "paydunya.php → HORS groupe v1 et HORS
// middleware auth:sanctum"). Le webhook est donc enregistré séparément,
// via le paramètre then: de withRouting() dans bootstrap/app.php (voir
// 12_generate_providers_config.php), qui exécute son callback SANS
// aucun préfixe ni groupe de middleware automatique.
