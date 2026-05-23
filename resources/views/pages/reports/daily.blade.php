@extends('layouts.app')

@section('title', 'Daily Report')

@section('extra_css')
<style>
    .page-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 24px; }

    .filter-bar {
        background: var(--bg-card);
        border: 1px solid var(--border-color);
        border-radius: 16px;
        padding: 20px;
        margin-bottom: 24px;
        display: flex;
        gap: 16px;
        flex-wrap: wrap;
        align-items: end;
    }

    .stat-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
        margin-bottom: 32px;
    }

    .stat-box {
        background: var(--bg-surface);
        border: 1px solid var(--border-color);
        border-radius: 12px;
        padding: 20px;
        text-align: center;
    }

    @media print {
        .sidebar, .header, .filter-bar, .btn-gold, .button-outline, nav, .page-header button {
            display: none !important;
        }
        .main-content {
            margin-left: 0 !important;
            padding: 0 !important;
        }
        body {
            background: white !important;
            color: black !important;
        }
        .card, .table {
            border: 1px solid #333 !important;
            box-shadow: none !important;
        }
        table { 
            page-break-inside: auto;
        }
        tr { 
            page-break-inside: avoid; 
            page-break-after: auto; 
        }
        thead { 
            display: table-header-group; 
        }
        .print-header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 3px double #000;
            padding-bottom: 15px;
        }
    }
</style>
@endsection

@section('content')
    <div class="print-header" style="display:none; text-align:center; margin-bottom:30px;" id="print-header">
        <h1 style="margin:0;">Daily Report - {{ $date->format('d-m-Y') }}</h1>
        <p style="margin:8px 0 0 0; color:#555;">{{ config('app.name') }} | Gold Workshop</p>
    </div>

    <div class="page-header">
        <div>
            <h1>Daily Report <span class="font-urdu">روزانہ رپورٹ</span></h1>
            <p style="color:var(--text-muted);">Date: <strong>{{ $date->format('l, d F Y') }}</strong></p>
        </div>
        <div style="display:flex; gap:12px;">
            <button onclick="quickFilter('today')" class="btn-gold">Today</button>
            <button onclick="quickFilter('month')" class="button-outline">This Month</button>
            <button onclick="quickFilter('quarter')" class="button-outline">This Quarter</button>
            <button onclick="quickFilter('year')" class="button-outline">This Year</button>
            <button onclick="window.print()" class="button-outline">
                <i class="bi bi-printer"></i> Print
            </button>
        </div>
    </div>

    <div class="filter-bar">
        <form method="GET" style="display:flex; gap:16px; flex-wrap:wrap; align-items:end;">
            <div>
                <label>Select Date</label>
                <input type="date" name="date" value="{{ $date->toDateString() }}" class="filter-control" style="width:180px;">
            </div>
            <button type="submit" class="btn-gold">Show Report</button>
        </form>
    </div>

    <div class="stat-grid">
        <div class="stat-box">
            <div style="color:var(--text-secondary);font-size:0.8rem;">Total Invoices</div>
            <div style="font-size:2rem;font-weight:700;margin:8px 0;">{{ $report['total_invoices'] ?? 0 }}</div>
        </div>
        <div class="stat-box">
            <div style="color:var(--text-secondary);font-size:0.8rem;">Gold Given</div>
            <div style="font-size:2rem;font-weight:700;color:var(--gold-primary);margin:8px 0;">{{ number_format($report['total_gold_khalis'] ?? 0, 3) }}g</div>
        </div>
        <div class="stat-box">
            <div style="color:var(--text-secondary);font-size:0.8rem;">Total Invoiced</div>
            <div style="font-size:2rem;font-weight:700;margin:8px 0;">{{ number_format($report['total_grand_total'] ?? 0, 3) }}g</div>
        </div>
        <div class="stat-box">
            <div style="color:var(--text-secondary);font-size:0.8rem;">Total Wasooli</div>
            <div style="font-size:2rem;font-weight:700;color:var(--success);margin:8px 0;">{{ number_format($report['total_wasooli'] ?? 0, 3) }}g</div>
        </div>
    </div>

    @if(empty($report['invoices']) || $report['invoices']->isEmpty())
        <div style="text-align:center;padding:60px;background:var(--bg-card);border-radius:16px;">
            <i class="bi bi-calendar-x" style="font-size:4rem;opacity:0.2;display:block;margin-bottom:16px;"></i>
            <h3>No transactions on this date</h3>
        </div>
    @else
        <div class="table-container" style="background:var(--bg-card);border-radius:16px;overflow:hidden;">
            <table style="width:100%">
                <thead>
                    <tr>
                        <th>Invoice No</th>
                        <th>Party</th>
                        <th style="text-align:right">Gold Khalis</th>
                        <th style="text-align:right">Effective Gold</th>
                        <th style="text-align:right">Wasooli</th>
                        <th style="text-align:right">Balance</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($report['invoices'] as $invoice)
                    <tr>
                        <td class="font-mono">{{ $invoice->formatted_invoice_no ?? 'INV-'.$invoice->id }}</td>
                        <td>{{ $invoice->customer?->name ?? '-' }} <small>({{ ucfirst($invoice->invoice_type ?? 'customer') }})</small></td>
                        <td style="text-align:right;font-family:monospace;">{{ number_format($invoice->gold_khalis ?? 0, 3) }}g</td>
                        <td style="text-align:right;font-family:monospace;color:var(--gold-primary);">{{ number_format($invoice->effective_gold ?? 0, 3) }}g</td>
                        <td style="text-align:right;font-family:monospace;color:var(--success);">{{ number_format($invoice->wasooli ?? 0, 3) }}g</td>
                        <td style="text-align:right;font-family:monospace;font-weight:600;color:{{ ($invoice->remaining_balance ?? 0) > 0 ? 'var(--error)' : 'var(--success)' }};">
                            {{ number_format($invoice->remaining_balance ?? 0, 3) }}g
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
@endsection

@section('extra_js')
<script>
    function quickFilter(type) {
        const today = new Date();
        let fromDate = '';
        
        if (type === 'today') {
            fromDate = today.toISOString().split('T')[0];
        } else if (type === 'month') {
            fromDate = new Date(today.getFullYear(), today.getMonth(), 1).toISOString().split('T')[0];
        } else if (type === 'quarter') {
            const quarterStart = new Date(today.getFullYear(), Math.floor(today.getMonth()/3)*3, 1);
            fromDate = quarterStart.toISOString().split('T')[0];
        } else if (type === 'year') {
            fromDate = new Date(today.getFullYear(), 0, 1).toISOString().split('T')[0];
        }
        
        const url = new URL(window.location.href);
        url.searchParams.set('date', fromDate);
        window.location.href = url.toString();
    }

    // Auto show print header when printing
    window.addEventListener('beforeprint', () => {
        document.getElementById('print-header').style.display = 'block';
    });
</script>
@endsection
