<?php

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
});
