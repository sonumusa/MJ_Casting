@extends('layouts.app')

@section('title', 'Ledger')

@section('extra_css')
<style>
    .ledger-header {
        background-color: var(--bg-card);
        border: 1px solid var(--border-color);
        border-radius: 12px;
        padding: 24px;
        margin-bottom: 24px;
    }

    .summary-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 16px;
        margin-top: 24px;
    }
    .summary-box {
        background-color: var(--bg-surface);
        border: 1px solid var(--border-color);
        border-radius: 8px;
        padding: 16px;
        text-align: center;
    }
    .summary-label { font-size: 0.7rem; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 4px; }
    .summary-value { font-family: 'JetBrains Mono', monospace; font-size: 1.1rem; font-weight: 600; color: var(--text-primary); }
    
    .box-outstanding { border-bottom: 3px solid var(--error); }
    .box-cleared { border-bottom: 3px solid var(--success); }

    .header-urdu { display: block; font-size: 0.65rem; color: var(--gold-muted); margin-top: 2px; text-transform: none; letter-spacing: 0; }
    .rp-amt-col { background-color: rgba(56, 189, 248, 0.05); }

    .filter-row {
        display: flex;
        gap: 16px;
        align-items: flex-end;
        margin-bottom: 24px;
        padding: 16px;
        background-color: var(--bg-card);
        border: 1px solid var(--border-color);
        border-radius: 12px;
    }

    .totals-row {
        background-color: rgba(218, 165, 32, 0.05) !important;
        font-weight: 700;
    }
    .totals-row td {
        color: var(--gold-primary);
        border-top: 2px solid var(--border-color);
    }

    @media (max-width: 768px) {
        .summary-grid { grid-template-columns: repeat(2, 1fr); }
        .filter-row { flex-direction: column; align-items: stretch; }
    }
</style>
@endsection

