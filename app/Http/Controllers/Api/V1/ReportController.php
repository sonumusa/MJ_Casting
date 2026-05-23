<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Services\LedgerService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function __construct(
        private LedgerService $ledgerService
    ) {}

    /**
     * Get daily report
     */
    public function daily(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'date' => 'required|date',
        ]);

        $report = $this->ledgerService->getDailyReport(Carbon::parse($validated['date']));

        return response()->json([
            'success' => true,
            'data' => $report,
        ]);
    }

    /**
     * Get customer report
     */
    public function customer(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'from_date' => 'required|date',
            'to_date' => 'required|date|after_or_equal:from_date',
        ]);

        $customer = Customer::find($validated['customer_id']);
        $report = $this->ledgerService->getCustomerReport(
            $customer,
            Carbon::parse($validated['from_date']),
            Carbon::parse($validated['to_date'])
        );

        return response()->json([
            'success' => true,
            'data' => $report,
        ]);
    }
}
