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

    .filter-tabs {
        display: flex;
        gap: 8px;
        margin-bottom: 16px;
        border-bottom: 1px solid var(--border-color);
        padding-bottom: 12px;
    }

    .filter-tab {
        padding: 8px 16px;
        border-radius: 8px;
        font-size: 0.85rem;
        cursor: pointer;
        border: 1px solid transparent;
        transition: all 0.2s;
        background: var(--bg-surface);
        color: var(--text-secondary);
    }

    .filter-tab.active {
        background: var(--gold-primary);
        color: white;
        border-color: var(--gold-deep);
        font-weight: 600;
    }

    .filter-section {
        display: none;
    }

    .filter-section.active {
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

    .range-badge {
        background: var(--gold-primary);
        color: white;
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 600;
        margin-left: 8px;
    }

    @media print {
        .sidebar, .header, .filter-bar, .btn-gold, .button-outline, nav, .page-header button, .filter-tabs {
            display: none !important;
        }
        .main-content { margin-left: 0 !important; padding: 0 !important; }
        body { background: white !important; color: black !important; }
        .card, .table { border: 1px solid #333 !important; box-shadow: none !important; }
        table { page-break-inside: auto; }
        tr { page-break-inside: avoid; page-break-after: auto; }
        thead { display: table-header-group; }
        .print-header {
            text-align: center; margin-bottom: 30px;
            border-bottom: 3px double #000; padding-bottom: 15px;
        }
    }
</style>
@endsection

@section('content')
    <div class="print-header" style="display:none; text-align:center; margin-bottom:30px;" id="print-header">
        <h1 style="margin:0;">Report - {{ $dateLabel }}</h1>
        <p style="margin:8px 0 0 0; color:#555;">{{ config('app.name') }} | Gold Workshop</p>
    </div>

    <div class="page-header">
        <div>
            <h1>Daily Report <span class="font-urdu">روزانہ رپورٹ</span></h1>
            <p style="color:var(--text-muted);">
                Period: <strong>{{ $dateLabel }}</strong>
                @if($hasRange ?? false)
                    <span class="range-badge">📅 Range</span>
                @endif
            </p>
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

    <!-- ✅ Filter Bar with Tabs: Single Date OR Date Range -->
    <div class="filter-bar">
        <!-- Tab Navigation -->
        <div style="width:100%;">
            <div class="filter-tabs">
                <button type="button" class="filter-tab active" onclick="switchFilterMode('single')" id="tab-single">
                    📅 Single Date
                </button>
                <button type="button" class="filter-tab" onclick="switchFilterMode('range')" id="tab-range">
                    📆 Date Range
                </button>
            </div>
        </div>

        <!-- Single Date Form -->
        <form method="GET" action="{{ route('reports.daily') }}" class="filter-section active" id="form-single">
            <input type="hidden" name="mode" value="single">
            <div style="display:flex; gap:16px; flex-wrap:wrap; align-items:end;">
                <div>
                    <label for="date">Select Date</label>
                    <input 
                        type="date" 
                        name="date" 
                        id="date"
                        value="{{ $filters['date'] ?? ($hasRange ?? false ? '' : $dateForView->toDateString()) }}" 
                        class="filter-control" 
                        style="width:180px;"
                        max="{{ now()->toDateString() }}"
                    >
                </div>
                <button type="submit" class="btn-gold">Show Report</button>
                @if(($filters['date'] ?? null) || ($hasRange ?? false))
                    <a href="{{ route('reports.daily') }}" class="button-outline">Reset</a>
                @endif
            </div>
        </form>

        <!-- Date Range Form -->
        <form method="GET" action="{{ route('reports.daily') }}" class="filter-section" id="form-range">
            <input type="hidden" name="mode" value="range">
            <div style="display:flex; gap:16px; flex-wrap:wrap; align-items:end;">
                <div>
                    <label for="from_date">From</label>
                    <input 
                        type="date" 
                        name="from_date" 
                        id="from_date"
                        value="{{ $filters['from_date'] ?? '' }}" 
                        class="filter-control" 
                        style="width:160px;"
                        max="{{ now()->toDateString() }}"
                    >
                </div>
                <div>
                    <label for="to_date">To</label>
                    <input 
                        type="date" 
                        name="to_date" 
                        id="to_date"
                        value="{{ $filters['to_date'] ?? '' }}" 
                        class="filter-control" 
                        style="width:160px;"
                        max="{{ now()->toDateString() }}"
                    >
                </div>
                <button type="submit" class="btn-gold">Show Range</button>
                @if($hasRange ?? false)
                    <a href="{{ route('reports.daily') }}" class="button-outline">Reset</a>
                @endif
            </div>
        </form>
    </div>

    <!-- Stats Grid -->
    <div class="stat-grid">
        <div class="stat-box">
            <div style="color:var(--text-secondary);font-size:0.8rem;">Total Invoices</div>
            <div style="font-size:2rem;font-weight:700;margin:8px 0;">{{ $report['total_invoices'] ?? 0 }}</div>
        </div>
        <div class="stat-box">
            <div style="color:var(--text-secondary);font-size:0.8rem;">Gold Given</div>
            <div style="font-size:2rem;font-weight:700;color:var(--gold-primary);margin:8px 0;">
                {{ number_format($report['total_gold_khalis'] ?? 0, 3) }}g
            </div>
        </div>
        <div class="stat-box">
            <div style="color:var(--text-secondary);font-size:0.8rem;">Total Invoiced</div>
            <div style="font-size:2rem;font-weight:700;margin:8px 0;">
                {{ number_format($report['total_grand_total'] ?? 0, 3) }}g
            </div>
        </div>
        <div class="stat-box">
            <div style="color:var(--text-secondary);font-size:0.8rem;">Total Wasooli</div>
            <div style="font-size:2rem;font-weight:700;color:var(--success);margin:8px 0;">
                {{ number_format($report['total_wasooli'] ?? 0, 3) }}g
            </div>
        </div>
        <div class="stat-box">
            <div style="color:var(--text-secondary);font-size:0.8rem;">Gold Receipts</div>
            <div style="font-size:2rem;font-weight:700;color:var(--info);margin:8px 0;">
                {{ number_format($report['total_receipt_khalis'] ?? 0, 3) }}g
            </div>
        </div>
        <div class="stat-box">
            <div style="color:var(--text-secondary);font-size:0.8rem;">Net Movement</div>
            <div style="font-size:2rem;font-weight:700;margin:8px 0; color:{{ ($report['net_movement'] ?? 0) >= 0 ? 'var(--error)' : 'var(--success)' }};">
                {{ number_format($report['net_movement'] ?? 0, 3) }}g
            </div>
        </div>
    </div>

    <!-- Empty State -->
    @if(empty($report['invoices']) || $report['invoices']->isEmpty())
        <div style="text-align:center;padding:60px;background:var(--bg-card);border-radius:16px;">
            <i class="bi bi-calendar-x" style="font-size:4rem;opacity:0.2;display:block;margin-bottom:16px;"></i>
            <h3>No transactions for {{ $dateLabel }}</h3>
            <p style="color:var(--text-muted);">Try selecting a different date or range.</p>
        </div>
    @else
        <!-- Invoices Table -->
        <div class="table-container" style="background:var(--bg-card);border-radius:16px;overflow:hidden;margin-bottom:24px;">
            <div style="padding:16px 20px;border-bottom:1px solid var(--border-color);display:flex;justify-content:space-between;align-items:center;">
                <h4 style="margin:0;color:var(--gold-primary);">📄 Invoices ({{ $report['total_invoices'] }})</h4>
                @if($hasRange ?? false)
                    <small style="color:var(--text-muted);">Sorted by date</small>
                @endif
            </div>
            <table style="width:100%">
                <thead>
                    <tr>
                        @if($hasRange ?? false)
                            <th>Date</th>
                        @endif
                        <th>Invoice No</th>
                        <th>Party</th>
                        <th style="text-align:right">Gold Khalis</th>
                        <th style="text-align:right">Effective Gold</th>
                        <th style="text-align:right">Received</th>
                        <th style="text-align:right">Wasooli</th>
                        <th style="text-align:right">Balance</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($report['invoices'] as $invoice)
                    <tr>
                        @if($hasRange ?? false)
                            <td style="font-size:0.85rem;color:var(--text-muted);">
                                {{ $invoice->invoice_date?->format('d/m') }}
                            </td>
                        @endif
                        <td class="font-mono">{{ $invoice->invoice_no ?? 'INV-'.$invoice->id }}</td>
                        <td>
                            {{ $invoice->customer?->name ?? '-' }} 
                            <small style="color:var(--text-muted);">({{ ucfirst($invoice->invoice_type ?? 'customer') }})</small>
                        </td>
                        <td style="text-align:right;font-family:monospace;">
                            {{ number_format($invoice->gold_khalis ?? 0, 3) }}g
                        </td>
                        <td style="text-align:right;font-family:monospace;color:var(--gold-primary);">
                            {{ number_format($invoice->effective_gold ?? 0, 3) }}g
                        </td>
                        <td style="text-align:right;font-family:monospace;color:var(--info);">
                            +{{ number_format($invoice->total_received_khalis ?? 0, 3) }}g
                        </td>
                        <td style="text-align:right;font-family:monospace;color:var(--success);">
                            -{{ number_format($invoice->wasooli ?? 0, 3) }}g
                        </td>
                        <td style="text-align:right;font-family:monospace;font-weight:600;color:{{ ($invoice->remaining_balance ?? 0) > 0 ? 'var(--error)' : 'var(--success)' }};">
                            {{ number_format($invoice->remaining_balance ?? 0, 3) }}g
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- Receipts Table (if any) -->
        @if(!empty($report['receipts']) && $report['receipts']->count() > 0)
        <div class="table-container" style="background:var(--bg-card);border-radius:16px;overflow:hidden;">
            <div style="padding:16px 20px;border-bottom:1px solid var(--border-color);">
                <h4 style="margin:0;color:var(--success);">📥 Gold Receipts ({{ $report['total_receipts'] }})</h4>
            </div>
            <table style="width:100%">
                <thead>
                    <tr>
                        @if($hasRange ?? false)
                            <th>Date</th>
                        @endif
                        <th>Receipt No</th>
                        <th>Party</th>
                        <th style="text-align:right">Khalis Weight</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($report['receipts'] as $receipt)
                    <tr>
                        @if($hasRange ?? false)
                            <td style="font-size:0.85rem;color:var(--text-muted);">
                                {{ $receipt->receipt_date?->format('d/m') }}
                            </td>
                        @endif
                        <td class="font-mono">{{ $receipt->receipt_no ?? 'RCV-'.$receipt->id }}</td>
                        <td>{{ $receipt->customer?->name ?? '-' }}</td>
                        <td style="text-align:right;font-family:monospace;color:var(--success);">
                            +{{ number_format($receipt->total_khalis_weight ?? 0, 3) }}g
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    @endif

    <!-- Debug Info (Local Only) -->
    @if(app()->environment('local'))
    <details style="margin-top:24px;padding:16px;background:var(--bg-surface);border-radius:12px;">
        <summary style="cursor:pointer;color:var(--text-muted);">🔍 Debug Info</summary>
        <pre style="margin-top:12px;font-size:0.8rem;overflow:auto;">
Query Range: {{ $report['debug']['from'] ?? 'N/A' }} {{ $report['debug']['to'] ? 'to '.$report['debug']['to'] : '' }}
Is Range: {{ $report['debug']['is_range'] ? 'Yes' : 'No' }}
Invoices Found: {{ $report['debug']['invoice_count'] ?? 0 }}
Receipts Found: {{ $report['debug']['receipt_count'] ?? 0 }}
        </pre>
    </details>
    @endif
@endsection

@section('extra_js')
<script>
    // ✅ Switch between Single Date and Date Range filter modes
    function switchFilterMode(mode) {
        // Update tab styles
        document.getElementById('tab-single').classList.toggle('active', mode === 'single');
        document.getElementById('tab-range').classList.toggle('active', mode === 'range');
        
        // Show/hide forms
        document.getElementById('form-single').classList.toggle('active', mode === 'single');
        document.getElementById('form-range').classList.toggle('active', mode === 'range');
        
        // Clear unused form inputs to avoid conflicts
        if (mode === 'single') {
            document.getElementById('from_date').value = '';
            document.getElementById('to_date').value = '';
        } else {
            document.getElementById('date').value = '';
        }
    }

    // ✅ Quick filter buttons - use single date mode
    function quickFilter(type) {
        const today = new Date();
        let dateValue = '';
        
        if (type === 'today') {
            dateValue = today.toISOString().split('T')[0];
        } else if (type === 'month') {
            dateValue = new Date(today.getFullYear(), today.getMonth(), 1).toISOString().split('T')[0];
        } else if (type === 'quarter') {
            const quarterStart = new Date(today.getFullYear(), Math.floor(today.getMonth()/3)*3, 1);
            dateValue = quarterStart.toISOString().split('T')[0];
        } else if (type === 'year') {
            dateValue = new Date(today.getFullYear(), 0, 1).toISOString().split('T')[0];
        }
        
        // Navigate to report in single-date mode
        const baseUrl = "{{ route('reports.daily') }}";
        window.location.href = baseUrl + '?date=' + dateValue;
    }

    // ✅ Auto-activate correct tab on page load based on URL params
    document.addEventListener('DOMContentLoaded', function() {
        const urlParams = new URLSearchParams(window.location.search);
        const hasFromDate = urlParams.has('from_date');
        const hasToDate = urlParams.has('to_date');
        
        if (hasFromDate && hasToDate) {
            switchFilterMode('range');
        } else {
            switchFilterMode('single');
        }
    });

    // Print handlers
    window.addEventListener('beforeprint', () => {
        document.getElementById('print-header').style.display = 'block';
    });
    window.addEventListener('afterprint', () => {
        document.getElementById('print-header').style.display = 'none';
    });
</script>
@endsection