@section('content')
    <div class="page-header" style="margin-bottom: 24px;">
        <div class="page-title-group">
            <h1>Customer Ledger</h1>
            <p class="font-urdu">گاہک کھاتہ</p>
        </div>
    </div>

    <!-- Customer Selector -->
    <div class="ledger-header">
        <form method="GET" action="{{ route('ledger.index') }}" id="ledger-form">
            <div class="form-group" style="margin-bottom: 0;">
                <label>Select Customer <span class="font-urdu">گاہک منتخب کریں</span></label>
                <div style="display: flex; gap: 12px;">
                    <select name="customer_id" class="filter-control" style="flex: 1;" onchange="this.form.submit()">
                        <option value="">Choose a customer...</option>
                        @foreach($customers as $customer)
                            <option value="{{ $customer->id }}" {{ request('customer_id') == $customer->id ? 'selected' : '' }}>
                                {{ $customer->name }}
                            </option>
                        @endforeach
                    </select>
                    <button type="submit" class="btn-gold">View Ledger</button>
                </div>
            </div>
        </form>

        @if($selectedCustomer && $ledgerData)
            <div style="margin-top: 20px; display: flex; align-items: center; gap: 20px;">
                <h2 style="font-family: 'Playfair Display', serif; color: var(--text-primary); margin: 0;">{{ $selectedCustomer->name }}</h2>
                <span class="user-role">{{ $ledgerData['invoices']->count() }} Invoices</span>
            </div>
            <div class="summary-grid">
                <div class="summary-box">
                    <div class="summary-label">Total Gold Given</div>
                    <div class="summary-value">{{ number_format($ledgerData['total_gold_khalis'], 3) }}g</div>
                </div>
                <div class="summary-box">
                    <div class="summary-label">Total Invoiced</div>
                    <div class="summary-value">{{ number_format($ledgerData['total_invoiced'], 3) }}g</div>
                </div>
                <div class="summary-box">
                    <div class="summary-label">Total Wasooli</div>
                    <div class="summary-value">{{ number_format($ledgerData['total_wasooli'], 3) }}g</div>
                </div>
                <div class="summary-box {{ $ledgerData['current_balance'] > 0 ? 'box-outstanding' : 'box-cleared' }}">
                    <div class="summary-label">Current Balance</div>
                    <div class="summary-value" style="color: {{ $ledgerData['current_balance'] > 0 ? 'var(--error)' : 'var(--success)' }};">
                        {{ number_format($ledgerData['current_balance'], 3) }}g
                    </div>
                </div>
            </div>
        @endif
    </div>

    @if($selectedCustomer)
        <!-- Date Filters -->
        <form method="GET" action="{{ route('ledger.index') }}" class="filter-row">
            <input type="hidden" name="customer_id" value="{{ $selectedCustomer->id }}">
            <div class="form-group" style="margin-bottom: 0; flex: 1;">
                <label>From Date</label>
                <input type="date" name="from_date" class="filter-control" value="{{ request('from_date') }}">
            </div>
            <div class="form-group" style="margin-bottom: 0; flex: 1;">
                <label>To Date</label>
                <input type="date" name="to_date" class="filter-control" value="{{ request('to_date') }}">
            </div>
            <div style="display: flex; gap: 8px;">
                <button type="submit" class="btn-gold" style="height: 38px;">Apply</button>
                <a href="{{ route('ledger.index', ['customer_id' => $selectedCustomer->id]) }}" class="btn-outline" style="height: 38px; padding: 8px 16px;">Reset</a>
            </div>
            <div style="flex: 1; text-align: right;">
                <button type="button" class="btn-outline" onclick="window.print()">
                    <i class="bi bi-printer"></i> Print Ledger
                </button>
            </div>
        </form>

        <!-- Ledger Table -->
        <div class="table-card">
            <div style="overflow-x: auto;">
                <table style="min-width: 1500px;">
                    <thead>
                        <tr>
                            <th style="width: 100px;">Date <span class="header-urdu font-urdu">تاریخ</span></th>
                            <th style="width: 80px;">No <span class="header-urdu font-urdu">نمبر شمار</span></th>
                            <th style="width: 100px;">Casting <span class="header-urdu font-urdu">کاسٹنگ</span></th>
                            <th style="width: 80px;">Waste <span class="header-urdu font-urdu">ویسٹ</span></th>
                            <th style="width: 100px;">Total <span class="header-urdu font-urdu">ٹوٹل</span></th>
                            <th style="width: 60px;">Ratti <span class="header-urdu font-urdu">رتی</span></th>
                            <th style="width: 80px;">Rate (RP) <span class="header-urdu font-urdu">ریٹ</span></th>
                            <th style="width: 100px;">Male Waste <span class="header-urdu font-urdu">میل ویسٹ</span></th>
                            <th style="width: 120px;">Gold Khalis <span class="header-urdu font-urdu">خالص سونا</span></th>
                            <th style="width: 120px;" class="rp-amt-col">RP Amt* <span class="header-urdu font-urdu">رقم</span></th>
                            <th style="width: 100px;">RP Maz (w) <span class="header-urdu font-urdu">پاسہ وزن</span></th>
                            <th style="width: 100px;">Cast Maz (w) <span class="header-urdu font-urdu">کاسٹ وزن</span></th>
                            <th style="width: 120px;">Grand Total <span class="header-urdu font-urdu">میزان</span></th>
                            <th style="width: 100px;">Wasooli <span class="header-urdu font-urdu">وصولی</span></th>
                            <th style="width: 120px;">Sabqa <span class="header-urdu font-urdu">سابقہ</span></th>
                            <th style="width: 120px;">Remaining <span class="header-urdu font-urdu">باقی</span></th>
                            <th>Remarks <span class="header-urdu font-urdu">ریمارکس</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($ledgerData['invoices'] as $index => $invoice)
                            <tr>
                                <td style="color: var(--text-secondary);">{{ $invoice->invoice_date->format('d-m-Y') }}</td>
                                <td class="font-mono" style="color: var(--gold-primary);" title="Invoice ID: {{ $invoice->invoice_no }}">
                                    {{ $index + 1 }}
                                    <div style="font-size: 0.6rem; color: var(--text-muted);">{{ $invoice->invoice_no }}</div>
                                </td>
                                <td class="font-mono">{{ number_format($invoice->casting_weight, 3) }}</td>
                                <td class="font-mono">{{ number_format($invoice->waste_weight, 3) }}</td>
                                <td class="font-mono">{{ number_format($invoice->total_weight, 3) }}</td>
                                <td class="font-mono">{{ number_format($invoice->ratti, 2) }}</td>
                                <td class="font-mono">{{ number_format($invoice->rp_rate, 2) }}</td>
                                <td class="font-mono">{{ number_format($invoice->male_waste, 3) }}</td>
                                <td class="font-mono" style="font-weight: 600; color: var(--gold-bright);">{{ number_format($invoice->gold_khalis, 3) }}</td>
                                <td class="font-mono rp-amt-col" style="color: var(--info);">{{ number_format($invoice->rp_amount, 2) }}</td>
                                <td class="font-mono">{{ number_format($invoice->rp_mazdori_weight, 3) }}</td>
                                <td class="font-mono">{{ number_format($invoice->casting_mazdori_weight, 3) }}</td>
                                <td class="font-mono" style="font-weight: 600;">{{ number_format($invoice->grand_total, 3) }}g</td>
                                <td class="font-mono">{{ number_format($invoice->wasooli, 3) }}g</td>
                                <td class="font-mono" style="color: var(--text-muted);">{{ number_format($invoice->previous_balance, 3) }}g</td>
                                <td class="font-mono" style="font-weight: 600; color: {{ $invoice->remaining_balance > 0 ? 'var(--error)' : 'var(--success)' }};">
                                    {{ number_format($invoice->remaining_balance, 3) }}g
                                </td>
                                <td style="font-size: 0.75rem; color: var(--text-muted);" title="{{ $invoice->remarks }}">
                                    {{ Str::limit($invoice->remarks, 20) }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="totals-row">
                            <td colspan="2">TOTALS</td>
                            <td class="font-mono">{{ number_format($ledgerData['total_casting'], 3) }}</td>
                            <td class="font-mono">{{ number_format($ledgerData['total_waste'], 3) }}</td>
                            <td class="font-mono">{{ number_format($ledgerData['total_weight'], 3) }}</td>
                            <td colspan="3"></td>
                            <td class="font-mono" style="color: var(--gold-bright);">{{ number_format($ledgerData['total_gold_khalis'], 3) }}</td>
                            <td colspan="3"></td>
                            <td class="font-mono">{{ number_format($ledgerData['total_invoiced'], 3) }}g</td>
                            <td class="font-mono">{{ number_format($ledgerData['total_wasooli'], 3) }}g</td>
                            <td></td>
                            <td class="font-mono" style="color: {{ $ledgerData['current_balance'] > 0 ? 'var(--error)' : 'var(--success)' }};">
                                {{ number_format($ledgerData['current_balance'], 3) }}g
                            </td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            <div style="padding: 12px 24px; font-size: 0.75rem; color: var(--text-muted);">
                * Fields marked with asterisk are for reference only and do not affect Grand Total calculations.
            </div>
        </div>
    @else
        <div class="card" style="text-align: center; padding: 60px; color: var(--text-muted);">
            <i class="bi bi-book" style="font-size: 4rem; opacity: 0.2; display: block; margin-bottom: 20px;"></i>
            <h3>Please select a customer to view their ledger.</h3>
            <p>Select a customer from the dropdown above to see transaction history and balances.</p>
        </div>
    @endif
@endsection
