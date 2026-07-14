<?php

namespace App\Http\Controllers\Api\V1\Analytics;

use App\Http\Controllers\Controller;
use App\Services\Analytics\AnalyticsService;
use Illuminate\Http\JsonResponse;

class AnalyticsController extends Controller
{
    public function __construct(protected AnalyticsService $analyticsService) {}

    /**
     * Return general and visitor dashboard analytics.
     */
    public function getDashboardMetrics(): JsonResponse
    {
        $data = $this->analyticsService->getDashboardMetrics();
        return response()->json($data);
    }

    /**
     * Return transaction and wallet analytics.
     */
    public function getTransactionMetrics(): JsonResponse
    {
        $data = $this->analyticsService->getTransactionMetrics();
        return response()->json($data);
    }
}
