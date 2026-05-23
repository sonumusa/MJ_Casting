@extends('layouts.app')

@section('title', 'Ledger')

@section('extra_css')
<style>
    .ledger-header {
        background-color: var(--bg-card);
        border: 1px solid var(--border-color);
        border-radius: 16px;
        padding: 24px;
        margin-bottom: 24px;
    }

    .summary-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        gap: 16px;
        margin-top: 24px;
    }
    .summary-box {
        background-color: var(--bg-surface);
        border: 1px solid var(--border-color);
        border-radius: 12px;
        padding: 18px;
        text-align: center;
    }
    .summary-label { font-size: 0.75rem; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 6px; }
    .summary-value { font-family: 'JetBrains Mono', monospace; font-size: 1.25rem; font-weight: 700; color: var(--text-primary); }

    .box-outstanding { border-left: 4px solid var(--error); }
    .box-cleared { border-left: 4px solid var(--success); }

    .filter-row {
        background-color: var(--bg-card);
        border: 1px solid var(--border-color);
        border-radius: 16px;
        padding: 20px;
        margin-bottom: 24px;
        display: flex;
        gap: 16px;
        align-items: end;
        flex-wrap: wrap;
    }

    .table-container {
        background: var(--bg-card);
        border: 1px solid var(--border-color);
        border-radius: 16px;
        overflow: hidden;
    }

    table { width: 100%; border-collapse: collapse; }
    th { 
        background: rgba(218,165,32,0.08); 
        padding: 16px 12px; 
        text-align: left; 
        font-size: 0.75rem; 
        text-transform: uppercase; 
        letter-spacing: 0.6px;
        border-bottom: 2px solid var(--border-color);
    }
    td { padding: 16px 12px; border-bottom: 1px solid var(--border-color); vertical-align: middle; }
    tr:hover { background-color: rgba(218,165,32,0.04); }
    .totals-row td {
        background: rgba(218,165,32,0.1) !important;
        font-weight: 700;
        border-top: 3px solid var(--gold-primary);
    }
    .receipt-row {
        background-color: rgba(16,185,129,0.08) !important;
    }
</style>
@endsection

