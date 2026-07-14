<?php

use App\Http\Controllers\Api\V1\Analytics\AnalyticsController;
use Illuminate\Support\Facades\Route;

// Analyses administratives (KPIs, ventes, histogrammes, occupation, repartition).
Route::middleware(['auth:sanctum', 'role:Admin'])->group(function () {
    Route::get('analytics/dashboard', [AnalyticsController::class, 'getDashboardMetrics']);
    Route::get('analytics/transactions', [AnalyticsController::class, 'getTransactionMetrics']);
});
