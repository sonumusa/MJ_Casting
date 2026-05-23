@extends('layouts.app')

@section('title', 'Gold Receipts')

@section('extra_css')
<style>
    .page-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 28px; }
    .page-title-group h1 { font-size: 1.9rem; color: var(--text-primary); margin: 0; font-weight: 700; }
    .page-title-group p { font-size: 1.05rem; color: var(--gold-muted); margin: 0; }

    .filter-panel {
        background-color: var(--bg-card);
        border: 1px solid var(--border-color);
        border-radius: 16px;
        padding: 24px;
        margin-bottom: 28px;
        box-shadow: var(--shadow-1);
    }
    .filter-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 18px;
        margin-top: 18px;
    }
    .filter-group label {
        display: block;
        font-size: 0.72rem;
        color: var(--text-secondary);
        margin-bottom: 8px;
        text-transform: uppercase;
        letter-spacing: 0.06em;
        font-weight: 600;
    }
    .filter-control {
        width: 100%;
        background-color: var(--bg-surface);
        border: 1px solid var(--border-color);
        border-radius: 10px;
        padding: 10px 14px;
        color: var(--text-body);
        font-size: 0.9rem;
    }
    .filter-control:focus { outline: none; border-color: var(--gold-primary); box-shadow: 0 0 0 3px rgba(218,165,32,0.1); }

    .table-card {
        background-color: var(--bg-card);
        border: 1px solid var(--border-color);
        border-radius: 16px;
        overflow: hidden;
        box-shadow: var(--shadow-1);
    }
    table { width: 100%; border-collapse: collapse; }
    th {
        background-color: rgba(255,255,255,0.02);
        text-align: left;
        padding: 16px 14px;
        color: var(--gold-primary);
        font-size: 0.72rem;
        text-transform: uppercase;
        letter-spacing: 0.06em;
        font-weight: 700;
        border-bottom: 2px solid var(--border-color);
    }
    td { padding: 16px 14px; border-bottom: 1px solid var(--border-color); vertical-align: middle; font-size: 0.88rem; }
    tr:hover td { background-color: rgba(255,255,255,0.03); }
    tr:last-child td { border-bottom: none; }

    .receipt-no { color: var(--gold-primary); font-weight: 700; font-family: 'JetBrains Mono', monospace; }
    .type-chip {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 4px 10px;
        border-radius: 16px;
        background: var(--bg-surface);
        border: 1px solid var(--border-color);
        color: var(--text-secondary);
        font-size: 0.75rem;
        font-weight: 600;
    }
    .type-chip.customer { border-color: rgba(56,189,248,0.3); color: var(--info); background: rgba(56,189,248,0.08); }
    .type-chip.dukandar { border-color: rgba(245,158,11,0.3); color: var(--warning); background: rgba(245,158,11,0.08); }
    .type-chip.karigar { border-color: rgba(16,185,129,0.3); color: var(--success); background: rgba(16,185,129,0.08); }

    .action-btns { display: flex; gap: 8px; }
    .action-btn {
        width: 34px;
        height: 34px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        text-decoration: none;
        transition: all 0.2s ease;
        border: 1px solid var(--border-color);
        background-color: var(--bg-surface);
        color: var(--text-secondary);
        font-size: 0.9rem;
    }
    .action-btn:hover { transform: translateY(-2px); box-shadow: var(--shadow-2); }
    .btn-view:hover { color: var(--info); border-color: rgba(56,189,248,0.3); }
    .btn-print:hover { color: var(--text-primary); border-color: rgba(255,255,255,0.2); }
    .btn-delete:hover { color: var(--error); border-color: rgba(244,63,94,0.3); }

    .pagination-container {
        padding: 20px 24px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-top: 1px solid var(--border-color);
    }
    .summary-bar {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
        gap: 16px;
        padding: 20px 24px;
        border-top: 1px solid var(--border-color);
        background: rgba(255,255,255,0.01);
    }
    .summary-item { text-align: center; }
    .summary-label { font-size: 0.7rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.08em; margin-bottom: 6px; font-weight: 600; }
    .summary-value { font-family: 'JetBrains Mono', monospace; font-size: 1.1rem; font-weight: 700; }
</style>
@endsection

