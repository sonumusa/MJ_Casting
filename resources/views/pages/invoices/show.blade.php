@extends('layouts.app')

@section('title', 'Invoice ' . $invoice->formatted_invoice_no)

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
    .calc-formula { font-size: 0.75rem; color: var(--text-muted); font-style: italic; }

    .highlight-row { background: rgba(218,165,32,0.06); }
    .highlight-row td { color: var(--gold-bright); font-weight: 700; }

    .status-badge-show {
        display: inline-flex; align-items: center; gap: 6px;
        padding: 6px 14px; border-radius: 20px; font-size: 0.8rem; font-weight: 700;
    }
    .status-active-show { background: rgba(16,185,129,0.12); color: var(--success); border: 1px solid rgba(16,185,129,0.2); }
    .status-cancelled-show { background: rgba(244,63,94,0.12); color: var(--error); border: 1px solid rgba(244,63,94,0.2); }

    .action-bar {
        display: flex; gap: 12px; flex-wrap: wrap; margin-top: 32px;
    }
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
            <h1>Invoice {{ $invoice->formatted_invoice_no }}</h1>
            <span class="status-badge-show status-{{ $invoice->status }}-show">{{ ucfirst($invoice->status) }}</span>
        </div>
        <p class="font-urdu" style="margin-top:6px;">بل تفصیل</p>
    </div>
    <div style="display:flex; gap:12px;">
        <a href="{{ route('invoices.print', $invoice) }}" target="_blank" class="btn-action btn-action-primary">
            <i class="bi bi-printer"></i> Print
        </a>
        <a href="{{ route('invoices.edit', $invoice) }}" class="btn-action">
            <i class="bi bi-pencil"></i> Edit
        </a>
    </div>
</div>

<div class="detail-grid">
    <div class="detail-card">
        <div class="detail-label">Invoice Type <span class="font-urdu">بل کی قسم</span></div>
        <div class="detail-value">{{ $invoice->invoice_type_label }}</div>
    </div>
    <div class="detail-card">
        <div class="detail-label">Party <span class="font-urdu">گاہک</span></div>
        <div class="detail-value">
            <a href="{{ route('customers.show', $invoice->customer) }}" style="color:inherit; text-decoration:none;">
                {{ $invoice->customer->name ?? 'Unknown' }}
            </a>
        </div>
    </div>
    <div class="detail-card">
        <div class="detail-label">Date <span class="font-urdu">تاریخ</span></div>
        <div class="detail-value">{{ $invoice->invoice_date->format('d/m/Y') }}</div>
    </div>
    <div class="detail-card">
        <div class="detail-label">Book No <span class="font-urdu">بک نمبر</span></div>
        <div class="detail-value">{{ $invoice->manual_book_no ?? '-' }}</div>
    </div>
</div>

@if($invoice->receives->count() > 0)
<div class="form-section">
    <div class="section-title">
        <i class="bi bi-box-arrow-in-down"></i>
        Gold Received from Party <span class="font-urdu" style="font-size:0.8rem; opacity:0.7; margin-left:8px;">سونا وصول کیا</span>
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
                </tr>
            </thead>
            <tbody>
                @foreach($invoice->receives as $receive)
                <tr>
                    <td style="color:var(--text-muted);">{{ $loop->iteration }}</td>
                    <td>{{ $receive->description ?: '-' }}</td>
                    <td class="font-mono" style="text-align:right;">{{ number_format($receive->gross_weight, 3) }} g</td>
                    <td class="font-mono" style="text-align:right;">{{ number_format($receive->ratti_impurity, 2) }} r</td>
                    <td class="font-mono" style="text-align:right; color:var(--gold-bright); font-weight:700;">{{ number_format($receive->khalis_weight, 3) }} g</td>
                </tr>
                @endforeach
                <tr class="highlight-row">
                    <td colspan="4" style="text-align:right;">Total Received Khalis <span class="font-urdu">کل وصول خالص</span></td>
                    <td class="font-mono" style="text-align:right;">{{ number_format($invoice->total_received_khalis, 3) }} g</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
