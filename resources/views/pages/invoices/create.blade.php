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
    }

    .btn-gold {
        background: linear-gradient(135deg, #B8860B 0%, #DAA520 100%) !important;
        color: white !important;
        border: none;
        padding: 12px 28px;
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
        color: white;
    }
</style>
@endsection

@section('content')
<form action="{{ route('invoices.store') }}" method="POST" id="invoice-form">
    @csrf
    <input type="hidden" name="previous_balance" id="previous_balance" value="0">
    <input type="hidden" name="total_received_khalis" id="total_received_khalis" value="0">
    <input type="hidden" name="waste_auto" id="waste_auto" value="1">
    <input type="hidden" name="ratti_auto" id="ratti_auto" value="1">
    <input type="hidden" name="male_waste_auto" id="male_waste_auto" value="1">

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
                        <label for="customer_id">Party <span class="font-urdu">گاہک / پارٹی</span></label>
                        <select name="customer_id" id="customer_id" class="filter-control" required>
                            <option value="">Select Party</option>
                            @foreach($customers as $customer)
                                <option value="{{ $customer->id }}" data-type="{{ $customer->party_type }}">{{ $customer->name }} ({{ ucfirst($customer->party_type) }})</option>
                            @endforeach
                        </select>
                        <div id="customer-balance-box" class="customer-info-box">
                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <span style="font-size: 0.8rem; color: var(--text-secondary);">To Date Opening / Previous Balance (سابقہ بقایا):</span>
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

            <!-- Section 2: Mazdori / Labor -->
            <div class="form-section">
                <div class="section-header">
                    <i class="bi bi-hammer"></i>
                    <h3>Mazdori / Labor <span class="font-urdu">مزدوری</span></h3>
                </div>
                <div class="input-grid">
                    <div class="form-group">
                        <label for="rp_mazdori_weight">RP Mazdori Gold Weight <span class="font-urdu">آر پی مزدوری وزن</span></label>
                        <div class="calc-input-wrapper">
                            <input type="number" step="0.001" name="rp_mazdori_weight" id="rp_mazdori_weight" class="filter-control" placeholder="0.000" value="0">
                            <span class="unit-label">g</span>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="rp_mazdori_rate">RP Mazdori Rate <span class="font-urdu">آر پی مزدوری ریٹ</span></label>
                        <div class="calc-input-wrapper">
                            <input type="number" step="0.01" name="rp_mazdori_rate" id="rp_mazdori_rate" class="filter-control" placeholder="0.00" value="5000">
                            <span class="unit-label">Rs/g</span>
                        </div>
                        <div class="formula-hint">RP Mazdori Weight × Rate = RP Mazdori Amount</div>
                    </div>

                    <div class="form-group">
                        <label for="casting_mazdori_weight">Casting Mazdori Weight <span class="font-urdu">کاسٹنگ مزدوری وزن</span></label>
                        <div class="calc-input-wrapper">
                            <input type="number" step="0.001" name="casting_mazdori_weight" id="casting_mazdori_weight" class="filter-control" placeholder="0.000" value="0">
                            <span class="unit-label">g</span>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="casting_mazdori_rate">Casting Mazdori Rate <span class="font-urdu">کاسٹنگ مزدوری ریٹ</span></label>
                        <div class="calc-input-wrapper">
                            <input type="number" step="0.01" name="casting_mazdori_rate" id="casting_mazdori_rate" class="filter-control" placeholder="0.00" value="5000">
                            <span class="unit-label">Rs/g</span>
                        </div>
                        <div class="formula-hint">Casting Mazdori Weight × Rate = Casting Mazdori Amount</div>
                    </div>

                    <div class="form-group">
                        <label for="rp_rate">Current Gold Rate (Rs per gram) <span class="font-urdu">موجودہ سونے کا ریٹ (روپے فی گرام)</span></label>
                        <input type="number" step="0.01" name="rp_rate" id="rp_rate" class="filter-control" value="{{ \App\Models\Setting::getSetting('default_rp_rate', 8500) }}" required>
                        <small class="formula-hint">Used for converting gold value to monetary total.</small>
                    </div>

                    <div class="form-group full-width">
                        <label for="remarks">Remarks <span class="font-urdu">ریمارکس</span></label>
                        <textarea name="remarks" id="remarks" class="filter-control" rows="3" placeholder="Any additional notes..."></textarea>
                    </div>
                </div>
            </div>

            <!-- Section 3: Gold Calculation -->
            <div class="form-section">
                <div class="section-header">
                    <i class="bi bi-calculator"></i>
                    <h3>Gold Calculation <span class="font-urdu">ذہبی حساب</span></h3>
                </div>
                <div class="input-grid">
                    <input type="hidden" name="waste_auto" id="waste_auto" value="1">
                    <input type="hidden" name="ratti_auto" id="ratti_auto" value="1">
                    <input type="hidden" name="male_waste_auto" id="male_waste_auto" value="1">

                    <div class="form-group">
                        <label for="casting_weight">Casting Weight <span class="font-urdu">کاسٹنگ وزن</span></label>
                        <div class="calc-input-wrapper">
                            <input type="number" step="0.001" name="casting_weight" id="casting_weight" class="filter-control" placeholder="0.000" required>
                            <span class="unit-label">g</span>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="ratti">Ratti <span class="font-urdu">رتی</span></label>
                        <input type="number" step="0.01" name="ratti" id="ratti" class="filter-control" placeholder="0.00" required>
                    </div>

                    <div class="form-group">
                        <label for="ratti_rate">Ratti Rate <span class="font-urdu">رتی ریٹ</span></label>
                        <div class="calc-input-wrapper">
                            <input type="number" step="0.001" name="ratti_rate" id="ratti_rate" class="filter-control" placeholder="0.000" value="{{ \App\Models\Setting::getSetting('default_ratti_rate', 0) }}">
                            <span class="unit-label">g</span>
                        </div>
                        <div class="formula-hint">Formula: Casting / 10 × Ratti Rate</div>
                    </div>

                    <div class="form-group">
                        <label for="waste_weight">Waste Weight <span class="font-urdu">ویسٹ وزن</span></label>
                        <div class="calc-input-wrapper">
                            <input type="number" step="0.001" name="waste_weight" id="waste_weight" class="filter-control" placeholder="0.000" readonly>
                            <span class="unit-label">g</span>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="male_waste">Male Waste <span class="font-urdu">میل ویسٹ</span></label>
                        <div class="calc-input-wrapper">
                            <input type="number" step="0.001" name="male_waste" id="male_waste" class="filter-control" placeholder="0.000" readonly>
                            <span class="unit-label">g</span>
                        </div>
                        <div class="formula-hint">Formula: Total Weight / 96 × Ratti</div>
                    </div>

                    <div class="form-group full-width">
                        <label>Total Weight <span class="font-urdu">کل وزن</span></label>
                        <div class="calc-input-wrapper">
                            <input type="number" step="0.001" name="total_weight" id="total_weight" class="filter-control" placeholder="0.000" readonly>
                            <span class="unit-label">g</span>
                        </div>
                    </div>

                    <div class="form-group full-width">
                        <label>Total Khalis Gold <span class="font-urdu">کل خالص سونا</span></label>
                        <div class="calc-input-wrapper">
                            <input type="number" step="0.001" name="gold_khalis" id="gold_khalis" class="filter-control" placeholder="0.000" readonly>
                            <span class="unit-label">g</span>
                        </div>
                        <div class="formula-hint">Formula: Total Weight - Male Waste</div>
                    </div>
                </div>
            </div>

            <!-- Section 4: Gold Received from Party -->
            <div class="form-section">
                <div class="section-header">
                    <i class="bi bi-box-arrow-in-down"></i>
                    <h3>Gold Received from Party <span class="font-urdu">سونا وصول کیا</span></h3>
                </div>
                <div id="receive-rows-container"></div>
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
                    <span class="live-label">Ratti:</span>
                    <span class="live-value" id="live-ratti">0.00</span>
                </div>
                <div class="live-row highlight-gold" id="gold-khalis-shimmer">
                    <span class="live-label"><i class="bi bi-star-fill"></i> Gold Khalis:</span>
                    <span class="live-value" id="val-gold-khalis">0.000g</span>
                </div>

                <div class="live-row">
                    <span class="live-label"><i class="bi bi-plus-circle row-addition"></i> RP Mazdori Gold:</span>
                    <span class="live-value" id="live-rp-maz">0.000g</span>
                </div>
                <div class="live-row">
                    <span class="live-label"><i class="bi bi-plus-circle row-addition"></i> Casting Mazdori:</span>
                    <span class="live-value" id="live-cast-maz">0.000g</span>
                </div>
                <div class="live-row highlight-blue">
                    <span class="live-label"><i class="bi bi-gem"></i> Effective Gold (Total Invoice):</span>
                    <span class="live-value" id="val-effective-gold">0.000g</span>
                </div>

                <div class="total-box box-grand-total">
                    <div class="total-label">TOTAL GOLD OUT</div>
                    <div class="total-value" id="val-total-gold-out">0.000g</div>
                </div>

                <div class="sidebar-divider" style="margin: 18px 0;"></div>

                <div class="live-row">
                    <span class="live-label">Opening / Previous Balance:</span>
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
                <!-- Wasooli display removed -->

                <div class="total-box box-remaining" id="box-remaining">
                    <div class="total-label">FINAL REMAINING BALANCE</div>
                    <div class="total-value" id="val-remaining-balance">0.000g</div>
                </div>

                <div style="margin-top: 28px; display: grid; gap: 14px;">
                    <button type="submit" name="action" value="save" class="btn-gold ripple" style="justify-content: center; padding: 14px 0;">
                        <i class="bi bi-save"></i> Save Invoice
                    </button>
                    <button type="submit" name="action" value="print" class="button-outline" style="justify-content: center; padding: 14px 0;">
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
// Simple Ratti Formula (No toggles - always auto)
function convertToKhalis(gross, ratti) {
    const g = parseFloat(gross || 0);
    const r = parseFloat(ratti || 0);
    if (g <= 0) return 0;
    return Math.round((g - ((g / 96) * r)) * 1000) / 1000;
}

let receiveRowIndex = 0;
let allCustomerOptions = [];

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
    row.remove();
    calculate();
}