@section('content')
    <div class="page-header">
        <div class="page-title-group">
            <h1>Gold Receipts</h1>
            <p class="font-urdu">سونا وصولی رسیدیں</p>
        </div>
        <a href="{{ route('gold-receipts.create') }}" class="btn-gold">
            <i class="bi bi-plus-lg"></i> New Receipt
        </a>
    </div>

    <div class="filter-panel">
        <div style="display: flex; justify-content: space-between; align-items: center; cursor: pointer;" onclick="toggleFilters()">
            <div style="display: flex; align-items: center; gap: 12px;">
                <i class="bi bi-funnel" style="color: var(--gold-primary);"></i>
                <span style="font-weight: 700;">Search & Filters</span>
            </div>
            <i class="bi bi-chevron-down" id="filter-chevron"></i>
        </div>

        <form method="GET" action="{{ route('gold-receipts.index') }}" id="filter-form" style="{{ request()->anyFilled(['search', 'customer_id', 'receipt_type', 'from_date', 'to_date']) ? '' : 'display: none;' }}">
            <div class="filter-grid">
                <div class="filter-group">
                    <label>Search</label>
                    <input type="text" name="search" value="{{ request('search') }}" class="filter-control" placeholder="Receipt #">
                </div>
                <div class="filter-group">
                    <label>Party</label>
                    <select name="customer_id" class="filter-control">
                        <option value="">All Parties</option>
                        @foreach($customers as $customer)
                            <option value="{{ $customer->id }}" {{ request('customer_id') == $customer->id ? 'selected' : '' }}>{{ $customer->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="filter-group">
                    <label>Type</label>
                    <select name="receipt_type" class="filter-control">
                        <option value="">All Types</option>
                        <option value="customer" {{ request('receipt_type') === 'customer' ? 'selected' : '' }}>Customer</option>
                        <option value="dukandar" {{ request('receipt_type') === 'dukandar' ? 'selected' : '' }}>Dukandar</option>
                        <option value="karigar" {{ request('receipt_type') === 'karigar' ? 'selected' : '' }}>Karigar</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label>From Date</label>
                    <input type="date" name="from_date" value="{{ request('from_date') }}" class="filter-control">
                </div>
                <div class="filter-group">
                    <label>To Date</label>
                    <input type="date" name="to_date" value="{{ request('to_date') }}" class="filter-control">
                </div>
            </div>
            <div style="margin-top: 22px; display: flex; justify-content: flex-end; gap: 12px;">
                <a href="{{ route('gold-receipts.index') }}" class="button-outline" style="padding: 10px 18px;">Reset</a>
                <button type="submit" class="btn-gold" style="padding: 10px 28px;">Apply Filters</button>
            </div>
        </form>
    </div>

    <div class="table-card">
        @if($receipts->isEmpty())
            <div style="padding: 48px; text-align: center; color: var(--text-muted);">
                <i class="bi bi-inbox" style="font-size: 3.5rem; display: block; margin-bottom: 20px; opacity: 0.25;"></i>
                <p style="font-size: 1rem;">No receipts found.</p>
            </div>
        @else
            <div style="overflow-x: auto;">
                <table>
                    <thead>
                        <tr>
                            <th>Receipt No <br><span class="font-urdu">رسید نمبر</span></th>
                            <th>Type <br><span class="font-urdu">قسم</span></th>
                            <th>Date <br><span class="font-urdu">تاریخ</span></th>
                            <th>Party <br><span class="font-urdu">گاہک</span></th>
                            <th style="text-align:right">Gross <br><span class="font-urdu">کچا</span></th>
                            <th style="text-align:right">Khalis <br><span class="font-urdu">خالص</span></th>
                            <th>Items</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $totalGross = 0;
                            $totalKhalis = 0;
                        @endphp
                        @foreach($receipts as $receipt)
                            <tr>
                                <td><span class="receipt-no">{{ $receipt->formatted_receipt_no }}</span></td>
                                <td>
                                    <span class="type-chip {{ $receipt->receipt_type }}">
                                        <i class="bi bi-person-badge"></i>
                                        {{ ucfirst($receipt->receipt_type) }}
                                    </span>
                                </td>
                                <td style="color: var(--text-secondary);">{{ $receipt->receipt_date->format('d/m/Y') }}</td>
                                <td>
                                    <a href="{{ route('customers.show', $receipt->customer) }}" style="color: var(--text-body); font-weight: 600; text-decoration: none;">
                                        {{ $receipt->customer->name ?? 'Unknown' }}
                                    </a>
                                </td>
                                <td class="font-mono" style="text-align:right;">{{ number_format($receipt->total_gross_weight, 3) }}g</td>
                                <td class="font-mono" style="text-align:right; color: var(--gold-bright); font-weight:700;">{{ number_format($receipt->total_khalis_weight, 3) }}g</td>
                                <td style="color: var(--text-muted);">{{ $receipt->items->count() }} items</td>
                                @php
                                    $totalGross += $receipt->total_gross_weight;
                                    $totalKhalis += $receipt->total_khalis_weight;
                                @endphp
                                <td>
                                    <div class="action-btns">
                                        <a href="{{ route('gold-receipts.show', $receipt) }}" class="action-btn btn-view" title="View">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <a href="{{ route('gold-receipts.print', $receipt) }}" target="_blank" class="action-btn btn-print" title="Print">
                                            <i class="bi bi-printer"></i>
                                        </a>
                                        <form action="{{ route('gold-receipts.destroy', $receipt) }}" method="POST" onsubmit="return confirm('Delete this receipt?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="action-btn btn-delete" title="Delete">
                                                <i class="bi bi-trash3"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="summary-bar">
                <div class="summary-item">
                    <div class="summary-label">Total Gross</div>
                    <div class="summary-value" style="color: var(--text-secondary);">{{ number_format($totalGross, 3) }}g</div>
                </div>
                <div class="summary-item">
                    <div class="summary-label">Total Khalis</div>
                    <div class="summary-value" style="color: var(--gold-bright);">{{ number_format($totalKhalis, 3) }}g</div>
                </div>
                <div class="summary-item">
                    <div class="summary-label">Records</div>
                    <div class="summary-value">{{ $receipts->total() }}</div>
                </div>
            </div>

            <div class="pagination-container">
                <div style="color: var(--text-muted); font-size: 0.85rem;">
                    Showing {{ $receipts->firstItem() ?? 0 }} to {{ $receipts->lastItem() ?? 0 }} of {{ $receipts->total() }} results
                </div>
                <div>{{ $receipts->links() }}</div>
            </div>
        @endif
    </div>
@endsection

@section('extra_js')
<script>
    function toggleFilters() {
        const form = document.getElementById('filter-form');
        const chevron = document.getElementById('filter-chevron');
        if (form.style.display === 'none') {
            form.style.display = 'block';
            chevron.style.transform = 'rotate(180deg)';
        } else {
            form.style.display = 'none';
            chevron.style.transform = 'rotate(0deg)';
        }
    }
</script>
@endsection
