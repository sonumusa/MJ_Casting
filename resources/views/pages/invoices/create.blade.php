@extends('layouts.app')

@section('title', 'New Invoice')

@section('extra_css')
<style>
    .invoice-grid {
        display: grid;
        grid-template-columns: 1fr 400px;
        gap: 32px;
        align-items: start;
    }

    .form-section {
        background-color: var(--bg-card);
        border: 1px solid var(--border-color);
        border-radius: 16px;
        padding: 28px;
        margin-bottom: 28px;
        box-shadow: var(--shadow-1);
        transition: box-shadow 0.2s ease;
    }
    .form-section:hover {
        box-shadow: var(--shadow-2);
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

    .toggle-group {
        display: flex;
        background: var(--bg-app);
        border: 1px solid var(--border-color);
        border-radius: 8px;
        padding: 3px;
    }
    .toggle-btn {
        padding: 4px 12px;
        font-size: 0.7rem;
        border-radius: 6px;
        cursor: pointer;
        border: none;
        background: transparent;
        color: var(--text-muted);
        transition: all 0.2s;
        font-weight: 500;
    }
    .toggle-btn.active {
        background: var(--gold-primary);
        color: white;
        box-shadow: var(--shadow-1);
    }

    .calc-input-wrapper { position: relative; }
    .calc-input-wrapper input { padding-right: 44px; }
    .unit-label {
        position: absolute;
        right: 14px;
        top: 50%;
        transform: translateY(-50%);
        color: var(--text-muted);
        font-size: 0.75rem;
        pointer-events: none;
        font-weight: 500;
    }

    .formula-hint { font-size: 0.7rem; color: var(--text-muted); margin-top: 6px; font-style: italic; }
    .manual-badge {
        font-size: 0.65rem;
        padding: 2px 6px;
        background: rgba(245, 158, 11, 0.12);
        color: var(--warning);
        border: 1px solid var(--warning);
        border-radius: 4px;
        margin-left: 8px;
        font-weight: 600;
    }

    /* Dynamic Receive Rows */
    .receive-row {
        display: grid;
        grid-template-columns: 2fr 1fr 1fr 1fr 48px;
        gap: 12px;
        align-items: end;
        margin-bottom: 12px;
        padding: 14px;
        background: var(--bg-surface);
        border-radius: 12px;
        border: 1px solid var(--border-color);
        transition: all 0.2s ease;
    }
    .receive-row:hover {
        border-color: rgba(218,165,32,0.3);
    }
    .receive-row .form-group { margin-bottom: 0; }
    .receive-row input { font-size: 0.9rem; }

    .btn-add-row {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 10px 18px;
        background: rgba(16,185,129,0.12);
        color: var(--success);
        border: 1px dashed var(--success);
        border-radius: 10px;
        cursor: pointer;
        font-weight: 600;
        font-size: 0.85rem;
        transition: all 0.2s ease;
    }
    .btn-add-row:hover {
        background: rgba(16,185,129,0.2);
        transform: translateY(-1px);
    }
    .btn-remove-row {
        width: 36px; height: 36px;
        display: flex; align-items: center; justify-content: center;
        background: rgba(244,63,94,0.1);
        color: var(--error);
        border: 1px solid rgba(244,63,94,0.2);
        border-radius: 8px;
        cursor: pointer;
        transition: all 0.2s ease;
    }
    .btn-remove-row:hover {
        background: rgba(244,63,94,0.2);
        transform: scale(1.05);
    }

    /* Live Panel */
    .live-panel {
        position: sticky;
        top: 88px;
        background-color: var(--bg-card);
        border: 1px solid var(--gold-primary);
        border-radius: 16px;
        padding: 28px;
        box-shadow: var(--shadow-3);
    }
    .live-panel h4 {
        color: var(--gold-primary);
        margin-bottom: 24px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-family: 'Playfair Display', serif;
        font-size: 1.15rem;
    }
    .live-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 10px 0;
        border-bottom: 1px solid rgba(255,255,255,0.04);
        font-size: 0.88rem;
    }
    .live-row:last-child { border-bottom: none; }
    .live-label { display: flex; align-items: center; gap: 10px; color: var(--text-secondary); font-weight: 500; }
    .live-value { font-family: 'JetBrains Mono', monospace; color: var(--text-primary); font-weight: 600; }
    .live-formula { font-size: 0.68rem; color: var(--text-muted); margin-left: auto; margin-right: 12px; font-style: italic; }

    .row-addition { color: var(--success); }
    .row-subtraction { color: var(--error); }
    .row-multiplication { color: var(--info); }

    .highlight-gold {
        background: rgba(218, 165, 32, 0.08);
        margin: 6px -28px;
        padding: 12px 28px;
        color: var(--gold-bright);
        border-left: 3px solid var(--gold-primary);
        border-right: 3px solid var(--gold-primary);
    }
    .highlight-gold .live-value { color: var(--gold-bright); font-weight: 700; }

    .highlight-blue {
        background: rgba(56, 189, 248, 0.06);
        margin: 6px -28px;
        padding: 12px 28px;
        border-left: 3px solid var(--info);
    }

    .total-box {
        margin-top: 24px;
        padding: 18px;
        border-radius: 12px;
        text-align: center;
        box-shadow: var(--shadow-2);
    }
    .box-grand-total { background: linear-gradient(135deg, #064e3b, #059669); border: 1px solid var(--success); }
    .box-remaining { background: linear-gradient(135deg, #450a0a, #b91c1c); border: 1px solid var(--error); }
    .box-remaining.cleared { background: linear-gradient(135deg, #064e3b, #059669); border: 1px solid var(--success); }

    .total-label { font-size: 0.72rem; text-transform: uppercase; letter-spacing: 0.12em; opacity: 0.9; margin-bottom: 6px; font-weight: 700; }
    .total-value { font-family: 'JetBrains Mono', monospace; font-size: 1.6rem; font-weight: 700; }

    .customer-info-box {
        background: var(--bg-surface);
        border: 1px solid var(--gold-muted);
        padding: 12px 16px;
        border-radius: 10px;
        margin-top: 12px;
        display: none;
    }

    .shimmer {
        position: relative;
        overflow: hidden;
    }
    .shimmer::after {
        content: '';
        position: absolute;
        top: 0; left: -100%; width: 100%; height: 100%;
        background: linear-gradient(90deg, transparent, rgba(218, 165, 32, 0.15), transparent);
        animation: shimmer 3s infinite;
    }
    @keyframes shimmer { 0% { left: -100%; } 100% { left: 100%; } }

    @media (max-width: 1024px) {
        .invoice-grid { grid-template-columns: 1fr; }
        .live-panel { position: static; }
        .receive-row { grid-template-columns: 1fr 1fr 1fr 1fr 48px; }
    }
    @media (max-width: 640px) {
        .receive-row { grid-template-columns: 1fr; }
        .receive-row .btn-remove-row { width: 100%; margin-top: 8px; }
    }
</style>
@endsection

@section('content')
<form action="{{ route('invoices.store') }}" method="POST" id="invoice-form">
    @csrf
    <input type="hidden" name="waste_auto" id="waste_auto" value="1">
    <input type="hidden" name="ratti_auto" id="ratti_auto" value="1">
    <input type="hidden" name="male_waste_auto" id="male_waste_auto" value="1">
    <input type="hidden" name="previous_balance" id="previous_balance" value="0">
    <input type="hidden" name="total_received_khalis" id="total_received_khalis" value="0">

    <div class="invoice-grid">
        <!-- Left Column: Form -->
        <div class="form-column">
            <div class="page-header" style="margin-bottom: 28px;">
                <div class="page-title-group">
                    <h1>New Invoice</h1>
                    <p class="font-urdu" style="margin-top:4px;">نیا بل</p>
                </div>
            </div>

            <!-- Section 1: Invoice Details -->
            <div class="form-section">
                <div class="section-header">
                    <i class="bi bi-info-circle"></i>
                    <h3>Invoice Details</h3>
                </div>
                <div class="input-grid">
                    <div class="form-group full-width">
                        <label for="invoice_type">Invoice Type <span class="font-urdu">بل کی قسم</span></label>
                        <select name="invoice_type" id="invoice_type" required>
                            <option value="customer">Customer Invoice (گاہک بل)</option>
                            <option value="dukandar">Dukandar Invoice (دوکاندار بل)</option>
                            <option value="karigar">Karigar Invoice (کاریگر بل)</option>
                        </select>
                    </div>
                    <div class="form-group full-width">
                        <label for="customer_id">Party / Customer <span class="font-urdu">گاہک / دوکاندار</span></label>
                        <select name="customer_id" id="customer_id" class="filter-control" required>
                            <option value="">Select Party</option>
                            @foreach($customers as $customer)
                                <option value="{{ $customer->id }}" data-type="{{ $customer->party_type }}">{{ $customer->name }} ({{ ucfirst($customer->party_type) }})</option>
                            @endforeach
                        </select>
                        <div id="customer-balance-box" class="customer-info-box">
                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <span style="font-size: 0.8rem; color: var(--text-secondary);">Previous Balance (Sabqa):</span>
                                <span id="customer-current-balance" class="font-mono" style="font-weight: 700; color: var(--gold-bright);">0.000g</span>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="invoice_date">Date <span class="font-urdu">تاریخ</span></label>
                        <input type="date" name="invoice_date" id="invoice_date" class="filter-control" value="{{ date('Y-m-d') }}" required>
                    </div>
                    <div class="form-group">
                        <label for="manual_book_no">Book No <span class="font-urdu">بک نمبر</span></label>
                        <input type="text" name="manual_book_no" id="manual_book_no" class="filter-control" placeholder="Optional">
                    </div>
                </div>
            </div>

            <!-- Section 2: Gold Received from Party (Dynamic Rows) -->
            <div class="form-section">
                <div class="section-header">
                    <i class="bi bi-box-arrow-in-down"></i>
                    <h3>Gold Received from Party <span class="font-urdu">سونا وصول کیا</span></h3>
                </div>
                <div id="receive-rows-container">
                    <!-- Rows injected by JS -->
                </div>
                <button type="button" class="btn-add-row" id="btn-add-receive">
                    <i class="bi bi-plus-lg"></i> Add Receive Row (نئی لائن)
                </button>
                <div style="margin-top: 16px; padding-top: 16px; border-top: 1px dashed var(--border-color);">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <span style="font-size: 0.85rem; color: var(--text-secondary); font-weight: 600;">Total Received Khalis:</span>
                        <span class="font-mono" id="display-total-received" style="font-size: 1.1rem; color: var(--success); font-weight: 700;">0.000g</span>
                    </div>
                </div>
            </div>

            <!-- Section 3: Gold Calculation (Casting / Work) -->
            <div class="form-section">
                <div class="section-header">
                    <i class="bi bi-calculator"></i>
                    <h3>Gold Calculation <span class="font-urdu">ذہبی حساب</span></h3>
                </div>
                <div class="input-grid">
                    <div class="form-group">
                        <label for="casting_weight">Casting Weight <span class="font-urdu">کاسٹنگ وزن</span></label>
                        <div class="calc-input-wrapper">
                            <input type="number" step="0.001" name="casting_weight" id="casting_weight" class="filter-control" placeholder="0.000" required>
                            <span class="unit-label">g</span>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>
                            <span>Waste Weight <span class="font-urdu">ویسٹ وزن</span> <span id="waste-manual-badge" class="manual-badge" style="display:none">Manual</span></span>
                            <div class="toggle-group">
                                <button type="button" class="toggle-btn active" data-target="waste_weight" data-mode="auto">Auto</button>
                                <button type="button" class="toggle-btn" data-target="waste_weight" data-mode="manual">Manual</button>
                            </div>
                        </label>
                        <div class="calc-input-wrapper">
                            <input type="number" step="0.001" name="waste_weight" id="waste_weight" class="filter-control" style="background-color: var(--bg-app)" readonly required>
                            <span class="unit-label">g</span>
                        </div>
                        <div id="waste-hint" class="formula-hint">Formula: Casting / 10 × Ratti Rate</div>
                    </div>
                    <div class="form-group">
                        <label for="total_weight">Total Weight <span class="font-urdu">کل وزن</span></label>
                        <div class="calc-input-wrapper">
                            <input type="number" step="0.001" id="total_weight_display" class="filter-control" style="background-color: var(--bg-app)" readonly>
                            <span class="unit-label">g</span>
                        </div>
                    </div>
                    <div class="form-group">
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                            <div>
                                <label>
                                    <span>Ratti <span class="font-urdu">رتی</span> <span id="ratti-manual-badge" class="manual-badge" style="display:none">Manual</span></span>
                                    <div class="toggle-group" style="margin-left: 8px;">
                                        <button type="button" class="toggle-btn active" data-target="ratti" data-mode="auto">Auto</button>
                                        <button type="button" class="toggle-btn" data-target="ratti" data-mode="manual">Manual</button>
                                    </div>
                                </label>
                                <input type="number" step="0.01" name="ratti" id="ratti" class="filter-control" style="background-color: var(--bg-app)" readonly>
                            </div>
                            <div>
                                <label for="ratti_rate">Ratti Deduction Rate (g) <span class="font-urdu">رتی کٹوتی شرح</span></label>
                                <input type="number" step="0.001" name="ratti_rate" id="ratti_rate" class="filter-control" value="{{ $calcSettings['default_ratti_rate'] }}" placeholder="0.000">
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>
                            <span>Male Waste <span class="font-urdu">میل ویسٹ</span> <span id="male-manual-badge" class="manual-badge" style="display:none">Manual</span></span>
                            <div class="toggle-group">
                                <button type="button" class="toggle-btn active" data-target="male_waste" data-mode="auto">Auto</button>
                                <button type="button" class="toggle-btn" data-target="male_waste" data-mode="manual">Manual</button>
                            </div>
                        </label>
                        <div class="calc-input-wrapper">
                            <input type="number" step="0.001" name="male_waste" id="male_waste" class="filter-control" style="background-color: var(--bg-app)" readonly required>
                            <span class="unit-label">g</span>
                        </div>
                        <div id="male-hint" class="formula-hint">Formula: Total / 96 × Ratti</div>
                    </div>
                    <div class="form-group">
                        <label for="gold_khalis">Gold Khalis <span class="font-urdu">خالص سونا</span></label>
                        <div class="calc-input-wrapper">
                            <input type="number" step="0.001" id="gold_khalis_display" class="filter-control" style="background-color: var(--bg-app)" readonly>
                            <span class="unit-label">g</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Section 4: Mazdori -->
            <div class="form-section">
                <div class="section-header">
                    <i class="bi bi-hammer"></i>
                    <h3>Mazdori <span class="font-urdu">مزدوری</span></h3>
                </div>
                <div class="input-grid">
                    <div class="form-group">
                        <label>RP Mazdori <span class="font-urdu">آر پی مزدوری</span></label>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                            <div class="calc-input-wrapper">
                                <input type="number" step="0.001" name="rp_mazdori_weight" id="rp_mazdori_weight" class="filter-control" placeholder="Weight">
                                <span class="unit-label">g</span>
                            </div>
                            <div class="calc-input-wrapper">
                                <input type="number" step="0.01" name="rp_mazdori_rate" id="rp_mazdori_rate" class="filter-control" placeholder="Rate">
                                <span class="unit-label">Rs</span>
                            </div>
                        </div>
                        <div id="rp-mazdori-amount-display" style="margin-top: 10px; font-size: 0.78rem; color: var(--info); font-weight: 500;">
                            Amount*: Rs. 0.00
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Casting Mazdori <span class="font-urdu">کاسٹنگ مزدوری</span></label>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                            <div class="calc-input-wrapper">
                                <input type="number" step="0.001" name="casting_mazdori_weight" id="casting_mazdori_weight" class="filter-control" placeholder="Weight">
                                <span class="unit-label">g</span>
                            </div>
                            <div class="calc-input-wrapper">
                                <input type="number" step="0.01" name="casting_mazdori_rate" id="casting_mazdori_rate" class="filter-control" placeholder="Rate">
                                <span class="unit-label">Rs</span>
                            </div>
                        </div>
                        <div id="casting-mazdori-amount-display" style="margin-top: 10px; font-size: 0.78rem; color: var(--info); font-weight: 500;">
                            Amount*: Rs. 0.00
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="rp_rate">RP Rate (Rs. per gram) <span class="font-urdu">ریٹ فی گرام</span></label>
                        <input type="number" step="0.01" name="rp_rate" id="rp_rate" class="filter-control" value="{{ \App\Models\Setting::getSetting('default_rp_rate', 0) }}" required>
                    </div>
                    <div class="form-group">
                        <label for="wasooli">
                            <span>Wasooli <span class="font-urdu">وصولی</span></span>
                            <button type="button" id="apply-balance-btn" class="button-outline" style="padding: 4px 10px; font-size: 0.7rem;">
                                Apply Previous Balance <span class="font-urdu">سابقہ بقایا لگائیں</span>
                            </button>
                        </label>
                        <input type="number" step="0.001" name="wasooli" id="wasooli" class="filter-control" placeholder="0.000">
                    </div>
                    <div class="form-group full-width">
                        <label for="remarks">Remarks <span class="font-urdu">ریمارکس</span></label>
                        <textarea name="remarks" id="remarks" class="filter-control" rows="2" placeholder="Any additional notes..."></textarea>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Column: Live Panel -->
        <div class="sidebar-column">
            <div class="live-panel">
                <h4>
                    <span>Live Calculation <span class="font-urdu">حساب کتاب</span></span>
                    <i class="bi bi-lightning-charge-fill"></i>
                </h4>

                <div style="font-size: 0.78rem; color: var(--text-muted); margin-bottom: 14px; padding-bottom: 10px; border-bottom: 1px solid rgba(255,255,255,0.05);">
                    <i class="bi bi-arrow-down-circle-fill" style="color: var(--success);"></i> Gold Received
                </div>
                <div class="live-row highlight-gold" id="received-khalis-shimmer">
                    <span class="live-label"><i class="bi bi-box-arrow-in-down"></i> Total Received Khalis:</span>
                    <span class="live-formula">Σ(Weight - Weight/96×Ratti)</span>
                    <span class="live-value" id="live-total-received">0.000g</span>
                </div>

                <div style="font-size: 0.78rem; color: var(--text-muted); margin: 18px 0 14px; padding-top: 14px; border-top: 1px solid rgba(255,255,255,0.05);">
                    <i class="bi bi-arrow-up-circle-fill" style="color: var(--info);"></i> Casting / Work Output
                </div>

                <div class="live-row">
                    <span class="live-label">Casting Weight:</span>
                    <span class="live-value" id="live-casting">0.000g</span>
                </div>
                <div class="live-row">
                    <span class="live-label"><i class="bi bi-plus-circle row-addition"></i> Waste Weight:</span>
                    <span class="live-formula" id="formula-waste">[Casting × {{ $calcSettings['default_waste_rate'] }}]</span>
                    <span class="live-value" id="live-waste">0.000g</span>
                </div>
                <div class="live-row" style="border-top: 1px solid var(--border-color); margin-top: 5px; padding-top: 10px;">
                    <span class="live-label">= Total Weight:</span>
                    <span class="live-formula">[ROUND(Casting + Waste, 3)]</span>
                    <span class="live-value" id="live-total-weight">0.000g</span>
                </div>

                <div class="sidebar-divider" style="margin: 10px 0;"></div>

                <div class="live-row">
                    <span class="live-label">Auto Ratti:</span>
                    <span class="live-formula" id="live-ratti-tier">[-]</span>
                    <span class="live-value" id="live-ratti">0.0</span>
                </div>
                <div class="live-row">
                    <span class="live-label">× Ratti Rate:</span>
                    <span class="live-value" id="live-ratti-rate">0.000g</span>
                </div>
                <div class="live-row">
                    <span class="live-label"><i class="bi bi-dash-circle row-subtraction"></i> Male Waste:</span>
                    <span class="live-formula">[Total / 96 × Ratti]</span>
                    <span class="live-value" id="live-male-waste">0.000g</span>
                </div>

                <div class="sidebar-divider" style="margin: 10px 0;"></div>

                <div class="live-row highlight-gold" id="gold-khalis-shimmer">
                    <span class="live-label"><i class="bi bi-star-fill"></i> Gold Khalis:</span>
                    <span class="live-formula">[Total - Male Waste]</span>
                    <span class="live-value" id="val-gold-khalis">0.000g</span>
                </div>

                <div class="live-row">
                    <span class="live-label"><i class="bi bi-plus-circle row-addition"></i> RP Mazdori:</span>
                    <span class="live-value" id="live-rp-maz">0.000g</span>
                </div>
                <div class="live-row">
                    <span class="live-label"><i class="bi bi-plus-circle row-addition"></i> Cast Mazdori:</span>
                    <span class="live-value" id="live-cast-maz">0.000g</span>
                </div>
                <div class="live-row highlight-blue">
                    <span class="live-label"><i class="bi bi-gem"></i> Effective Gold:</span>
                    <span class="live-formula">[Khalis + Mazdori]</span>
                    <span class="live-value" id="val-effective-gold">0.000g</span>
                </div>

                <div class="total-box box-grand-total">
                    <div class="total-label">TOTAL GOLD OUT</div>
                    <div class="total-value" id="val-total-gold-out">0.000g</div>
                </div>

                <div class="sidebar-divider" style="margin: 18px 0;"></div>

                <div class="live-row">
                    <span class="live-label">Previous Balance:</span>
                    <span class="live-value" id="val-prev-balance">0.000g</span>
                </div>
                <div class="live-row">
                    <span class="live-label">+ This Invoice:</span>
                    <span class="live-value" id="val-this-invoice">0.000g</span>
                </div>
                <div class="live-row">
                    <span class="live-label">- Received Khalis:</span>
                    <span class="live-value" id="live-received-deduct" style="color: var(--success);">0.000g</span>
                </div>
                <div class="live-row">
                    <span class="live-label">- Wasooli:</span>
                    <span class="live-value" id="val-wasooli">0.000g</span>
                </div>

                <div class="total-box box-remaining" id="box-remaining">
                    <div class="total-label">REMAINING</div>
                    <div class="total-value" id="val-remaining-balance">0.000g</div>
                </div>

                <div style="margin-top: 18px; font-size: 0.7rem; color: var(--text-muted); text-align: center; font-style: italic; line-height: 1.5;">
                    * Amounts marked with asterisk are for reference only and do not affect the Grand Total calculation.
                </div>

                <div style="margin-top: 28px; display: grid; gap: 14px;">
                    <button type="submit" name="action" value="save" class="btn-gold ripple" style="justify-content: center; padding: 12px;">
                        <i class="bi bi-save"></i> Save Invoice
                    </button>
                    <button type="submit" name="action" value="print" class="button-outline" style="justify-content: center; padding: 12px;">
                        <i class="bi bi-printer"></i> Save & Print
                    </button>
                </div>
            </div>
        </div>
    </div>
</form>
@endsection

@section('extra_js')
<script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js"></script>
<script>
    // Calculation Settings from PHP
    const CALC_SETTINGS = @json($calcSettings);

    // Conversion formula: gross - (gross / 96 * ratti)
    function convertToKhalis(gross, ratti) {
        const g = parseFloat(gross || 0);
        const r = parseFloat(ratti || 0);
        if (g <= 0) return 0;
        return Math.round((g - ((g / 96) * r)) * 1000) / 1000;
    }

    let receiveRowIndex = 0;

    function addReceiveRow(data = null) {
        const idx = receiveRowIndex++;
        const container = document.getElementById('receive-rows-container');
        const row = document.createElement('div');
        row.className = 'receive-row animate-up';
        row.dataset.index = idx;
        row.innerHTML = `
            <div class="form-group">
                <label style="font-size:0.7rem; text-transform:none; letter-spacing:0;">Description <span class="font-urdu">تفصیل</span></label>
                <input type="text" name="receives[${idx}][description]" class="filter-control" placeholder="e.g. Old Chain" value="${data?.description || ''}">
            </div>
            <div class="form-group">
                <label style="font-size:0.7rem; text-transform:none; letter-spacing:0;">Gross Wt <span class="font-urdu">کچا وزن</span></label>
                <div class="calc-input-wrapper">
                    <input type="number" step="0.001" name="receives[${idx}][gross_weight]" class="filter-control receive-gross" placeholder="0.000" value="${data?.gross_weight || ''}">
                    <span class="unit-label">g</span>
                </div>
            </div>
            <div class="form-group">
                <label style="font-size:0.7rem; text-transform:none; letter-spacing:0;">Ratti Imp <span class="font-urdu">رتی نقص</span></label>
                <div class="calc-input-wrapper">
                    <input type="number" step="0.01" name="receives[${idx}][ratti_impurity]" class="filter-control receive-ratti" placeholder="0" value="${data?.ratti_impurity || ''}">
                    <span class="unit-label">r</span>
                </div>
            </div>
            <div class="form-group">
                <label style="font-size:0.7rem; text-transform:none; letter-spacing:0;">Khalis <span class="font-urdu">خالص</span></label>
                <div class="calc-input-wrapper">
                    <input type="number" step="0.001" name="receives[${idx}][khalis_weight]" class="filter-control receive-khalis" placeholder="0.000" value="${data?.khalis_weight || ''}" readonly style="background: var(--bg-app); color: var(--gold-bright); font-weight: 700;">
                    <span class="unit-label">g</span>
                </div>
            </div>
            <button type="button" class="btn-remove-row" onclick="removeReceiveRow(this)" title="Remove">
                <i class="bi bi-trash3"></i>
            </button>
        `;
        container.appendChild(row);

        // Attach listeners
        const grossInput = row.querySelector('.receive-gross');
        const rattiInput = row.querySelector('.receive-ratti');
        const khalisInput = row.querySelector('.receive-khalis');

        const updateRow = () => {
            const k = convertToKhalis(grossInput.value, rattiInput.value);
            khalisInput.value = k.toFixed(3);
            calculate();
        };

        grossInput.addEventListener('input', updateRow);
        rattiInput.addEventListener('input', updateRow);
    }

    function removeReceiveRow(btn) {
        const row = btn.closest('.receive-row');
        gsap.to(row, {
            opacity: 0, height: 0, marginBottom: 0, padding: 0, duration: 0.3,
            onComplete: () => { row.remove(); calculate(); }
        });
    }

    document.getElementById('btn-add-receive').addEventListener('click', () => addReceiveRow());

    document.addEventListener('DOMContentLoaded', () => {
        const form = document.getElementById('invoice-form');
        const inputs = form.querySelectorAll('input, select, textarea');

        // Add initial receive row
        addReceiveRow();

        // Manual/Auto Toggle
        const toggleBtns = document.querySelectorAll('.toggle-btn');
        toggleBtns.forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                const targetId = btn.dataset.target;
                const mode = btn.dataset.mode;
                const targetInput = document.getElementById(targetId);
                const autoInput = document.getElementById(targetId + '_auto');
                const badge = document.getElementById(targetId.replace('_', '-') + '-manual-badge');

                btn.parentElement.querySelectorAll('.toggle-btn').forEach(b => b.classList.remove('active'));
                btn.classList.add('active');

                if (mode === 'manual') {
                    targetInput.readOnly = false;
                    targetInput.style.backgroundColor = 'var(--bg-surface)';
                    targetInput.style.boxShadow = '0 0 10px rgba(245, 158, 11, 0.2)';
                    if (badge) badge.style.display = 'inline-block';
                    autoInput.value = "0";
                } else {
                    targetInput.readOnly = true;
                    targetInput.style.backgroundColor = 'var(--bg-app)';
                    targetInput.style.boxShadow = 'none';
                    if (badge) badge.style.display = 'none';
                    autoInput.value = "1";
                    calculate();
                }
            });
        });

        // Apply Balance Button
        document.getElementById('apply-balance-btn').addEventListener('click', () => {
            const balance = parseFloat(document.getElementById('previous_balance').value || 0);
            document.getElementById('wasooli').value = balance.toFixed(3);
            calculate();
        });

        // Customer Selection
        const customerSelect = document.getElementById('customer_id');
        customerSelect.addEventListener('change', async () => {
            const customerId = customerSelect.value;
            const box = document.getElementById('customer-balance-box');
            const balanceText = document.getElementById('customer-current-balance');
            const prevBalanceInput = document.getElementById('previous_balance');

            if (customerId) {
                try {
                    const response = await fetch(`/customers/${customerId}/last-balance`);
                    const data = await response.json();
                    const balance = parseFloat(data.balance || 0);

                    prevBalanceInput.value = balance;
                    box.style.display = 'block';
                    balanceText.innerText = balance.toFixed(3) + 'g';
                    gsap.from(box, { opacity: 0, y: -10, duration: 0.3 });
                } catch (error) {
                    console.error('Error fetching balance:', error);
                }
            } else {
                box.style.display = 'none';
                prevBalanceInput.value = "0";
            }
            calculate();
        });

        // Main Calculation Engine
        function calculate() {
            const input = {
                casting_weight: document.getElementById('casting_weight').value,
                waste_weight: document.getElementById('waste_weight').value,
                waste_auto: document.getElementById('waste_auto').value === "1",
                ratti: document.getElementById('ratti').value,
                ratti_auto: document.getElementById('ratti_auto').value === "1",
                ratti_rate: document.getElementById('ratti_rate').value,
                male_waste: document.getElementById('male_waste').value,
                male_waste_auto: document.getElementById('male_waste_auto').value === "1",
                rp_mazdori_weight: document.getElementById('rp_mazdori_weight').value,
                rp_mazdori_rate: document.getElementById('rp_mazdori_rate').value,
                casting_mazdori_weight: document.getElementById('casting_mazdori_weight').value,
                casting_mazdori_rate: document.getElementById('casting_mazdori_rate').value,
                rp_rate: document.getElementById('rp_rate').value,
                wasooli: document.getElementById('wasooli').value,
                previous_balance: document.getElementById('previous_balance').value,
            };

            // Calculate total received khalis
            let totalReceivedKhalis = 0;
            document.querySelectorAll('.receive-khalis').forEach(el => {
                totalReceivedKhalis += parseFloat(el.value || 0);
            });
            totalReceivedKhalis = Math.round(totalReceivedKhalis * 1000) / 1000;
            input.total_received_khalis = totalReceivedKhalis;
            document.getElementById('total_received_khalis').value = totalReceivedKhalis;
            document.getElementById('display-total-received').innerText = totalReceivedKhalis.toFixed(3) + 'g';

            const results = GoldCalc.calculateAll(input, CALC_SETTINGS);

            // Update form fields
            if (input.waste_auto) document.getElementById('waste_weight').value = results.waste_weight.toFixed(3);
            if (input.ratti_auto) document.getElementById('ratti').value = results.ratti.toFixed(2);
            if (input.male_waste_auto) document.getElementById('male_waste').value = results.male_waste.toFixed(3);

            document.getElementById('total_weight_display').value = results.total_weight.toFixed(3);
            document.getElementById('gold_khalis_display').value = results.gold_khalis.toFixed(3);

            // Update Live Panel
            document.getElementById('live-casting').innerText = parseFloat(input.casting_weight || 0).toFixed(3) + 'g';
            document.getElementById('live-waste').innerText = results.waste_weight.toFixed(3) + 'g';
            document.getElementById('live-total-weight').innerText = results.total_weight.toFixed(3) + 'g';

            document.getElementById('live-ratti').innerText = results.ratti.toFixed(2);
            document.getElementById('live-ratti-tier').innerText = results.ratti_tier_applied ? `[${results.ratti_tier_applied}]` : '[-]';
            document.getElementById('live-ratti-rate').innerText = parseFloat(input.ratti_rate || 0).toFixed(3) + 'g';
            document.getElementById('live-male-waste').innerText = results.male_waste.toFixed(3) + 'g';

            document.getElementById('val-gold-khalis').innerText = results.gold_khalis.toFixed(3) + 'g';

            const rpMazWeight = parseFloat(input.rp_mazdori_weight || 0);
            const castMazWeight = parseFloat(input.casting_mazdori_weight || 0);
            document.getElementById('live-rp-maz').innerText = rpMazWeight.toFixed(3) + 'g';
            document.getElementById('live-cast-maz').innerText = castMazWeight.toFixed(3) + 'g';

            document.getElementById('val-effective-gold').innerText = results.effective_gold.toFixed(3) + 'g';
            document.getElementById('val-total-gold-out').innerText = results.effective_gold.toFixed(3) + 'g';

            document.getElementById('val-prev-balance').innerText = parseFloat(input.previous_balance || 0).toFixed(3) + 'g';
            document.getElementById('val-this-invoice').innerText = results.effective_gold.toFixed(3) + 'g';
            document.getElementById('live-received-deduct').innerText = totalReceivedKhalis.toFixed(3) + 'g';
            document.getElementById('val-wasooli').innerText = parseFloat(input.wasooli || 0).toFixed(3) + 'g';

            const remainingVal = document.getElementById('val-remaining-balance');
            const remainingBox = document.getElementById('box-remaining');
            remainingVal.innerText = results.remaining_balance.toFixed(3) + 'g';

            if (results.remaining_balance <= 0) {
                remainingBox.classList.add('cleared');
                remainingVal.style.color = 'white';
            } else {
                remainingBox.classList.remove('cleared');
            }

            // Amounts
            document.getElementById('rp-mazdori-amount-display').innerText = 'Amount*: Rs. ' + results.rp_mazdori_amount.toLocaleString();
            document.getElementById('casting-mazdori-amount-display').innerText = 'Amount*: Rs. ' + results.casting_mazdori_amount.toLocaleString();

            // Shimmer effects
            const shimmer = document.getElementById('gold-khalis-shimmer');
            if (results.gold_khalis > 0) shimmer.classList.add('shimmer');
            else shimmer.classList.remove('shimmer');

            const recvShimmer = document.getElementById('received-khalis-shimmer');
            if (totalReceivedKhalis > 0) recvShimmer.classList.add('shimmer');
            else recvShimmer.classList.remove('shimmer');
        }

        inputs.forEach(input => {
            input.addEventListener('input', calculate);
        });

        calculate();
    });
</script>
@endsection
