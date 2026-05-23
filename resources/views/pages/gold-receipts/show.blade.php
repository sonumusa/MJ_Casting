@extends('layouts.app')

@section('title', 'Receipt ' . $goldReceipt->formatted_receipt_no)

@section('extra_css')
<style>
    .detail-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; margin-bottom: 24px; }
    .detail-card {
        background: var(--bg-surface);
        border: 1px solid var(--border-color);
        border-radius: 12px;
        padding: 20px;
        transition: all 0.2s ease;
    }
    .detail-card:hover { border-color: rgba(218,165,32,0.2); transform: translateY(-2px); box-shadow: var(--shadow-2); }
    .detail-label { font-size: 0.7rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.08em; margin-bottom: 8px; font-weight: 700; }
    .detail-value { font-size: 1.1rem; color: var(--text-primary); font-weight: 600; }
    .detail-value.font-mono { font-family: 'JetBrains Mono', monospace; }

    .section-title {
        display: flex; align-items: center; gap: 12px;
        font-family: 'Playfair Display', serif; font-size: 1.15rem; color: var(--text-primary);
        margin-bottom: 20px; padding-bottom: 12px; border-bottom: 1px solid var(--border-color);
    }
    .section-title i { color: var(--gold-primary); font-size: 1.25rem; }

    .calc-table { width: 100%; border-collapse: collapse; }
    .calc-table th { text-align: left; padding: 12px 14px; font-size: 0.72rem; color: var(--gold-muted); border-bottom: 1px solid var(--border-color); }
    .calc-table td { padding: 12px 14px; font-size: 0.9rem; border-bottom: 1px solid var(--border-color); }
    .calc-table tr:last-child td { border-bottom: none; }
    .highlight-row { background: rgba(218,165,32,0.06); }
    .highlight-row td { color: var(--gold-bright); font-weight: 700; }

    .type-chip-show {
        display: inline-flex; align-items: center; gap: 6px;
        padding: 6px 14px; border-radius: 16px; font-size: 0.8rem; font-weight: 700;
    }
    .type-chip-show.customer { background: rgba(56,189,248,0.12); color: var(--info); border: 1px solid rgba(56,189,248,0.2); }
    .type-chip-show.dukandar { background: rgba(245,158,11,0.12); color: var(--warning); border: 1px solid rgba(245,158,11,0.2); }
    .type-chip-show.karigar { background: rgba(16,185,129,0.12); color: var(--success); border: 1px solid rgba(16,185,129,0.2); }

    .action-bar { display: flex; gap: 12px; flex-wrap: wrap; margin-top: 32px; }
    .btn-action {
        display: inline-flex; align-items: center; gap: 8px; padding: 10px 20px;
        border-radius: 10px; font-weight: 600; font-size: 0.85rem; text-decoration: none; transition: all 0.2s ease;
        border: 1px solid var(--border-color); background: var(--bg-surface); color: var(--text-body);
    }
    .btn-action:hover { transform: translateY(-2px); box-shadow: var(--shadow-2); border-color: var(--gold-primary); color: var(--gold-primary); }
    .btn-action-danger { border-color: rgba(244,63,94,0.2); color: var(--error); }
    .btn-action-danger:hover { background: rgba(244,63,94,0.08); border-color: var(--error); }
    .btn-action-primary { background: linear-gradient(135deg, var(--gold-deep), var(--gold-primary)); color: white; border: none; }
    .btn-action-primary:hover { filter: brightness(1.1); color: white; }
</style>
@endsection

@section('content')
<div class="page-header" style="margin-bottom: 28px;">
    <div class="page-title-group">
        <div style="display:flex; align-items:center; gap:16px;">
            <h1>Receipt {{ $goldReceipt->formatted_receipt_no }}</h1>
            <span class="type-chip-show {{ $goldReceipt->receipt_type }}">
                <i class="bi bi-person-badge"></i> {{ ucfirst($goldReceipt->receipt_type) }}
            </span>
        </div>
        <p class="font-urdu" style="margin-top:6px;">سونا وصولی رسید</p>
    </div>
    <div style="display:flex; gap:12px;">
        <a href="{{ route('gold-receipts.print', $goldReceipt) }}" target="_blank" class="btn-action btn-action-primary">
            <i class="bi bi-printer"></i> Print
        </a>
        <a href="{{ route('gold-receipts.edit', $goldReceipt) }}" class="btn-action">
            <i class="bi bi-pencil"></i> Edit
        </a>
    </div>
</div>

<div class="detail-grid">
    <div class="detail-card">
        <div class="detail-label">Party <span class="font-urdu">گاہک</span></div>
        <div class="detail-value">
            <a href="{{ route('customers.show', $goldReceipt->customer) }}" style="color:inherit; text-decoration:none;">
                {{ $goldReceipt->customer->name ?? 'Unknown' }}
            </a>
        </div>
    </div>
    <div class="detail-card">
        <div class="detail-label">Date <span class="font-urdu">تاریخ</span></div>
        <div class="detail-value">{{ $goldReceipt->receipt_date->format('d/m/Y') }}</div>
    </div>
    <div class="detail-card">
        <div class="detail-label">Total Gross <span class="font-urdu">کل کچا</span></div>
        <div class="detail-value font-mono">{{ number_format($goldReceipt->total_gross_weight, 3) }} g</div>
    </div>
    <div class="detail-card" style="border-color: var(--gold-primary);">
        <div class="detail-label">Total Khalis <span class="font-urdu">کل خالص</span></div>
        <div class="detail-value font-mono" style="color:var(--gold-bright);">{{ number_format($goldReceipt->total_khalis_weight, 3) }} g</div>
    </div>
