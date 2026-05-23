<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\GoldReceipt;
use App\Models\GoldReceiptItem;
use App\Services\GoldCalculationService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GoldReceiptController extends Controller
{
    public function __construct(
        private GoldCalculationService $calcService
    ) {}

    public function index(Request $request): View
    {
        $query = GoldReceipt::with('customer');

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('receipt_no', 'like', "%{$search}%")
                  ->orWhereHas('customer', fn($cq) => $cq->where('name', 'like', "%{$search}%"));
            });
        }

        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->input('customer_id'));
        }

        if ($request->filled('receipt_type')) {
            $query->where('receipt_type', $request->input('receipt_type'));
        }

        if ($request->filled('from_date')) {
            $query->whereDate('receipt_date', '>=', $request->input('from_date'));
        }

        if ($request->filled('to_date')) {
            $query->whereDate('receipt_date', '<=', $request->input('to_date'));
        }

        $receipts = $query->latest('receipt_date')->latest('id')->paginate(25)->withQueryString();
        $customers = Customer::orderBy('name')->get();

        return view('pages.gold-receipts.index', compact('receipts', 'customers'));
    }

    public function create(): View
    {
        $customers = Customer::orderBy('name')->get();
        $nextReceiptNo = $this->generateReceiptNo();

        return view('pages.gold-receipts.create', compact('customers', 'nextReceiptNo'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'receipt_type' => 'required|in:customer,dukandar,karigar',
            'receipt_date' => 'required|date',
            'remarks' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.description' => 'nullable|string|max:255',
            'items.*.gross_weight' => 'required|numeric|min:0',
            'items.*.ratti_impurity' => 'required|numeric|min:0',
        ]);

        try {
            DB::beginTransaction();

            $totalGross = 0;
            $totalKhalis = 0;

            $receipt = GoldReceipt::create([
                'receipt_no' => $this->generateReceiptNo(),
                'customer_id' => $validated['customer_id'],
                'receipt_type' => $validated['receipt_type'],
                'receipt_date' => $validated['receipt_date'],
                'remarks' => $validated['remarks'] ?? null,
                'total_gross_weight' => 0,
                'total_khalis_weight' => 0,
                'created_by' => Auth::id(),
            ]);

            foreach ($validated['items'] as $item) {
                $gross = (float) $item['gross_weight'];
                $ratti = (float) $item['ratti_impurity'];
                $khalis = $this->calcService->convertToKhalis($gross, $ratti);

                GoldReceiptItem::create([
                    'receipt_id' => $receipt->id,
                    'description' => $item['description'] ?? null,
                    'gross_weight' => $gross,
                    'ratti_impurity' => $ratti,
                    'khalis_weight' => $khalis,
                ]);

                $totalGross += $gross;
                $totalKhalis += $khalis;
            }

            $receipt->update([
                'total_gross_weight' => round($totalGross, 3),
                'total_khalis_weight' => round($totalKhalis, 3),
            ]);

            DB::commit();

            return redirect()
                ->route('gold-receipts.show', $receipt)
                ->with('success', "Receipt {$receipt->receipt_no} created successfully.")
                ->with('print_receipt_id', $receipt->id);

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Gold receipt store failed', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);

            return back()
                ->withInput()
                ->with('error', 'Failed to save receipt. ' . $e->getMessage());
        }
    }

    public function show(GoldReceipt $goldReceipt): View
    {
        $goldReceipt->load('customer', 'items');
        return view('pages.gold-receipts.show', compact('goldReceipt'));
    }

    public function edit(GoldReceipt $goldReceipt): View
    {
        $goldReceipt->load('customer', 'items');
        $customers = Customer::orderBy('name')->get();

        return view('pages.gold-receipts.edit', compact('goldReceipt', 'customers'));
    }

    public function update(Request $request, GoldReceipt $goldReceipt): RedirectResponse
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'receipt_type' => 'required|in:customer,dukandar,karigar',
            'receipt_date' => 'required|date',
            'remarks' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.description' => 'nullable|string|max:255',
            'items.*.gross_weight' => 'required|numeric|min:0',
            'items.*.ratti_impurity' => 'required|numeric|min:0',
        ]);

        try {
            DB::beginTransaction();

            $goldReceipt->update([
                'customer_id' => $validated['customer_id'],
                'receipt_type' => $validated['receipt_type'],
                'receipt_date' => $validated['receipt_date'],
                'remarks' => $validated['remarks'] ?? null,
                'updated_by' => Auth::id(),
            ]);

            // Delete old items and recreate
            $goldReceipt->items()->delete();

            $totalGross = 0;
            $totalKhalis = 0;

            foreach ($validated['items'] as $item) {
                $gross = (float) $item['gross_weight'];
                $ratti = (float) $item['ratti_impurity'];
                $khalis = $this->calcService->convertToKhalis($gross, $ratti);

                GoldReceiptItem::create([
                    'receipt_id' => $goldReceipt->id,
                    'description' => $item['description'] ?? null,
                    'gross_weight' => $gross,
                    'ratti_impurity' => $ratti,
                    'khalis_weight' => $khalis,
                ]);

                $totalGross += $gross;
                $totalKhalis += $khalis;
            }

            $goldReceipt->update([
                'total_gross_weight' => round($totalGross, 3),
                'total_khalis_weight' => round($totalKhalis, 3),
            ]);

            DB::commit();

            return redirect()
                ->route('gold-receipts.show', $goldReceipt)
                ->with('success', "Receipt {$goldReceipt->receipt_no} updated successfully.");

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Gold receipt update failed', ['id' => $goldReceipt->id, 'error' => $e->getMessage()]);

            return back()
                ->withInput()
                ->with('error', 'Failed to update receipt. ' . $e->getMessage());
        }
    }

    public function destroy(GoldReceipt $goldReceipt): RedirectResponse
    {
        try {
            DB::beginTransaction();
            $receiptNo = $goldReceipt->receipt_no;
            $goldReceipt->delete();
            DB::commit();

            return redirect()
                ->route('gold-receipts.index')
                ->with('success', "Receipt {$receiptNo} deleted successfully.");
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Gold receipt delete failed', ['id' => $goldReceipt->id, 'error' => $e->getMessage()]);

            return back()->with('error', 'Failed to delete receipt. ' . $e->getMessage());
        }
    }

    public function print(GoldReceipt $goldReceipt): View
    {
        $goldReceipt->load('customer', 'items');

        $workshopSettings = [
            'name' => \App\Models\Setting::getSetting('workshop_name', 'M.J Casting'),
            'name_urdu' => \App\Models\Setting::getSetting('workshop_name_urdu', 'ایم جے کاسٹنگ'),
            'address' => \App\Models\Setting::getSetting('address', ''),
            'phone' => \App\Models\Setting::getSetting('phone', ''),
            'phone2' => \App\Models\Setting::getSetting('phone2', ''),
            'phone3' => \App\Models\Setting::getSetting('phone3', ''),
            'city' => \App\Models\Setting::getSetting('city', ''),
            'messenger' => \App\Models\Setting::getSetting('messenger', ''),
            'social' => \App\Models\Setting::getSetting('social', ''),
        ];

        return view('pages.gold-receipts.print', compact('goldReceipt', 'workshopSettings'));
    }

    private function generateReceiptNo(): string
    {
        $prefix = 'RCV';
        $lastReceipt = GoldReceipt::latest('id')->first();
        $number = ($lastReceipt?->id ?? 0) + 1;
        return $prefix . '-' . str_pad($number, 5, '0', STR_PAD_LEFT);
    }
}