@section('content')
    <div class="page-header">
        <div class="page-title-group">
            <h1>Party Ledger <span class="font-urdu">کھاتہ</span></h1>
        </div>
    </div>

    <div class="ledger-header">
        <form method="GET" action="{{ route('ledger.index') }}">
            <div style="display: flex; gap: 12px; align-items: end;">
                <div style="flex: 1;">
                    <label>Select Party <span class="font-urdu">پارٹی منتخب کریں</span></label>
                    <select name="customer_id" class="filter-control" onchange="this.form.submit()">
                        <option value="">Select Party...</option>
                        @foreach($customers as $c)
                            <option value="{{ $c->id }}" {{ request('customer_id') == $c->id ? 'selected' : '' }}>
                                {{ $c->name }} ({{ ucfirst($c->party_type) }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="btn-gold">View Ledger</button>
            </div>
        </form>

        @if($selectedCustomer && $ledgerData)
            <div style="margin-top: 24px; padding: 20px; background: var(--bg-surface); border-radius: 12px;">
                <h2 style="margin:0 0 8px 0; color:var(--gold-primary);">{{ $selectedCustomer->name }}</h2>
                <p style="margin:0; color:var(--text-secondary);">Party Type: <strong>{{ ucfirst($selectedCustomer->party_type) }}</strong></p>
                
                <div class="summary-grid" style="margin-top: 20px;">
                    <div class="summary-box">
                        <div class="summary-label">Opening Balance</div>
                        <div class="summary-value">{{ number_format($ledgerData['opening_balance'] ?? 0, 3) }}g</div>
                    </div>
                    <div class="summary-box">
                        <div class="summary-label">Gold Given</div>
                        <div class="summary-value">{{ number_format($ledgerData['total_gold_khalis'] ?? 0, 3) }}g</div>
                    </div>
                    <div class="summary-box">
                        <div class="summary-label">Total Invoiced</div>
                        <div class="summary-value">{{ number_format($ledgerData['total_invoiced'] ?? 0, 3) }}g</div>
                    </div>
                    <div class="summary-box {{ ($ledgerData['current_balance'] ?? 0) > 0 ? 'box-outstanding' : 'box-cleared' }}">
                        <div class="summary-label">Current Balance</div>
                        <div class="summary-value" style="color: {{ ($ledgerData['current_balance'] ?? 0) > 0 ? 'var(--error)' : 'var(--success)' }};">
                            {{ number_format($ledgerData['current_balance'] ?? 0, 3) }}g
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>

    @if($selectedCustomer && $ledgerData)
    @if($selectedCustomer && $ledgerData)
        <div class="filter-row">
            <form method="GET" action="{{ route('ledger.index') }}">
                <input type="hidden" name="customer_id" value="{{ $selectedCustomer->id }}">
                <div style="display:flex; gap:16px; flex-wrap:wrap;">
                    <div>
                        <label>From</label>
                        <input type="date" name="from_date" value="{{ request('from_date') }}" class="filter-control">
                    </div>
                    <div>
                        <label>To</label>
                        <input type="date" name="to_date" value="{{ request('to_date') }}" class="filter-control">
                    </div>
                    <div style="display:flex; gap:8px; align-items:flex-end;">
                        <button type="submit" class="btn-gold">Filter</button>
                        <a href="{{ route('ledger.index', ['customer_id' => $selectedCustomer->id]) }}" class="button-outline">Reset</a>
                    </div>
                </div>
            </form>
        </div>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Type</th>
                        <th>Description</th>
                        <th style="text-align:right">Gold In (Received)</th>
                        <th style="text-align:right">Gold Out (Given)</th>
                        <th style="text-align:right">Wasooli</th>
                        <th style="text-align:right">Running Balance</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Opening -->
                    <tr style="background:rgba(234,179,8,0.1);">
                        <td colspan="6"><strong>Opening Balance</strong></td>
                        <td class="font-mono" style="text-align:right; font-weight:700;">{{ number_format($ledgerData['opening_balance'] ?? 0, 3) }}g</td>
                    </tr>

                    <!-- Integrated Transactions (Chronological) -->
                    @if(!empty($ledgerData['transactions']))
                        @foreach($ledgerData['transactions'] as $txn)
                            @if($txn['type'] === 'invoice')
                                @php
                                    $invoice = $txn['object'];
                                @endphp
                                <tr>
                                    <td>{{ $txn['date']->format('d/m/Y') }}</td>
                                    <td><span style="background:#eab308;color:black;padding:2px 8px;border-radius:999px;font-size:0.7rem;">INVOICE</span></td>
                                    <td>Invoice #{{ $invoice->invoice_no }}</td>
                                    <td style="text-align:right">
                                        @if($invoice->total_received_khalis > 0)
                                            -{{ number_format($invoice->total_received_khalis, 3) }}g
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td class="negative" style="text-align:right">+{{ number_format($invoice->effective_gold ?? 0, 3) }}g</td>
                                    <td class="font-mono" style="text-align:right">
                                        @if($invoice->wasooli > 0)
                                            -{{ number_format($invoice->wasooli ?? 0, 3) }}g
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td class="font-mono" style="text-align:right; font-weight:600;color:{{ $txn['running_balance_after'] > 0 ? 'var(--error)' : 'var(--success)' }};">
                                        {{ number_format($txn['running_balance_after'], 3) }}g
                                    </td>
                                </tr>
                            @elseif($txn['type'] === 'receipt')
                                @php
                                    $receipt = $txn['object'];
                                @endphp
                                <tr class="receipt-row">
                                    <td>{{ $txn['date']->format('d/m/Y') }}</td>
                                    <td><span style="background:#4ade80;color:black;padding:2px 8px;border-radius:999px;font-size:0.7rem;">RECEIPT</span></td>
                                    <td>Receipt #{{ $txn['receipt_no'] }}</td>
                                    <td class="positive" style="text-align:right">-{{ number_format($receipt->total_khalis_weight, 3) }}g</td>
                                    <td style="text-align:right">-</td>
                                    <td style="text-align:right">-</td>
                                    <td class="font-mono" style="text-align:right; font-weight:600;">{{ number_format($txn['running_balance_after'], 3) }}g</td>
                                </tr>
                            @endif
                        @endforeach
                    @else
                        <tr>
                            <td colspan="7" style="text-align:center; padding:40px; color:var(--text-secondary);">
                                No transactions in selected period
                            </td>
                        </tr>
                    @endif

                    <tr class="totals-row">
                        <td colspan="6"><strong>FINAL BALANCE</strong></td>
                        <td class="font-mono" style="text-align:right; font-size:1.1rem; font-weight:700; color:{{ ($ledgerData['current_balance'] ?? 0) > 0 ? 'var(--error)' : 'var(--success)' }};">
                            {{ number_format($ledgerData['current_balance'] ?? 0, 3) }}g
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    @else
        <div style="text-align:center; padding:80px 20px; background:var(--bg-card); border-radius:16px; border:1px solid var(--border-color);">
            <i class="bi bi-journal-text" style="font-size:4rem; opacity:0.2; display:block; margin-bottom:20px;"></i>
            <h3>Select a party to view ledger</h3>
            <p class="text-muted">Gold Receipts and Invoices will appear here with running balance.</p>
        </div>
    @endif
@endsection
