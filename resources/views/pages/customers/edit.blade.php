@extends('layouts.app')

@section('title', 'Edit Party: ' . $customer->name)

@section('extra_css')
<style>
    .form-section {
        background-color: var(--bg-card);
        border: 1px solid var(--border-color);
        border-radius: 16px;
        padding: 28px;
        margin-bottom: 28px;
        box-shadow: var(--shadow-1);
    }
    .section-header {
        display: flex;
        align-items: center;
        gap: 14px;
        margin-bottom: 24px;
        padding-bottom: 14px;
        border-bottom: 1px solid var(--border-color);
        position: relative;
    }
    .section-header::after {
        content: '';
        position: absolute;
        bottom: -1px;
        left: 0;
        width: 80px;
        height: 2px;
        background: var(--gold-primary);
        border-radius: 2px;
    }
    .section-header i { color: var(--gold-primary); font-size: 1.35rem; }
    .section-header h3 { font-family: 'Playfair Display', serif; font-size: 1.15rem; color: var(--text-primary); margin: 0; }
    .input-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 22px;
    }
    .full-width { grid-column: span 2; }
    .form-group label {
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-size: 0.75rem;
        color: var(--text-secondary);
        margin-bottom: 8px;
        text-transform: uppercase;
        letter-spacing: 0.06em;
        font-weight: 600;
    }
    .btn-gold {
        background: linear-gradient(135deg, var(--gold-deep) 0%, var(--gold-primary) 100%);
        color: white;
        border: none;
        padding: 12px 28px;
        border-radius: 10px;
        font-weight: 700;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 10px;
        transition: all 0.2s ease;
        text-decoration: none;
        font-size: 0.9rem;
        box-shadow: var(--shadow-2);
    }
    .btn-gold:hover { filter: brightness(1.1); transform: translateY(-2px); box-shadow: var(--shadow-3); }
    .button-outline {
        background: transparent;
        border: 1px solid var(--border-color);
        color: var(--text-body);
        padding: 12px 28px;
        border-radius: 10px;
        font-weight: 600;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 10px;
        transition: all 0.2s ease;
        text-decoration: none;
        font-size: 0.9rem;
    }
    .button-outline:hover { border-color: var(--gold-primary); color: var(--gold-primary); background: rgba(218,165,32,0.05); }
    @media (max-width: 640px) {
        .input-grid { grid-template-columns: 1fr; }
        .full-width { grid-column: span 1; }
    }
</style>
@endsection

@section('content')
<div class="page-header" style="margin-bottom: 28px;">
    <div class="page-title-group">
        <h1>Edit Party: {{ $customer->name }}</h1>
        <p class="font-urdu" style="margin-top:4px;">گاہک / دوکاندار / کاریگر میں ترمیم</p>
    </div>
</div>

<form action="{{ route('customers.update', $customer) }}" method="POST">
    @csrf
    @method('PUT')
    <div class="form-section">
        <div class="section-header">
            <i class="bi bi-person-gear"></i>
            <h3>Party Details</h3>
        </div>
        <div class="input-grid">
            <div class="form-group">
                <label for="name">Name <span class="font-urdu">نام</span></label>
                <input type="text" name="name" id="name" class="filter-control" value="{{ $customer->name }}" required>
            </div>
            <div class="form-group">
                <label for="party_type">Party Type <span class="font-urdu">قسم</span></label>
                <select name="party_type" id="party_type" required>
                    <option value="customer" {{ $customer->party_type === 'customer' ? 'selected' : '' }}>Customer (گاہک)</option>
                    <option value="dukandar" {{ $customer->party_type === 'dukandar' ? 'selected' : '' }}>Dukandar (دوکاندار)</option>
                    <option value="karigar" {{ $customer->party_type === 'karigar' ? 'selected' : '' }}>Karigar (کاریگر)</option>
                </select>
            </div>
            <div class="form-group">
                <label for="phone">Phone <span class="font-urdu">فون</span></label>
                <input type="text" name="phone" id="phone" class="filter-control" value="{{ $customer->phone }}">
            </div>
            <div class="form-group">
                <label for="cnic">CNIC <span class="font-urdu">شناختی کارڈ</span></label>
                <input type="text" name="cnic" id="cnic" class="filter-control" value="{{ $customer->cnic }}">
            </div>
            <div class="form-group">
                <label for="city">City <span class="font-urdu">شہر</span></label>
                <input type="text" name="city" id="city" class="filter-control" value="{{ $customer->city }}">
            </div>
            <div class="form-group">
                <label for="opening_balance">Opening Balance (grams) <span class="font-urdu">ابتدائی بقایا</span></label>
                <div style="position:relative;">
                    <input type="number" step="0.001" name="opening_balance" id="opening_balance" class="filter-control" value="{{ number_format($customer->opening_balance, 3, '.', '') }}">
                    <span style="position:absolute; right:14px; top:50%; transform:translateY(-50%); color:var(--text-muted); font-size:0.75rem; pointer-events:none;">g</span>
                </div>
            </div>
            <div class="form-group full-width">
                <label for="address">Address <span class="font-urdu">پتہ</span></label>
                <textarea name="address" id="address" class="filter-control" rows="2">{{ $customer->address }}</textarea>
            </div>
            <div class="form-group">
                <label for="status">Status <span class="font-urdu">حیثیت</span></label>
                <select name="status" id="status" required>
                    <option value="active" {{ $customer->status === 'active' ? 'selected' : '' }}>Active</option>
                    <option value="inactive" {{ $customer->status === 'inactive' ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>
        </div>
    </div>

    <div style="display:flex; gap:14px; margin-top:8px;">
        <button type="submit" class="btn-gold">
            <i class="bi bi-save"></i> Update Party
        </button>
        <a href="{{ route('customers.index') }}" class="button-outline">
            <i class="bi bi-arrow-left"></i> Cancel
        </a>
    </div>
</form>
@endsection
