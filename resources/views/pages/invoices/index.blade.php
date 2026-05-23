@extends('layouts.app')

@section('title', 'Invoices')

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
    tr:last-child td { border-bottom: none; }
    tr:hover td { background-color: rgba(255,255,255,0.03); }

    .invoice-no { color: var(--gold-primary); font-weight: 700; font-family: 'JetBrains Mono', monospace; }
    .book-no { color: var(--text-muted); font-size: 0.78rem; }
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

    .status-badge {
        padding: 4px 12px;
        border-radius: 999px;
        font-size: 0.72rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.04em;
    }
    .status-active { background-color: rgba(16, 185, 129, 0.1); color: var(--success); border: 1px solid rgba(16,185,129,0.2); }
    .status-cancelled { background-color: rgba(244, 63, 94, 0.1); color: var(--error); border: 1px solid rgba(244,63,94,0.2); }

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
    .btn-edit:hover { color: var(--gold-primary); border-color: rgba(218,165,32,0.3); }
    .btn-print:hover { color: var(--text-primary); border-color: rgba(255,255,255,0.2); }
    .btn-delete:hover { color: var(--error); border-color: rgba(244,63,94,0.3); }

    .sync-indicator { font-size: 0.85rem; margin-left: 6px; }
    .sync-pending { color: var(--warning); }
    .sync-failed { color: var(--error); }

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

    /* Fixed New Invoice Button */
    .btn-gold {
        background: linear-gradient(135deg, #B8860B 0%, #DAA520 100%) !important;
        color: white !important;
        border: none;
        padding: 12px 28px !important;
        border-radius: 12px;
        font-weight: 600;
        box-shadow: 0 4px 15px rgba(218, 165, 32, 0.4);
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 10px;
        transition: all 0.3s cubic-bezier(0.4, 0.0, 0.2, 1);
        font-size: 1rem;
        letter-spacing: 0.025em;
    }
    .btn-gold:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 25px rgba(218, 165, 32, 0.5);
        color: white !important;
    }
</style>
@endsection