@endif

<div class="form-section">
    <div class="section-title">
        <i class="bi bi-calculator"></i>
        Gold Calculation <span class="font-urdu" style="font-size:0.8rem; opacity:0.7; margin-left:8px;">ذہبی حساب</span>
    </div>
    <div style="overflow-x:auto;">
        <table class="calc-table">
            @foreach($breakdown['steps'] as $step)
            <tr class="{{ in_array($step['label'], ['Gold Khalis', 'Effective Gold', 'Grand Total', 'Received Khalis']) ? 'highlight-row' : '' }}">
                <td style="width: 30%;">{{ $step['label'] }}</td>
                <td style="width: 40%;" class="calc-formula">{{ $step['formula'] }}</td>
                <td style="width: 30%; text-align:right;" class="font-mono">{{ number_format($step['value'], 3) }} g</td>
            </tr>
            @endforeach
        </table>
    </div>
</div>

<div class="form-section">
    <div class="section-title">
        <i class="bi bi-cash-stack"></i>
        Balance Summary <span class="font-urdu" style="font-size:0.8rem; opacity:0.7; margin-left:8px;">بقایا خلاصہ</span>
    </div>
    <div class="detail-grid" style="margin-bottom:0;">
        <div class="detail-card">
            <div class="detail-label">Previous Balance</div>
            <div class="detail-value font-mono">{{ number_format($breakdown['balance_chain']['previous_balance'], 3) }} g</div>
        </div>
        <div class="detail-card">
            <div class="detail-label">+ This Invoice</div>
            <div class="detail-value font-mono">{{ number_format($breakdown['balance_chain']['grand_total'], 3) }} g</div>
        </div>
        <div class="detail-card">
            <div class="detail-label">- Received Khalis</div>
            <div class="detail-value font-mono" style="color:var(--success);">{{ number_format($breakdown['balance_chain']['received_khalis'] ?? 0, 3) }} g</div>
        </div>
        <div class="detail-card">
            <div class="detail-label">- Wasooli</div>
            <div class="detail-value font-mono">{{ number_format($breakdown['balance_chain']['wasooli'], 3) }} g</div>
        </div>
        <div class="detail-card" style="border-color: {{ $breakdown['balance_chain']['remaining_balance'] > 0 ? 'var(--error)' : 'var(--success)' }};">
            <div class="detail-label">Remaining Balance</div>
            <div class="detail-value font-mono" style="color: {{ $breakdown['balance_chain']['remaining_balance'] > 0 ? 'var(--error)' : 'var(--success)' }};">
                {{ number_format($breakdown['balance_chain']['remaining_balance'], 3) }} g
            </div>
        </div>
    </div>
</div>

@if($invoice->remarks)
<div class="form-section" style="background: rgba(56,189,248,0.03);">
    <div class="section-title">
        <i class="bi bi-chat-square-text"></i>
        Remarks <span class="font-urdu" style="font-size:0.8rem; opacity:0.7; margin-left:8px;">ریمارکس</span>
    </div>
    <p style="color: var(--text-secondary); line-height: 1.6;">{{ $invoice->remarks }}</p>
</div>
@endif

<div class="action-bar">
    <a href="{{ route('invoices.print', $invoice) }}" target="_blank" class="btn-action btn-action-primary">
        <i class="bi bi-printer"></i> Print Invoice
    </a>
    <a href="{{ route('invoices.edit', $invoice) }}" class="btn-action">
        <i class="bi bi-pencil"></i> Edit Invoice
    </a>
    <a href="{{ route('invoices.index') }}" class="btn-action">
        <i class="bi bi-arrow-left"></i> Back to Invoices
    </a>
    <form action="{{ route('invoices.destroy', $invoice) }}" method="POST" onsubmit="return confirm('Are you sure?')" style="display:inline;">
        @csrf
        @method('DELETE')
        <button type="submit" class="btn-action btn-action-danger">
            <i class="bi bi-trash3"></i> Delete
        </button>
    </form>
</div>
@endsection
