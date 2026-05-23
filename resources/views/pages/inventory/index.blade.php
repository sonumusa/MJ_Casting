@extends('layouts.app')

@section('title', 'Inventory')

@section('extra_css')
<style>
    .page-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 28px; }
    .page-title-group h1 { font-size: 1.9rem; color: var(--text-primary); margin: 0; font-weight: 700; }
    .page-title-group p { font-size: 1.05rem; color: var(--gold-muted); margin: 0; }

    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
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
    .stat-card:hover { transform: translateY(-3px); box-shadow: var(--shadow-3); }
    .stat-card::before {
        content: '';
        position: absolute;
        top: 0; left: 0; right: 0;
        height: 3px;
        background: linear-gradient(90deg, var(--gold-deep), var(--gold-primary));
        border-radius: 16px 16px 0 0;
    }
    .stat-icon {
        width: 44px; height: 44px; border-radius: 12px;
        display: flex; align-items: center; justify-content: center;
        font-size: 1.3rem; margin-bottom: 16px;
        background: rgba(218,165,32,0.08); color: var(--gold-primary);
        border: 1px solid rgba(218,165,32,0.15);
    }
    .stat-icon.green { background: rgba(16,185,129,0.08); color: var(--success); border-color: rgba(16,185,129,0.15); }
    .stat-icon.red { background: rgba(244,63,94,0.08); color: var(--error); border-color: rgba(244,63,94,0.15); }
    .stat-icon.blue { background: rgba(56,189,248,0.08); color: var(--info); border-color: rgba(56,189,248,0.15); }
    .stat-label { font-size: 0.72rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.08em; margin-bottom: 10px; font-weight: 700; }
    .stat-value { font-family: 'JetBrains Mono', monospace; font-size: 1.6rem; font-weight: 700; color: var(--text-primary); }
    .stat-sub { font-size: 0.78rem; color: var(--text-secondary); margin-top: 6px; }

    .form-section {
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
        width: 80px; height: 2px;
        background: var(--gold-primary); border-radius: 2px;
    }
    .section-header i { color: var(--gold-primary); font-size: 1.35rem; }
    .section-header h3 { font-family: 'Playfair Display', serif; font-size: 1.15rem; color: var(--text-primary); margin: 0; }
    .form-group label {
        display: flex; justify-content: space-between; align-items: center;
        font-size: 0.75rem; color: var(--text-secondary); margin-bottom: 8px;
        text-transform: uppercase; letter-spacing: 0.06em; font-weight: 600;
    }
    .input-grid {
        display: grid; grid-template-columns: repeat(2, 1fr); gap: 22px;
    }
    @media (max-width: 640px) { .input-grid { grid-template-columns: 1fr; } }

    .btn-gold {
        background: linear-gradient(135deg, var(--gold-deep) 0%, var(--gold-primary) 100%);
        color: white; border: none; padding: 12px 28px; border-radius: 10px;
        font-weight: 700; cursor: pointer; display: inline-flex;
        align-items: center; gap: 10px; transition: all 0.2s ease;
        text-decoration: none; font-size: 0.9rem; box-shadow: var(--shadow-2);
    }
    .btn-gold:hover { filter: brightness(1.1); transform: translateY(-2px); box-shadow: var(--shadow-3); }
    .formula-display {
        font-size: 0.78rem; color: var(--text-muted); font-style: italic;
        background: rgba(255,255,255,0.02); padding: 10px 14px; border-radius: 8px;
        margin-top: 14px; border: 1px dashed var(--border-color);
    }
</style>
@endsection

@section('content')
<div class="page-header">
    <div class="page-title-group">
        <h1>Inventory</h1>
        <p class="font-urdu">سونے کا اسٹاک</p>
    </div>
</div>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon"><i class="bi bi-box-seam"></i></div>
        <div class="stat-label">Opening Balance <span class="font-urdu">ابتدائی بیلنس</span></div>
        <div class="stat-value" style="color: var(--gold-bright);">{{ number_format($inventory->opening_balance ?? 0, 3) }} <span style="font-size:0.9rem;">g</span></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon green"><i class="bi bi-box-arrow-in-down"></i></div>
        <div class="stat-label">Total Received <span class="font-urdu">کل وصول</span></div>
        <div class="stat-value" style="color: var(--success);">{{ number_format($receivedWeight ?? 0, 3) }} <span style="font-size:0.9rem;">g</span></div>
        <div class="stat-sub">From invoices + receipts</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon red"><i class="bi bi-box-arrow-up"></i></div>
        <div class="stat-label">Total Given <span class="font-urdu">کل دیا</span></div>
        <div class="stat-value" style="color: var(--error);">{{ number_format($givenWeight ?? 0, 3) }} <span style="font-size:0.9rem;">g</span></div>
        <div class="stat-sub">From active invoices (Effective Gold)</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon blue"><i class="bi bi-bank"></i></div>
        <div class="stat-label">Closing Balance <span class="font-urdu">اختتامی بیلنس</span></div>
        <div class="stat-value" style="color: var(--info);">{{ number_format($closingBalance ?? 0, 3) }} <span style="font-size:0.9rem;">g</span></div>
        <div class="stat-sub">Opening + Received - Given</div>
    </div>
</div>

<form action="{{ route('inventory.update') }}" method="POST">
    @csrf
    <div class="form-section">
        <div class="section-header">
            <i class="bi bi-sliders"></i>
            <h3>Update Inventory</h3>
        </div>
        <div class="input-grid">
            <div class="form-group">
                <label for="opening_balance">Opening Balance (grams) <span class="font-urdu">ابتدائی بیلنس</span></label>
                <div style="position:relative;">
                    <input type="number" step="0.001" name="opening_balance" id="opening_balance" class="filter-control" value="{{ number_format($inventory->opening_balance ?? 0, 3, '.', '') }}" required>
                    <span style="position:absolute; right:14px; top:50%; transform:translateY(-50%); color:var(--text-muted); font-size:0.75rem; pointer-events:none;">g</span>
                </div>
            </div>
            <div class="form-group">
                <label for="period_label">Period Label <span class="font-urdu">مدہ</span></label>
                <input type="text" name="period_label" id="period_label" class="filter-control" value="{{ $inventory->period_label }}" placeholder="e.g. Ramadan 2026">
            </div>
        </div>
        <div class="formula-display">
            <strong>Formula:</strong> Closing = Opening ({{ number_format($inventory->opening_balance ?? 0, 3) }} g) + Received ({{ number_format($receivedWeight ?? 0, 3) }} g) - Given ({{ number_format($givenWeight ?? 0, 3) }} g) = <strong>{{ number_format($closingBalance ?? 0, 3) }} g</strong>
        </div>
        <div style="margin-top: 22px;">
            <button type="submit" class="btn-gold">
                <i class="bi bi-save"></i> Update Inventory
            </button>
        </div>
    </div>
</form>
@endsection
