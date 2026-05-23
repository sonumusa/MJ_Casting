@extends('layouts.app')

@section('title', 'Customer Report')

@section('content')
    <div class="page-header" style="margin-bottom: 24px;">
        <div class="page-title-group">
            <h1>Customer Report <span class="font-urdu">گاہک کی رپورٹ</span></h1>
            <p style="color: var(--text-muted);">View activity for a specific customer</p>
        </div>
    </div>

    <div class="card" style="margin-bottom: 24px;">
        <form method="GET" style="display:grid; grid-template-columns: repeat(4, 1fr); gap:16px; align-items: flex-end;">
            <div class="form-group" style="margin-bottom: 0;">
                <label>Select Customer <span class="font-urdu">گاہک منتخب کریں</span></label>
                <select name="customer_id" required>
                    <option value="">Select customer</option>
                    @foreach($customers as $customer)
                        <option value="{{ $customer->id }}" {{ optional($selectedCustomer)->id == $customer->id ? 'selected' : '' }}>{{ $customer->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group" style="margin-bottom: 0;">
                <label>From Date <span class="font-urdu">تاریخ سے</span></label>
                <input type="date" name="from_date" value="{{ request('from_date', now()->startOfMonth()->toDateString()) }}" required>
            </div>
            <div class="form-group" style="margin-bottom: 0;">
                <label>To Date <span class="font-urdu">تاریخ تک</span></label>
                <input type="date" name="to_date" value="{{ request('to_date', now()->toDateString()) }}" required>
            </div>
            <div style="display: flex; gap: 8px;">
                <button type="submit" class="btn-gold" style="flex: 1;">
                    <i class="bi bi-search"></i> Get Report
                </button>
                @if($report)
                    <button type="button" class="btn-outline" onclick="window.print()">
                        <i class="bi bi-printer"></i>
                    </button>
                @endif
            </div>
        </form>
    </div>

    @if($report)
        <!-- Summary Stats -->
        <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 24px;">
            <div class="card" style="padding: 20px; text-align: center; margin-bottom: 0;">
                <div style="font-size: 0.7rem; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 8px;">Total Invoices</div>
                <div class="font-mono" style="font-size: 1.5rem; font-weight: 700; color: var(--text-primary);">{{ $report['total_invoices'] }}</div>
            </div>
            <div class="card" style="padding: 20px; text-align: center; margin-bottom: 0;">
                <div style="font-size: 0.7rem; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 8px;">Gold Weight</div>
                <div class="font-mono" style="font-size: 1.5rem; font-weight: 700; color: var(--gold-primary);">{{ number_format($report['total_gold_khalis'], 3) }}g</div>
            </div>
            <div class="card" style="padding: 20px; text-align: center; margin-bottom: 0;">
                <div style="font-size: 0.7rem; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 8px;">Total Amount</div>
                <div class="font-mono" style="font-size: 1.5rem; font-weight: 700; color: var(--text-primary);">{{ number_format($report['total_grand_total'], 3) }}g</div>
            </div>
            <div class="card" style="padding: 20px; text-align: center; margin-bottom: 0; border-bottom: 3px solid {{ $report['current_balance'] > 0 ? 'var(--error)' : 'var(--success)' }};">
                <div style="font-size: 0.7rem; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 8px;">Outstanding</div>
                <div class="font-mono" style="font-size: 1.5rem; font-weight: 700; color: {{ $report['current_balance'] > 0 ? 'var(--error)' : 'var(--success)' }};">{{ number_format($report['current_balance'], 3) }}g</div>
            </div>
        </div>

        <div class="card">
            <h3 style="margin-bottom: 20px; font-family: 'Playfair Display', serif;">Activity Details</h3>
            <div style="overflow-x: auto;">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Invoice No</th>
                            <th>Total Amount</th>
                            <th>Remaining Balance</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($report['invoices'] as $invoice)
                            <tr>
                                <td style="color: var(--text-secondary);">{{ $invoice->invoice_date->format('d/m/Y') }}</td>
                                <td class="font-mono" style="color: var(--gold-primary);">{{ $invoice->formatted_invoice_no }}</td>
                                <td class="font-mono">{{ number_format($invoice->grand_total, 3) }}g</td>
                                <td class="font-mono" style="color: {{ $invoice->remaining_balance > 0 ? 'var(--error)' : 'var(--success)' }};">
                                    {{ number_format($invoice->remaining_balance, 3) }}g
                                </td>
                                <td style="text-align: right;">
                                    <a href="{{ route('invoices.show', $invoice) }}" class="btn-outline" style="padding: 4px 12px; font-size: 0.75rem;">
                                        <i class="bi bi-eye"></i> View
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @else
        <div class="card" style="text-align: center; padding: 60px; color: var(--text-muted);">
            <i class="bi bi-person-lines-fill" style="font-size: 4rem; opacity: 0.2; display: block; margin-bottom: 20px;"></i>
            <h3>Select a customer to generate a report.</h3>
            <p>Use the filters above to see detailed history for a specific customer and time period.</p>
        </div>
    @endif
@endsection
