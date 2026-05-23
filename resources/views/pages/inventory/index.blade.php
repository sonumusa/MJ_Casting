@extends('layouts.app')

@section('title', 'Inventory / Stock Report')

@section('extra_css')
<style>
    .page-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 28px; }
    .page-title-group h1 { font-size: 1.9rem; color: var(--text-primary); margin: 0; font-weight: 700; }
    .page-title-group p { font-size: 1.05rem; color: var(--gold-muted); margin: 0; }

    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
        gap: 22px;
        margin-bottom: 32px;
    }
    .stat-card {
        background: var(--bg-card);
        border: 1px solid var(--border-color);
        border-radius: 16px;
        padding: 24px;
        box-shadow: var(--shadow-1);
        transition: all 0.2s ease;
        position: relative;
        overflow: hidden;
    }
    .stat-card:hover { transform: translateY(-4px); box-shadow: var(--shadow-3); }
    .stat-card::before {
        content: '';
        position: absolute;
        top: 0; left: 0; right: 0;
        height: 4px;
        background: linear-gradient(90deg, var(--gold-deep), var(--gold-primary));
    }
    .stat-icon {
        width: 52px; height: 52px; border-radius: 14px;
        display: flex; align-items: center; justify-content: center;
        font-size: 1.5rem; margin-bottom: 16px;
        background: rgba(218,165,32,0.1); color: var(--gold-primary);
        border: 2px solid rgba(218,165,32,0.2);
    }
    .stat-icon.green { background: rgba(16,185,129,0.1); color: var(--success); border-color: rgba(16,185,129,0.3); }
    .stat-icon.red { background: rgba(244,63,94,0.1); color: var(--error); border-color: rgba(244,63,94,0.3); }
    .stat-icon.blue { background: rgba(56,189,248,0.1); color: var(--info); border-color: rgba(56,189,248,0.3); }

    .stat-label { font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.1em; margin-bottom: 8px; font-weight: 700; }
    .stat-value { font-family: 'JetBrains Mono', monospace; font-size: 1.85rem; font-weight: 700; }
    .stat-sub { font-size: 0.82rem; color: var(--text-secondary); margin-top: 8px; line-height: 1.4; }

    .form-section, .report-section {
        background-color: var(--bg-card);
        border: 1px solid var(--border-color);
        border-radius: 16px;
        padding: 28px;
        margin-bottom: 28px;
        box-shadow: var(--shadow-1);
    }
    .section-header {
        display: flex; align-items: center; gap: 14px;
        margin-bottom: 24px; padding-bottom: 14px;
        border-bottom: 1px solid var(--border-color);
        position: relative;
    }
    .section-header::after {
        content: ''; position: absolute; bottom: -1px; left: 0;
        width: 80px; height: 3px;
        background: var(--gold-primary); border-radius: 3px;
    }
    .section-header i { color: var(--gold-primary); font-size: 1.4rem; }
    .section-header h3 { font-family: 'Playfair Display', serif; font-size: 1.2rem; color: var(--text-primary); margin: 0; }

    .movement-table td, .movement-table th {
        padding: 14px 12px;
        font-size: 0.9rem;
    }
    .positive { color: var(--success); font-weight: 600; }
    .negative { color: var(--error); font-weight: 600; }

    .filter-bar {
        background: var(--bg-surface);
        padding: 16px 20px;
        border-radius: 12px;
        margin-bottom: 20px;
        display: flex;
        gap: 16px;
        align-items: end;
        flex-wrap: wrap;
    }

    .formula-box {
        background: rgba(218,165,32,0.08);
        border: 1px dashed var(--gold-primary);
        border-radius: 12px;
        padding: 16px;
        font-size: 0.85rem;
        line-height: 1.6;
        color: var(--gold-bright);
    }

    .opening-stock {
        background: linear-gradient(135deg, #1e3a2f, #14532d);
        border: 2px solid #4ade80;
        color: white;
        border-radius: 16px;
        padding: 20px;
        text-align: center;
        margin-bottom: 24px;
    }
</style>
@endsection

@section('content')
<div class="page-header">
    <div class="page-title-group">
        <h1>Inventory / Stock Report</h1>
        <p class="font-urdu">مال / سونے کا اسٹاک رپورٹ</p>
    </div>
</div>

<div class="opening-stock">
    <div style="font-size:0.9rem; opacity:0.9; margin-bottom:6px;">OPENING STOCK (ابتدائی بیلنس)</div>
    <div style="font-size:2.4rem; font-weight:700; font-family:monospace;">{{ number_format($inventory->opening_balance ?? 0, 3) }} <span style="font-size:1rem;">grams</span></div>
    <div style="font-size:0.85rem; margin-top:8px; opacity:0.85;">{{ $inventory->period_label ?? 'Current Period' }}</div>
</div>

<!-- Filters -->
<div class="filter-bar">
    <form method="GET" class="d-flex gap-3 flex-wrap align-items-end">
        <div>
            <label class="stat-label">From Date</label>
            <input type="date" name="from_date" value="{{ $fromDate }}" class="filter-control" style="width: 160px;">
        </div>
        <div>
            <label class="stat-label">To Date</label>
            <input type="date" name="to_date" value="{{ $toDate }}" class="filter-control" style="width: 160px;">
        </div>
        <button type="submit" class="btn-gold" style="height: 42px; padding: 0 24px;">
            <i class="bi bi-funnel"></i> Filter Report
        </button>
        <a href="{{ route('inventory.index') }}" class="button-outline" style="height: 42px; padding: 0 20px;">Reset</a>
    </form>
</div>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon green"><i class="bi bi-box-arrow-in-down"></i></div>
        <div class="stat-label">Total Received <span class="font-urdu">کل وصول</span></div>
        <div class="stat-value" style="color: var(--success);">{{ number_format($totalReceived ?? 0, 3) }} <small>g</small></div>
        <div class="stat-sub">
            Receipts: {{ number_format($receiptKhalis ?? 0, 3) }}g<br>
            From Invoices: {{ number_format($invoiceReceivedKhalis ?? 0, 3) }}g
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon red"><i class="bi bi-box-arrow-up"></i></div>
        <div class="stat-label">Total Given <span class="font-urdu">کل دیا گیا</span></div>
        <div class="stat-value" style="color: var(--error);">{{ number_format($givenWeight ?? 0, 3) }} <small>g</small></div>
        <div class="stat-sub">From active invoices</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon blue"><i class="bi bi-bank"></i></div>
        <div class="stat-label">Closing Balance <span class="font-urdu">اختتامی بیلنس</span></div>
        <div class="stat-value" style="color: var(--info); font-size: 2.1rem;">{{ number_format($closingBalance ?? 0, 3) }} <small>g</small></div>
        <div class="stat-sub positive">This should match physical stock</div>
    </div>
</div>

<div class="formula-box">
    <strong>Formula (Fixed):</strong> Closing = Opening ({{ number_format($inventory->opening_balance ?? 0, 3) }}g) 
    + Received ({{ number_format($totalReceived ?? 0, 3) }}g) 
    - Given ({{ number_format($givenWeight ?? 0, 3) }}g)<br><br>
    <strong style="color:#4ade80">Gold Receipts are now fully reflected in inventory.</strong>
</div>

<!-- Recent Movement -->
<div class="report-section">
    <div class="section-header">
        <i class="bi bi-list-ul"></i>
        <h3>Recent Stock Movement <span class="font-urdu">(تازہ لین دین)</span></h3>
    </div>

    <h5 style="margin:20px 0 10px;color:var(--success);">Gold Receipts (+ Stock)</h5>
    <table class="movement-table" style="width:100%;margin-bottom:30px">
        <thead>
            <tr>
                <th>Date</th>
                <th>Receipt</th>
                <th>Party</th>
                <th style="text-align:right">Khalis Added</th>
            </tr>
        </thead>
        <tbody>
            @forelse($recentReceipts as $r)
            <tr>
                <td>{{ $r->receipt_date->format('d/m/Y') }}</td>
                <td><strong>RCV-{{ str_pad($r->id,5,'0',STR_PAD_LEFT) }}</strong></td>
                <td>{{ $r->customer?->name }}</td>
                <td class="positive" style="text-align:right">+{{ number_format($r->total_khalis_weight,3) }}g</td>
            </tr>
            @empty
            <tr><td colspan="4" style="text-align:center;padding:30px;color:#666">No receipts found</td></tr>
            @endforelse
        </tbody>
    </table>

    <h5 style="margin:20px 0 10px;color:var(--error);">Gold Given in Invoices (- Stock)</h5>
    <table class="movement-table" style="width:100%">
        <thead>
            <tr>
                <th>Date</th>
                <th>Invoice</th>
                <th>Party</th>
                <th style="text-align:right">Effective Gold</th>
            </tr>
        </thead>
        <tbody>
            @forelse($recentInvoices as $inv)
            <tr>
                <td>{{ $inv->invoice_date->format('d/m/Y') }}</td>
                <td><strong>INV-{{ str_pad($inv->id,5,'0',STR_PAD_LEFT) }}</strong></td>
                <td>{{ $inv->customer?->name }}</td>
                <td class="negative" style="text-align:right">-{{ number_format($inv->effective_gold ?? 0,3) }}g</td>
            </tr>
            @empty
            <tr><td colspan="4" style="text-align:center;padding:30px;color:#666">No invoices found</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<!-- Update Opening Stock -->
<form action="{{ route('inventory.update') }}" method="POST">
    @csrf
    <div class="form-section">
        <div class="section-header">
            <i class="bi bi-gear-fill"></i>
            <h3>Update Opening Stock</h3>
        </div>
        
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:24px">
            <div class="form-group">
                <label>Opening Balance (grams) <span class="font-urdu">ابتدائی بیلنس</span></label>
                <input type="number" step="0.001" name="opening_balance" 
                       value="{{ number_format($inventory->opening_balance ?? 0, 3, '.', '') }}" 
                       class="filter-control" required>
            </div>
            <div class="form-group">
                <label>Period Label <span class="font-urdu">مدت کا نام</span></label>
                <input type="text" name="period_label" value="{{ $inventory->period_label }}" 
                       class="filter-control" placeholder="e.g. FY 2026-27 or Physical Count March 2026">
            </div>
        </div>

        <button type="submit" class="btn-gold" style="margin-top:24px;">
            <i class="bi bi-arrow-repeat"></i> Recalculate &amp; Save Stock
        </button>
    </div>
</form>
@endsection