</div>

<div class="form-section">
    <div class="section-title">
        <i class="bi bi-box-arrow-in-down"></i>
        Items Received <span class="font-urdu" style="font-size:0.8rem; opacity:0.7; margin-left:8px;">وصول شدہ اشیاء</span>
    </div>
    <div style="overflow-x:auto;">
        <table class="calc-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Description <span class="font-urdu">تفصیل</span></th>
                    <th style="text-align:right">Gross Weight <span class="font-urdu">کچا وزن</span></th>
                    <th style="text-align:right">Ratti Impurity <span class="font-urdu">رتی نقص</span></th>
                    <th style="text-align:right">Khalis Pure <span class="font-urdu">خالص سونا</span></th>
                    <th style="text-align:right">Deduction</th>
                </tr>
            </thead>
            <tbody>
                @foreach($goldReceipt->items as $item)
                <tr>
                    <td style="color:var(--text-muted);">{{ $loop->iteration }}</td>
                    <td>{{ $item->description ?: '-' }}</td>
                    <td class="font-mono" style="text-align:right;">{{ number_format($item->gross_weight, 3) }} g</td>
                    <td class="font-mono" style="text-align:right;">{{ number_format($item->ratti_impurity, 2) }} r</td>
                    <td class="font-mono" style="text-align:right; color:var(--gold-bright); font-weight:700;">{{ number_format($item->khalis_weight, 3) }} g</td>
                    <td class="font-mono" style="text-align:right; color:var(--error);">{{ number_format($item->gross_weight - $item->khalis_weight, 3) }} g</td>
                </tr>
                @endforeach
                <tr class="highlight-row">
                    <td colspan="2" style="text-align:right;">TOTALS</td>
                    <td class="font-mono" style="text-align:right;">{{ number_format($goldReceipt->total_gross_weight, 3) }} g</td>
                    <td></td>
                    <td class="font-mono" style="text-align:right;">{{ number_format($goldReceipt->total_khalis_weight, 3) }} g</td>
                    <td class="font-mono" style="text-align:right;">{{ number_format($goldReceipt->total_gross_weight - $goldReceipt->total_khalis_weight, 3) }} g</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

@if($goldReceipt->remarks)
<div class="form-section" style="background: rgba(56,189,248,0.03);">
    <div class="section-title">
        <i class="bi bi-chat-square-text"></i>
        Remarks <span class="font-urdu" style="font-size:0.8rem; opacity:0.7; margin-left:8px;">ریمارکس</span>
    </div>
    <p style="color: var(--text-secondary); line-height: 1.6;">{{ $goldReceipt->remarks }}</p>
</div>
@endif

<div class="form-section" style="background: rgba(218,165,32,0.03);">
    <div class="section-title">
        <i class="bi bi-calculator"></i>
        Conversion Summary <span class="font-urdu" style="font-size:0.8rem; opacity:0.7; margin-left:8px;">تبدیلی خلاصہ</span>
    </div>
    <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px;">
        <div class="detail-card" style="margin-bottom:0;">
            <div class="detail-label">Gross Weight</div>
            <div class="detail-value font-mono">{{ number_format($goldReceipt->total_gross_weight, 3) }} g</div>
        </div>
        <div class="detail-card" style="margin-bottom:0;">
            <div class="detail-label">Deduction</div>
            <div class="detail-value font-mono" style="color:var(--error);">{{ number_format($goldReceipt->total_gross_weight - $goldReceipt->total_khalis_weight, 3) }} g</div>
        </div>
        <div class="detail-card" style="margin-bottom:0; border-color:var(--gold-primary);">
            <div class="detail-label">Pure Khalis Added to Inventory</div>
            <div class="detail-value font-mono" style="color:var(--gold-bright);">{{ number_format($goldReceipt->total_khalis_weight, 3) }} g</div>
        </div>
    </div>
    <div style="margin-top:14px; font-size:0.78rem; color:var(--text-muted); font-style:italic; text-align:center;">
        Formula: Khalis = Gross - (Gross / 96 × Ratti) | خالص = کچا - (کچا / ۹۶ × رتی)
    </div>
</div>

<div class="action-bar">
    <a href="{{ route('gold-receipts.print', $goldReceipt) }}" target="_blank" class="btn-action btn-action-primary">
        <i class="bi bi-printer"></i> Print Receipt
    </a>
    <a href="{{ route('gold-receipts.edit', $goldReceipt) }}" class="btn-action">
        <i class="bi bi-pencil"></i> Edit Receipt
    </a>
    <a href="{{ route('gold-receipts.index') }}" class="btn-action">
        <i class="bi bi-arrow-left"></i> Back to Receipts
    </a>
    <form action="{{ route('gold-receipts.destroy', $goldReceipt) }}" method="POST" onsubmit="return confirm('Are you sure?')" style="display:inline;">
        @csrf
        @method('DELETE')
        <button type="submit" class="btn-action btn-action-danger">
            <i class="bi bi-trash3"></i> Delete
        </button>
    </form>
</div>
@endsection