@section('content')
    <div class="page-header">
        <div class="page-title-group">
            <h1>Invoices</h1>
            <p class="font-urdu">بل</p>
        </div>
        <div style="display: flex; gap: 14px;">
            <a href="{{ route('invoices.export') }}" class="button-outline">
                <i class="bi bi-download"></i> Export
            </a>
            <a href="{{ route('invoices.create') }}" class="btn-gold">
                <i class="bi bi-plus-lg"></i> New Invoice
            </a>
        </div>
    </div>

    <!-- Filter Panel -->
    <div class="filter-panel">
        <div style="display: flex; justify-content: space-between; align-items: center; cursor: pointer;" onclick="toggleFilters()">
            <div style="display: flex; align-items: center; gap: 12px;">
                <i class="bi bi-funnel" style="color: var(--gold-primary);"></i>
                <span style="font-weight: 700;">Search & Filters</span>
            </div>
            <i class="bi bi-chevron-down" id="filter-chevron"></i>
        </div>

        <form method="GET" action="{{ route('invoices.index') }}" id="filter-form" style="{{ request()->anyFilled(['search', 'customer_id', 'invoice_type', 'from_date', 'to_date']) ? '' : 'display: none;' }}">
            <div class="filter-grid">
                <div class="filter-group">
                    <label>Search</label>
                    <input type="text" name="search" value="{{ request('search') }}" class="filter-control" placeholder="Invoice # or Book #">
                </div>
                <div class="filter-group">
                    <label>Customer</label>
                    <select name="customer_id" class="filter-control">
                        <option value="">All Customers</option>
                        @foreach($customers as $customer)
                            <option value="{{ $customer->id }}" {{ request('customer_id') == $customer->id ? 'selected' : '' }}>{{ $customer->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="filter-group">
                    <label>Invoice Type</label>
                    <select name="invoice_type" class="filter-control">
                        <option value="">All Types</option>
                        <option value="customer" {{ request('invoice_type') === 'customer' ? 'selected' : '' }}>Customer</option>
                        <option value="dukandar" {{ request('invoice_type') === 'dukandar' ? 'selected' : '' }}>Dukandar</option>
                        <option value="karigar" {{ request('invoice_type') === 'karigar' ? 'selected' : '' }}>Karigar</option>
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
                <a href="{{ route('invoices.index') }}" class="button-outline" style="padding: 10px 18px;">Reset</a>
                <button type="submit" class="btn-gold" style="padding: 10px 28px;">Apply Filters</button>
            </div>
        </form>
    </div>

    <!-- Invoices Table -->
    <div class="table-card">
        @if($invoices->isEmpty())
            <div style="padding: 48px; text-align: center; color: var(--text-muted);">
                <i class="bi bi-inbox" style="font-size: 3.5rem; display: block; margin-bottom: 20px; opacity: 0.25;"></i>
                <p style="font-size: 1rem;">No invoices found matching your criteria.</p>
            </div>
        @else
            <div style="overflow-x: auto;">
                <table>
                    <thead>
                        <tr>
                            <th style="width: 40px;"></th>
                            <th>Invoice No <br><span class="font-urdu" style="font-size:0.7rem; opacity:0.8;">بل نمبر</span></th>
                            <th>Type <br><span class="font-urdu" style="font-size:0.7rem; opacity:0.8;">قسم</span></th>
                            <th>Book No <br><span class="font-urdu" style="font-size:0.7rem; opacity:0.8;">کتاب نمبر</span></th>
                            <th>Date <br><span class="font-urdu" style="font-size:0.7rem; opacity:0.8;">تاریخ</span></th>
                            <th>Party <br><span class="font-urdu" style="font-size:0.7rem; opacity:0.8;">گاہک</span></th>
                            <th style="text-align:right">Gold Khalis <br><span class="font-urdu" style="font-size:0.7rem; opacity:0.8;">خالص سونا</span></th>
                            <th style="text-align:right">Received <br><span class="font-urdu" style="font-size:0.7rem; opacity:0.8;">وصول</span></th>
                            <th style="text-align:right">Wasooli <br><span class="font-urdu" style="font-size:0.7rem; opacity:0.8;">وصولی</span></th>
                            <th style="text-align:right">Remaining <br><span class="font-urdu" style="font-size:0.7rem; opacity:0.8;">باقی</span></th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $totalGoldKhalis = 0;
                            $totalReceived = 0;
                            $totalWasooli = 0;
                        @endphp
                        @foreach($invoices as $invoice)
                            <tr>
                                <td style="padding: 14px 8px; text-align:center;">
                                    @if($invoice->sync_status === 'pending')
                                        <i class="bi bi-clock-history sync-indicator sync-pending" title="Pending Sync"></i>
                                    @elseif($invoice->sync_status === 'failed')
                                        <i class="bi bi-exclamation-circle sync-indicator sync-failed" title="Sync Failed"></i>
                                    @else
                                        <i class="bi bi-check-circle" style="color: var(--success); font-size: 0.8rem; opacity: 0.6;"></i>
                                    @endif
                                </td>
                                <td>
                                    <span class="invoice-no">{{ $invoice->formatted_invoice_no }}</span>
                                </td>
                                <td>
                                    <span class="type-chip {{ $invoice->invoice_type }}">
                                        <i class="bi bi-person-badge"></i>
                                        {{ ucfirst($invoice->invoice_type) }}
                                    </span>
                                </td>
                                <td><span class="book-no">{{ $invoice->manual_book_no ?? '-' }}</span></td>
                                <td style="color: var(--text-secondary);">{{ $invoice->invoice_date->format('d/m/Y') }}</td>
                                <td>
                                    <a href="{{ route('customers.show', $invoice->customer) }}" style="color: var(--text-body); font-weight: 600; text-decoration: none;">
                                        {{ $invoice->customer->name ?? 'Unknown' }}
                                    </a>
                                </td>
                                <td class="font-mono" style="text-align:right;">{{ number_format($invoice->gold_khalis, 3) }}g</td>
                                <td class="font-mono" style="text-align:right; color: var(--success);">{{ number_format($invoice->total_received_khalis, 3) }}g</td>
                                <td class="font-mono" style="text-align:right;">{{ number_format($invoice->wasooli, 3) }}g</td>
                                <td class="font-mono" style="text-align:right; color: {{ $invoice->remaining_balance > 0 ? 'var(--error)' : 'var(--success)' }};">
                                    {{ number_format($invoice->remaining_balance, 3) }}g
                                </td>
                                <td>
                                    <span class="status-badge status-{{ $invoice->status }}">
                                        {{ $invoice->status }}
                                    </span>
                                </td>
                                @php
                                    $totalGoldKhalis += $invoice->gold_khalis;
                                    $totalReceived += $invoice->total_received_khalis;
                                    $totalWasooli += $invoice->wasooli;
                                @endphp
                                <td>
                                    <div class="action-btns">
                                        <a href="{{ route('invoices.show', $invoice) }}" class="action-btn btn-view" title="View">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <a href="{{ route('invoices.edit', $invoice) }}" class="action-btn btn-edit" title="Edit">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <a href="{{ route('invoices.print', $invoice) }}" target="_blank" class="action-btn btn-print" title="Print">
                                            <i class="bi bi-printer"></i>
                                        </a>
                                        <form action="{{ route('invoices.destroy', $invoice) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this invoice?')">
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
                    <div class="summary-label">Total Gold Khalis</div>
                    <div class="summary-value" style="color: var(--gold-bright);">{{ number_format($totalGoldKhalis, 3) }}g</div>
                </div>
                <div class="summary-item">
                    <div class="summary-label">Total Received</div>
                    <div class="summary-value" style="color: var(--success);">{{ number_format($totalReceived, 3) }}g</div>
                </div>
                <div class="summary-item">
                    <div class="summary-label">Total Wasooli</div>
                    <div class="summary-value" style="color: var(--info);">{{ number_format($totalWasooli, 3) }}g</div>
                </div>
                <div class="summary-item">
                    <div class="summary-label">Records</div>
                    <div class="summary-value">{{ $invoices->total() }}</div>
                </div>
            </div>

            <div class="pagination-container">
                <div style="color: var(--text-muted); font-size: 0.85rem;">
                    Showing {{ $invoices->firstItem() ?? 0 }} to {{ $invoices->lastItem() ?? 0 }} of {{ $invoices->total() }} results
                </div>
                <div>
                    {{ $invoices->links() }}
                </div>
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
