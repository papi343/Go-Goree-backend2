<?php

use App\Http\Controllers\Api\V1\Voyages\ChaloupeController;
use App\Http\Controllers\Api\V1\Voyages\TarifController;
use App\Http\Controllers\Api\V1\Voyages\TrajetController;
use App\Http\Controllers\Api\V1\Voyages\VoyageController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    // Lecture : ouverte à tout utilisateur authentifié (nécessaire pour acheter).
    Route::apiResource('voyages', VoyageController::class)->only(['index', 'show']);
    Route::apiResource('trajets', TrajetController::class)->only(['index', 'show']);
    Route::apiResource('chaloupes', ChaloupeController::class)->only(['index', 'show']);
    Route::apiResource('tarifs', TarifController::class)->only(['index', 'show']);

    // Écriture (création/modification/suppression) : réservée aux administrateurs.
    Route::middleware('role:Admin')->group(function () {
        Route::post('voyages/generer', [VoyageController::class, 'generer']);
        Route::apiResource('voyages', VoyageController::class)->except(['index', 'show']);
        Route::apiResource('trajets', TrajetController::class)->except(['index', 'show']);
        Route::apiResource('chaloupes', ChaloupeController::class)->except(['index', 'show']);
        Route::apiResource('tarifs', TarifController::class)->except(['index', 'show']);
    });
});
