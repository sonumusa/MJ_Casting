<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\LedgerService;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    public function __construct(
        private LedgerService $ledgerService
    ) {}

    /**
     * Get dashboard statistics
     */
    public function stats(): JsonResponse
    {
        $stats = $this->ledgerService->getDashboardStats();

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }
}
