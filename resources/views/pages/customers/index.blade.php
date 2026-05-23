@extends('layouts.app')

@section('title', 'Parties')

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

    .balance-positive { color: var(--error); font-weight: 700; }
    .balance-zero { color: var(--success); font-weight: 700; }
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
    .btn-delete:hover { color: var(--error); border-color: rgba(244,63,94,0.3); }
    .pagination-container {
        padding: 20px 24px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-top: 1px solid var(--border-color);
    }
</style>
@endsection

@section('content')
<div class="page-header">
    <div class="page-title-group">
        <h1>Parties</h1>
        <p class="font-urdu">گاہک / دوکاندار / کاریگر</p>
    </div>
    <a href="{{ route('customers.create') }}" class="btn-gold">
        <i class="bi bi-plus-lg"></i> New Party
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
    <form method="GET" action="{{ route('customers.index') }}" id="filter-form" style="{{ request()->anyFilled(['search','party_type']) ? '' : 'display: none;' }}">
        <div class="filter-grid">
            <div class="filter-group">
                <label>Search</label>
                <input type="text" name="search" value="{{ request('search') }}" class="filter-control" placeholder="Name or phone">
            </div>
            <div class="filter-group">
                <label>Party Type</label>
                <select name="party_type" class="filter-control">
                    <option value="">All Types</option>
                    <option value="customer" {{ request('party_type') === 'customer' ? 'selected' : '' }}>Customer</option>
                    <option value="dukandar" {{ request('party_type') === 'dukandar' ? 'selected' : '' }}>Dukandar</option>
                    <option value="karigar" {{ request('party_type') === 'karigar' ? 'selected' : '' }}>Karigar</option>
                </select>
            </div>
        </div>
        <div style="margin-top: 22px; display: flex; justify-content: flex-end; gap: 12px;">
            <a href="{{ route('customers.index') }}" class="button-outline" style="padding: 10px 18px;">Reset</a>
            <button type="submit" class="btn-gold" style="padding: 10px 28px;">Apply</button>
        </div>
    </form>
</div>

<div class="table-card">
    @if($customers->isEmpty())
        <div style="padding: 48px; text-align: center; color: var(--text-muted);">
            <i class="bi bi-people" style="font-size: 3.5rem; display: block; margin-bottom: 20px; opacity: 0.25;"></i>
            <p style="font-size: 1rem;">No parties found.</p>
        </div>
    @else
        <div style="overflow-x: auto;">
            <table>
                <thead>
                    <tr>
                        <th>Name <br><span class="font-urdu" style="font-size:0.7rem; opacity:0.8;">نام</span></th>
                        <th>Type <br><span class="font-urdu" style="font-size:0.7rem; opacity:0.8;">قسم</span></th>
                        <th>Phone <br><span class="font-urdu" style="font-size:0.7rem; opacity:0.8;">فون</span></th>
                        <th>City <br><span class="font-urdu" style="font-size:0.7rem; opacity:0.8;">شہر</span></th>
                        <th style="text-align:right">Balance <br><span class="font-urdu" style="font-size:0.7rem; opacity:0.8;">بقایا</span></th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($customers as $customer)
                        <tr>
                            <td>
                                <a href="{{ route('customers.show', $customer) }}" style="color: var(--text-primary); font-weight: 600; text-decoration: none;">
                                    {{ $customer->name }}
                                </a>
                            </td>
                            <td>
                                <span class="type-chip {{ $customer->party_type }}">
                                    <i class="bi bi-person-badge"></i>
                                    {{ ucfirst($customer->party_type) }}
                                </span>
                            </td>
                            <td style="color: var(--text-secondary);">{{ $customer->phone ?: '-' }}</td>
                            <td style="color: var(--text-secondary);">{{ $customer->city ?: '-' }}</td>
                            <td class="font-mono" style="text-align:right;">
                                @php $bal = $customer->getCurrentBalance(); @endphp
                                <span class="{{ $bal > 0 ? 'balance-positive' : 'balance-zero' }}">
                                    {{ number_format($bal, 3) }}g
                                </span>
                            </td>
                            <td>
                                <div class="action-btns">
                                    <a href="{{ route('customers.show', $customer) }}" class="action-btn btn-view" title="View">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <a href="{{ route('customers.edit', $customer) }}" class="action-btn btn-edit" title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <form action="{{ route('customers.destroy', $customer) }}" method="POST" onsubmit="return confirm('Delete this party?')" style="display:inline;">
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
        <div class="pagination-container">
            <div style="color: var(--text-muted); font-size: 0.85rem;">
                Showing {{ $customers->firstItem() ?? 0 }} to {{ $customers->lastItem() ?? 0 }} of {{ $customers->total() }} results
            </div>
            <div>{{ $customers->links() }}</div>
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
