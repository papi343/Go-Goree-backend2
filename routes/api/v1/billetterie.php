<?php

use App\Http\Controllers\Api\V1\Billetterie\BilletController;
use App\Http\Controllers\Api\V1\Billetterie\EmbarquementController;
use App\Http\Controllers\Api\V1\Billetterie\PayementController;
use App\Http\Controllers\Api\V1\Billetterie\ScanController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    // Achat/consultation de billets (la liste/détail est filtrée dans le contrôleur).
    Route::apiResource('billets', BilletController::class)->only(['index', 'store', 'show']);

    // Embarquement & scan : réservés aux agents (contrôleurs) et admins.
    Route::middleware('role:Admin,Agent')->group(function () {
        Route::get('embarquements', [EmbarquementController::class, 'index']);
        Route::post('embarquements/ouvrir', [EmbarquementController::class, 'ouvrir']);
        Route::post('embarquements/{id}/fermer', [EmbarquementController::class, 'fermer']);

        Route::apiResource('scans', ScanController::class)->only(['index', 'store', 'show']);
    });

    // Gestion administrative des paiements.
    Route::middleware('role:Admin')->group(function () {
        Route::apiResource('payements', PayementController::class);
    });
});
