<?php

use App\Http\Controllers\Api\V1\Residents\AbonnementController;
use App\Http\Controllers\Api\V1\Residents\DemandeResidenceController;
use App\Http\Controllers\Api\V1\Residents\PlanController;
use App\Http\Controllers\Api\V1\Residents\ResidentController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    // Un client peut soumettre et consulter (la liste/détail est filtrée dans le contrôleur).
    Route::apiResource('demandes-residence', DemandeResidenceController::class)
        ->only(['index', 'store', 'show']);

    // Plans d'abonnement : consultation ouverte.
    Route::get('plans', [PlanController::class, 'index']);

    // Souscription d'abonnement par un résident.
    Route::post('abonnements/souscrire', [AbonnementController::class, 'souscrire']);

    // Actions administratives.
    Route::middleware('role:Admin')->group(function () {
        Route::post('demandes-residence/{id}/valider', [DemandeResidenceController::class, 'valider']);
        Route::post('demandes-residence/{id}/refuser', [DemandeResidenceController::class, 'refuser']);
        Route::apiResource('demandes-residence', DemandeResidenceController::class)
            ->only(['update', 'destroy']);

        Route::apiResource('residents', ResidentController::class);
        Route::apiResource('abonnements', AbonnementController::class);

        // Gestion des plans (création/modification/suppression).
        Route::post('plans', [PlanController::class, 'store']);
        Route::get('plans/{id}', [PlanController::class, 'show']);
        Route::put('plans/{id}', [PlanController::class, 'update']);
        Route::delete('plans/{id}', [PlanController::class, 'destroy']);
    });
});
