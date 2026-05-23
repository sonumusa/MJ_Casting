@extends('layouts.app')

@section('title', 'Daily Report')

@section('content')
    <div class="page-header" style="margin-bottom: 24px;">
        <div class="page-title-group">
            <h1>Daily Report <span class="font-urdu">روزانہ کی رپورٹ</span></h1>
            <p style="color: var(--text-muted);">Transactions for {{ $date->format('l, jS M Y') }}</p>
        </div>
    </div>

    <div class="card" style="margin-bottom: 24px;">
        <form method="GET" style="display:flex; gap:16px; align-items: flex-end;">
            <div class="form-group" style="margin-bottom: 0; flex: 1; max-width: 300px;">
                <label>Select Date <span class="font-urdu">تاریخ منتخب کریں</span></label>
                <input type="date" name="date" value="{{ $date->toDateString() }}" required>
            </div>
            <button type="submit" class="btn-gold">
                <i class="bi bi-arrow-clockwise"></i> Refresh Report
            </button>
            <button type="button" class="btn-outline" onclick="window.print()">
                <i class="bi bi-printer"></i> Print
            </button>
        </form>
    </div>

    <!-- Stats Grid -->
    <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 24px;">
        <div class="card" style="padding: 20px; text-align: center; margin-bottom: 0;">
            <div style="font-size: 0.7rem; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 8px;">Total Invoices</div>
            <div class="font-mono" style="font-size: 1.5rem; font-weight: 700; color: var(--text-primary);">{{ $report['total_invoices'] }}</div>
        </div>
        <div class="card" style="padding: 20px; text-align: center; margin-bottom: 0;">
            <div style="font-size: 0.7rem; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 8px;">Gold Given</div>
            <div class="font-mono" style="font-size: 1.5rem; font-weight: 700; color: var(--gold-primary);">{{ number_format($report['total_gold_khalis'], 3) }}g</div>
        </div>
        <div class="card" style="padding: 20px; text-align: center; margin-bottom: 0;">
            <div style="font-size: 0.7rem; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 8px;">Total Invoiced</div>
            <div class="font-mono" style="font-size: 1.5rem; font-weight: 700; color: var(--text-primary);">{{ number_format($report['total_grand_total'], 3) }}g</div>
        </div>
        <div class="card" style="padding: 20px; text-align: center; margin-bottom: 0;">
            <div style="font-size: 0.7rem; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 8px;">Total Wasooli</div>
            <div class="font-mono" style="font-size: 1.5rem; font-weight: 700; color: var(--success);">{{ number_format($report['total_wasooli'], 3) }}g</div>
        </div>
    </div>

    <div class="card">
        <h3 style="margin-bottom: 20px; font-family: 'Playfair Display', serif;">Invoices List</h3>
        @if($report['invoices']->isEmpty())
            <div style="padding: 40px; text-align: center; color: var(--text-muted);">
                <i class="bi bi-file-earmark-x" style="font-size: 3rem; opacity: 0.2; display: block; margin-bottom: 16px;"></i>
                No invoices found for this date.
            </div>
        @else
            <div style="overflow-x: auto;">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Invoice No</th>
                            <th>Customer</th>
                            <th>Gold Khalis</th>
                            <th>Grand Total</th>
                            <th>Wasooli</th>
                            <th>Balance</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($report['invoices'] as $invoice)
                            <tr>
                                <td class="font-mono" style="color: var(--gold-primary);">{{ $invoice->formatted_invoice_no }}</td>
                                <td style="color: var(--text-primary);">{{ $invoice->customer->name ?? '-' }}</td>
                                <td class="font-mono">{{ number_format($invoice->gold_khalis, 3) }}g</td>
                                <td class="font-mono">{{ number_format($invoice->grand_total, 3) }}g</td>
                                <td class="font-mono">{{ number_format($invoice->wasooli, 3) }}g</td>
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
        @endif
    </div>
@endsection