document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('invoice-form');
    const invoiceTypeSelect = document.getElementById('invoice_type');
    const customerSelect = document.getElementById('customer_id');

    // Add initial receive row
    addReceiveRow();

    // Party Type Filtering (kept from your original)
    allCustomerOptions = Array.from(customerSelect.options).map(option => ({
        value: option.value,
        text: option.textContent,
        type: option.getAttribute('data-type') || ''
    }));

    function filterCustomers(selectedType) {
        const currentValue = customerSelect.value;
        customerSelect.innerHTML = '<option value="">Select matching Party</option>';

        allCustomerOptions.forEach(opt => {
            if (!opt.value) return;
            if (!selectedType || opt.type === selectedType) {
                const option = new Option(opt.text, opt.value);
                option.setAttribute('data-type', opt.type);
                customerSelect.appendChild(option);
            }
        });

        if (currentValue && Array.from(customerSelect.options).some(o => o.value === currentValue)) {
            customerSelect.value = currentValue;
        }
    }

    invoiceTypeSelect.addEventListener('change', (e) => {
        filterCustomers(e.target.value);
        calculate();
    });

    // Customer Selection - Show opening balance immediately
    customerSelect.addEventListener('change', async () => {
        const customerId = customerSelect.value;
        const box = document.getElementById('customer-balance-box');
        const balanceText = document.getElementById('customer-current-balance');
        const prevBalanceInput = document.getElementById('previous_balance');

        if (customerId) {
            try {
                const baseUrl = '{{ url("") }}';
                const response = await fetch(`${baseUrl}/customers/${customerId}/last-balance`);
                const data = await response.json();
                const balance = parseFloat(data.balance || 0);

                prevBalanceInput.value = balance;
                box.style.display = 'block';
                balanceText.innerText = balance.toFixed(3) + 'g';
            } catch (error) {
                console.error('Error fetching customer balance:', error);
                prevBalanceInput.value = "0";
            }
        } else {
            box.style.display = 'none';
            prevBalanceInput.value = "0";
        }
        calculate();
    });

    // Main Calculation Engine using casting, ratti, ratti rate, and mazdori weights
    function calculate() {
        const casting = parseFloat(document.getElementById('casting_weight').value) || 0;
        const ratti = parseFloat(document.getElementById('ratti').value) || 0;
        const rattiRate = parseFloat(document.getElementById('ratti_rate').value) || 0;
        const rpMazdoriWeight = parseFloat(document.getElementById('rp_mazdori_weight').value) || 0;
        const rpMazdoriRate = parseFloat(document.getElementById('rp_mazdori_rate').value) || 0;
        const castMaz = parseFloat(document.getElementById('casting_mazdori_weight').value) || 0;
        const castMazRate = parseFloat(document.getElementById('casting_mazdori_rate').value) || 0;
        const rpRate = parseFloat(document.getElementById('rp_rate').value) || 0;
        const previous = parseFloat(document.getElementById('previous_balance').value) || 0;

        const wasteWeight = rattiRate > 0 ? parseFloat((casting / 10 * rattiRate).toFixed(3)) : 0;
        const totalWeight = parseFloat((casting - wasteWeight).toFixed(3));
        const maleWaste = totalWeight > 0 && ratti > 0 ? parseFloat((totalWeight / 96 * ratti).toFixed(3)) : 0;
        const goldKhalis = parseFloat((totalWeight - maleWaste).toFixed(3));

        const rpMazdoriAmount = parseFloat((rpMazdoriWeight * rpMazdoriRate).toFixed(2));
        const castMazdoriAmount = parseFloat((castMaz * castMazRate).toFixed(2));
        const effective = parseFloat((goldKhalis + rpMazdoriWeight + castMaz).toFixed(3));

        let totalReceivedKhalis = 0;
        document.querySelectorAll('.receive-khalis').forEach(el => {
            totalReceivedKhalis += parseFloat(el.value || 0);
        });
        totalReceivedKhalis = Math.round(totalReceivedKhalis * 1000) / 1000;

        const rpGoldDisplay = document.getElementById('rp-gold-value');
        if (rpGoldDisplay) rpGoldDisplay.innerText = rpMazdoriWeight.toFixed(3);

        document.getElementById('waste_weight').value = wasteWeight.toFixed(3);
        document.getElementById('total_weight').value = totalWeight.toFixed(3);
        document.getElementById('male_waste').value = maleWaste.toFixed(3);
        document.getElementById('gold_khalis').value = goldKhalis.toFixed(3);

        document.getElementById('live-casting').innerText = casting.toFixed(3) + 'g';
        document.getElementById('live-ratti').innerText = ratti.toFixed(2);
        document.getElementById('val-gold-khalis').innerText = goldKhalis.toFixed(3) + 'g';
        document.getElementById('live-rp-maz').innerText = rpMazdoriWeight.toFixed(3) + 'g';
        document.getElementById('live-cast-maz').innerText = castMaz.toFixed(3) + 'g';
        document.getElementById('val-effective-gold').innerText = effective.toFixed(3) + 'g';
        document.getElementById('live-total-received').innerText = totalReceivedKhalis.toFixed(3) + 'g';
        document.getElementById('val-prev-balance').innerText = previous.toFixed(3) + 'g';

        const totalReceivedInput = document.getElementById('total_received_khalis');
        if (totalReceivedInput) totalReceivedInput.value = totalReceivedKhalis.toFixed(3);

        const liveReceivedDeduct = document.getElementById('live-received-deduct');
        if (liveReceivedDeduct) liveReceivedDeduct.innerText = totalReceivedKhalis.toFixed(3) + 'g';
        const thisInvoiceEl = document.getElementById('val-this-invoice');
        if (thisInvoiceEl) thisInvoiceEl.innerText = effective.toFixed(3) + 'g';
        const totalGoldOutEl = document.getElementById('val-total-gold-out');
        if (totalGoldOutEl) totalGoldOutEl.innerText = effective.toFixed(3) + 'g';

        const remaining = previous + effective - totalReceivedKhalis;
        document.getElementById('val-remaining-balance').innerText = remaining.toFixed(3) + 'g';
        document.getElementById('box-remaining').classList.toggle('cleared', remaining <= 0);
    }

    // Attach live calculation to all inputs
    const allInputs = document.querySelectorAll('input, select, textarea');
    allInputs.forEach(input => {
        input.addEventListener('input', calculate);
        input.addEventListener('change', calculate);
    });

    // Initial calculation
    calculate();
});
</script>
@endsection