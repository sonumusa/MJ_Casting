@extends('layouts.app')

@section('title', 'New Gold Receipt')

@section('extra_css')
<style>
    .receipt-grid {
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
    .receive-row:hover { border-color: rgba(218,165,32,0.3); }
    .receive-row .form-group { margin-bottom: 0; }
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
    .btn-add-row:hover { background: rgba(16,185,129,0.2); transform: translateY(-1px); }
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
    .btn-remove-row:hover { background: rgba(244,63,94,0.2); transform: scale(1.05); }
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
    .highlight-gold {
        background: rgba(218, 165, 32, 0.08);
        margin: 6px -28px;
        padding: 12px 28px;
        color: var(--gold-bright);
        border-left: 3px solid var(--gold-primary);
        border-right: 3px solid var(--gold-primary);
    }
    .highlight-gold .live-value { color: var(--gold-bright); font-weight: 700; }
    .total-box {
        margin-top: 24px;
        padding: 18px;
        border-radius: 12px;
        text-align: center;
        box-shadow: var(--shadow-2);
        background: linear-gradient(135deg, #064e3b, #059669);
        border: 1px solid var(--success);
    }
    .total-label { font-size: 0.72rem; text-transform: uppercase; letter-spacing: 0.12em; opacity: 0.9; margin-bottom: 6px; font-weight: 700; }
    .total-value { font-family: 'JetBrains Mono', monospace; font-size: 1.6rem; font-weight: 700; }
    .formula-display {
        font-size: 0.75rem; color: var(--text-muted); font-style: italic; margin-top: 4px;
        background: rgba(255,255,255,0.02); padding: 6px 10px; border-radius: 6px;
    }
    .calc-input-wrapper { position: relative; }
    .calc-input-wrapper input { padding-right: 44px; }
    .unit-label {
        position: absolute; right: 14px; top: 50%; transform: translateY(-50%);
        color: var(--text-muted); font-size: 0.75rem; pointer-events: none; font-weight: 500;
    }
    @media (max-width: 1024px) {
        .receipt-grid { grid-template-columns: 1fr; }
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
<form action="{{ route('gold-receipts.store') }}" method="POST" id="receipt-form">
    @csrf
    <div class="receipt-grid">
        <div class="form-column">
            <div class="page-header" style="margin-bottom: 28px;">
                <div class="page-title-group">
                    <h1>New Gold Receipt</h1>
                    <p class="font-urdu" style="margin-top:4px;">سونا وصولی رسید</p>
                </div>
            </div>

            <div class="form-section">
                <div class="section-header">
                    <i class="bi bi-info-circle"></i>
                    <h3>Receipt Details</h3>
                </div>
                <div class="input-grid">
                    <div class="form-group full-width">
                        <label for="receipt_type">Receipt Type <span class="font-urdu">قسم</span></label>
                        <select name="receipt_type" id="receipt_type" required>
                            <option value="customer">Customer Receipt (گاہک)</option>
                            <option value="dukandar">Dukandar Receipt (دوکاندار)</option>
                            <option value="karigar">Karigar Receipt (کاریگر)</option>
                        </select>
                    </div>
                    <div class="form-group full-width">
                        <label for="customer_id">Party / Customer <span class="font-urdu">گاہک / دوکاندار</span></label>
                        <select name="customer_id" id="customer_id" class="filter-control" required>
                            <option value="">Select Party</option>
                            @foreach($customers as $customer)
                                <option value="{{ $customer->id }}">{{ $customer->name }} ({{ ucfirst($customer->party_type) }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="receipt_date">Date <span class="font-urdu">تاریخ</span></label>
                        <input type="date" name="receipt_date" id="receipt_date" class="filter-control" value="{{ date('Y-m-d') }}" required>
                    </div>
                    <div class="form-group">
                        <label>Receipt No <span class="font-urdu">رسید نمبر</span></label>
                        <input type="text" class="filter-control" value="{{ $nextReceiptNo }}" readonly style="background:var(--bg-app); color:var(--gold-bright); font-weight:700;">
                    </div>
                </div>
            </div>

            <div class="form-section">
                <div class="section-header">
                    <i class="bi bi-box-arrow-in-down"></i>
                    <h3>Gold Items Received <span class="font-urdu">وصول شدہ سونا</span></h3>
                </div>
                <div id="receive-rows-container">
                    <!-- Rows injected by JS -->
                </div>
                <button type="button" class="btn-add-row" id="btn-add-receive">
                    <i class="bi bi-plus-lg"></i> Add Item Row (نئی لائن)
                </button>
                <div class="formula-display">
                    Conversion Formula: <strong>Khalis = Gross - (Gross / 96 × Ratti)</strong> <span class="font-urdu">خالص = کچا - (کچا / ۹۶ × رتی)</span>
                </div>
            </div>

            <div class="form-section">
                <div class="section-header">
                    <i class="bi bi-chat-square-text"></i>
                    <h3>Additional Info</h3>
                </div>
                <div class="form-group full-width" style="margin-bottom:0;">
                    <label for="remarks">Remarks <span class="font-urdu">ریمارکس</span></label>
                    <textarea name="remarks" id="remarks" class="filter-control" rows="2" placeholder="Any additional notes..."></textarea>
                </div>
            </div>
        </div>

        <div class="sidebar-column">
            <div class="live-panel">
                <h4>
                    <span>Live Summary <span class="font-urdu">خلاصہ</span></span>
                    <i class="bi bi-lightning-charge-fill"></i>
                </h4>

                <div class="live-row highlight-gold" id="total-gross-row">
                    <span class="live-label"><i class="bi bi-box-arrow-in-down"></i> Total Gross:</span>
                    <span class="live-value" id="live-total-gross">0.000g</span>
                </div>
                <div class="live-row" style="border-top: 1px solid rgba(255,255,255,0.05); margin-top:8px; padding-top:12px;">
                    <span class="live-label"><i class="bi bi-dash-circle" style="color:var(--error);"></i> Total Deduction:</span>
                    <span class="live-value" id="live-total-deduction">0.000g</span>
                </div>
                <div class="live-row highlight-gold" id="total-khalis-row">
                    <span class="live-label"><i class="bi bi-star-fill"></i> Total Khalis:</span>
                    <span class="live-value" id="live-total-khalis">0.000g</span>
                </div>

                <div class="total-box">
                    <div class="total-label">PURE GOLD RECEIVED</div>
                    <div class="total-value" id="val-total-khalis">0.000g</div>
                </div>

                <div style="margin-top: 28px; display: grid; gap: 14px;">
                    <button type="submit" class="btn-gold ripple" style="justify-content: center; padding: 12px;">
                        <i class="bi bi-save"></i> Save Receipt
                    </button>
                    <a href="{{ route('gold-receipts.index') }}" class="button-outline" style="justify-content: center; padding: 12px; text-align:center;">
                        <i class="bi bi-arrow-left"></i> Cancel
                    </a>
                </div>
            </div>
        </div>
    </div>
</form>
@endsection

@section('extra_js')
<script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js"></script>
<script>
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
                <input type="text" name="items[${idx}][description]" class="filter-control" placeholder="e.g. Old Chain" value="${data?.description || ''}">
            </div>
            <div class="form-group">
                <label style="font-size:0.7rem; text-transform:none; letter-spacing:0;">Gross Wt <span class="font-urdu">کچا وزن</span></label>
                <div class="calc-input-wrapper">
                    <input type="number" step="0.001" name="items[${idx}][gross_weight]" class="filter-control receive-gross" placeholder="0.000" value="${data?.gross_weight || ''}">
                    <span class="unit-label">g</span>
                </div>
            </div>
            <div class="form-group">
                <label style="font-size:0.7rem; text-transform:none; letter-spacing:0;">Ratti Imp <span class="font-urdu">رتی نقص</span></label>
                <div class="calc-input-wrapper">
                    <input type="number" step="0.01" name="items[${idx}][ratti_impurity]" class="filter-control receive-ratti" placeholder="0" value="${data?.ratti_impurity || ''}">
                    <span class="unit-label">r</span>
                </div>
            </div>
            <div class="form-group">
                <label style="font-size:0.7rem; text-transform:none; letter-spacing:0;">Khalis <span class="font-urdu">خالص</span></label>
                <div class="calc-input-wrapper">
                    <input type="number" step="0.001" name="items[${idx}][khalis_weight]" class="filter-control receive-khalis" placeholder="0.000" value="${data?.khalis_weight || ''}" readonly style="background: var(--bg-app); color: var(--gold-bright); font-weight: 700;">
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
        gsap.to(row, { opacity: 0, height: 0, marginBottom: 0, padding: 0, duration: 0.3, onComplete: () => { row.remove(); calculate(); } });
    }

    document.getElementById('btn-add-receive').addEventListener('click', () => addReceiveRow());

    document.addEventListener('DOMContentLoaded', () => {
        addReceiveRow();
    });

    function calculate() {
        let totalGross = 0;
        let totalKhalis = 0;
        let totalDeduction = 0;

        document.querySelectorAll('.receive-row').forEach(row => {
            const gross = parseFloat(row.querySelector('.receive-gross')?.value || 0);
            const khalis = parseFloat(row.querySelector('.receive-khalis')?.value || 0);
            totalGross += gross;
            totalKhalis += khalis;
            totalDeduction += (gross - khalis);
        });

        totalGross = Math.round(totalGross * 1000) / 1000;
        totalKhalis = Math.round(totalKhalis * 1000) / 1000;
        totalDeduction = Math.round(totalDeduction * 1000) / 1000;

        document.getElementById('live-total-gross').innerText = totalGross.toFixed(3) + 'g';
        document.getElementById('live-total-deduction').innerText = totalDeduction.toFixed(3) + 'g';
        document.getElementById('live-total-khalis').innerText = totalKhalis.toFixed(3) + 'g';
        document.getElementById('val-total-khalis').innerText = totalKhalis.toFixed(3) + 'g';

        const shimmer = document.getElementById('total-khalis-row');
        if (totalKhalis > 0) shimmer.classList.add('shimmer');
        else shimmer.classList.remove('shimmer');
    }
</script>
@endsection
