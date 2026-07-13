<?php

use App\Http\Controllers\Api\V1\Users\ControleurController;
use App\Http\Controllers\Api\V1\Users\UserController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    // Comptes contrôleurs (agents) créés par un administrateur.
    Route::get('controleurs', [ControleurController::class, 'index']);
    Route::post('controleurs', [ControleurController::class, 'store']);

    Route::apiResource('users', UserController::class);
});
