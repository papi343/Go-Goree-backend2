<?php

use App\Http\Controllers\Api\V1\Settings\ParametreController;
use App\Http\Controllers\Api\V1\Rapports\RapportController;
use App\Http\Controllers\Api\V1\Auth\PersonalAccessTokenController;
use App\Http\Controllers\Api\V1\Users\ControleurController;
use App\Http\Controllers\Api\V1\Users\UserController;
use Illuminate\Support\Facades\Route;

// Administration des comptes : réservée aux administrateurs.
Route::middleware(['auth:sanctum', 'role:Admin'])->group(function () {
    // Comptes contrôleurs (agents) créés par un administrateur.
    Route::get('controleurs', [ControleurController::class, 'index']);
    Route::post('controleurs', [ControleurController::class, 'store']);
    Route::post('controleurs/{id}/renvoyer-invitation', [ControleurController::class, 'resendInvitation']);

    Route::apiResource('users', UserController::class);

    // Paramètres généraux
    Route::get('settings', [ParametreController::class, 'index']);
    Route::put('settings', [ParametreController::class, 'update']);
    // Rapports
    Route::get('rapports', [RapportController::class, 'index']);
    Route::post('rapports', [RapportController::class, 'store']);
    Route::get('rapports/{id}/telecharger', [RapportController::class, 'telecharger']);
    // Clés API & Sessions actives
    Route::get('tokens', [PersonalAccessTokenController::class, 'index']);
    Route::post('tokens', [PersonalAccessTokenController::class, 'store']);
    Route::delete('tokens/{id}', [PersonalAccessTokenController::class, 'destroy']);
});
