@extends('layouts.print')

@section('title', 'Invoice #' . $invoice->invoice_no)

@section('extra_css')
<style>
    .mj-header {
        background-color: #E8481C;
        color: white;
        text-align: center;
        padding: 10px 0;
        margin-bottom: 5px;
    }
    .mj-header h1 {
        margin: 0;
        font-size: 24pt;
        font-family: 'Noto Nastaliq Urdu', serif;
        font-weight: bold;
    }
    .mj-header p {
        margin: 0;
        font-size: 14pt;
        font-weight: bold;
    }

    .contact-row {
        text-align: center;
        font-size: 8pt;
        font-family: 'Noto Nastaliq Urdu', serif;
        padding: 2px 0;
        border-bottom: 1px solid #000;
        margin-bottom: 2px;
    }

    .address-line {
        text-align: center;
        font-size: 8pt;
        margin-bottom: 5px;
    }

    .meta-block {
        border: 1px solid #000;
        padding: 5px;
        margin-bottom: 10px;
    }
    .meta-table {
        width: 100%;
        border-collapse: collapse;
    }
    .meta-table td {
        font-size: 9pt;
        padding: 2px;
    }
    .meta-label {
        font-family: 'Noto Nastaliq Urdu', serif;
        text-align: right;
        padding-left: 5px;
    }
    .meta-value {
        text-align: left;
        font-weight: bold;
    }

    .calc-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 10px;
    }
    .calc-table td {
        padding: 3px 0;
        font-size: 10pt;
    }
    .calc-val {
        text-align: left;
        width: 40%;
        font-family: 'JetBrains Mono', monospace;
        font-weight: bold;
    }
    .calc-label {
        text-align: right;
        width: 60%;
        font-family: 'Noto Nastaliq Urdu', serif;
    }

    .line-separator {
        border-top: 1px dashed #000;
        margin: 5px 0;
    }

    /* ✅ NEW: Received & Wasooli section styles */
    .received-section {
        background: rgba(16, 185, 129, 0.1);
        border: 1px solid #10b981;
        border-radius: 4px;
        padding: 5px;
        margin: 5px 0;
    }
    .wasooli-section {
        background: rgba(59, 130, 246, 0.1);
        border: 1px solid #3b82f6;
        border-radius: 4px;
        padding: 5px;
        margin: 5px 0;
    }

    .charges-block {
        margin-top: 10px;
    }
    .charge-row {
        display: flex;
        justify-content: space-between;
        font-size: 9pt;
        padding: 2px 0;
    }
    .charge-center {
        flex: 1;
        text-align: center;
        font-family: 'Noto Nastaliq Urdu', serif;
    }

    /* ✅ NEW: Balance summary box */
    .balance-summary {
        border: 2px solid #000;
        padding: 8px;
        margin-top: 10px;
        background: #fff9c4;
    }
    .balance-row {
        display: flex;
        justify-content: space-between;
        padding: 2px 0;
        font-size: 10pt;
    }
    .balance-row.total {
        border-top: 2px solid #000;
        padding-top: 5px;
        margin-top: 5px;
        font-weight: bold;
        font-size: 12pt;
    }

    .footer-section {
        margin-top: 15px;
        border-top: 1px solid #000;
        padding-top: 5px;
        text-align: center;
        font-size: 7pt;
    }
    .footer-disclaimer {
        font-family: 'Noto Nastaliq Urdu', serif;
        margin-bottom: 5px;
    }

    @media print {
        .mj-header { background-color: #E8481C !important; -webkit-print-color-adjust: exact; }
        .balance-summary { background: #fff9c4 !important; -webkit-print-color-adjust: exact; }
        .received-section { background: rgba(16, 185, 129, 0.1) !important; -webkit-print-color-adjust: exact; }
        .wasooli-section { background: rgba(59, 130, 246, 0.1) !important; -webkit-print-color-adjust: exact; }
    }
</style>
@endsection

@php
    function fv($val, $dec = 3) {
        if ($val === null || $val === '' || $val == 0) return '—';
        return number_format($val, $dec);
    }
    
    // ✅ Helper variables for clean template
    $prevBalance = $invoice->previous_balance ?? 0;
    $effectiveGold = $invoice->effective_gold ?? 0;
    $receivedKhalis = $invoice->total_received_khalis ?? 0;
    $wasooli = $invoice->wasooli ?? 0;
    $remainingBalance = $invoice->remaining_balance ?? 0;
@endphp

@section('content')
    <!-- Section 1: Header Band -->
    <div class="mj-header">
        <h1>ایم جے کاسٹنگ</h1>
        <p>M.J Casting</p>
    </div>

    <!-- Section 2: Contact Info Row -->
    <div class="contact-row">
        فریم نالی: {{ \App\Models\Setting::getSetting('phone', '0300-0000000') }} &nbsp; | &nbsp; 
        کریم نالی: {{ \App\Models\Setting::getSetting('phone_2', '0300-0000000') }}
    </div>

    <!-- Section 3: Address Line -->
    <div class="address-line">
        {{ \App\Models\Setting::getSetting('address', 'Plot C-947, Karigar Area, Lahore') }}
    </div>

    <!-- Section 4: Meta Block -->
    <div class="meta-block">
        <table class="meta-table">
            <tr>
                <td class="meta-value">{{ $invoice->id }}</td>
                <td class="meta-label">نمبر</td>
                <td class="meta-value" style="text-align: right;">{{ $invoice->customer->name }}</td>
            </tr>
            <tr>
                <td class="meta-value">0</td>
                <td class="meta-label">اینٹری</td>
                <td class="meta-value" style="text-align: right;">{{ $invoice->customer->phone }} <span class="meta-label">فون نمبر</span></td>
            </tr>
            <tr>
                <td class="meta-value">{{ $invoice->invoice_date->format('d-m-Y') }}</td>
                <td class="meta-label">تاریخ</td>
                <td class="meta-value" style="text-align: right;">{{ $invoice->invoice_no }} <span class="meta-label">بل نمبر</span></td>
            </tr>
            <tr>
                <td class="meta-value">{{ $invoice->created_at->format('h:i A') }}</td>
                <td class="meta-label">وقت</td>
                <td class="meta-value" style="text-align: right;">{{ number_format($invoice->rp_rate, 2) }} <span class="meta-label">میل:</span></td>
            </tr>
            @if($invoice->manual_book_no)
            <tr>
                <td colspan="2"></td>
                <td class="meta-value" style="text-align: right;">{{ $invoice->manual_book_no }} <span class="meta-label">کتاب نمبر</span></td>
            </tr>
            @endif
        </table>
    </div>

    <!-- Section 5: Calculation Block (Gold Work) -->
    <table class="calc-table">
        <tr>
            <td class="calc-val">{{ fv($invoice->casting_weight) }}</td>
            <td class="calc-label">کاسٹ وزن</td>
        </tr>
        <tr>
            <td class="calc-val">{{ fv($invoice->waste_weight) }}</td>
            <td class="calc-label">ویسٹ</td>
        </tr>
        <tr>
            <td class="calc-val">{{ fv($invoice->total_weight) }}</td>
            <td class="calc-label">ٹوٹل سونا پاؤنڈ</td>
        </tr>
        <tr>
            <td class="calc-val">{{ fv($invoice->male_waste) }}</td>
            <td class="calc-label">میل کاٹ</td>
        </tr>
        <tr>
            <td class="calc-val">{{ fv($invoice->gold_khalis) }}</td>
            <td class="calc-label">خالص سونا</td>
        </tr>
        <tr>
            <td class="calc-val">
                {{ fv($invoice->rp_mazdori_weight) }}
                @if($invoice->ratti > 0)
                    <span style="font-size: 7pt; vertical-align: top;">{{ $invoice->ratti }}</span>
                @endif
            </td>
            <td class="calc-label">اجرت کا پاسہ</td>
        </tr>
        <tr>
            <td class="calc-val">{{ fv($invoice->casting_mazdori_weight) }}</td>
            <td class="calc-label">یارن کا پاسہ</td>
        </tr>
        <tr style="border-top: 1px solid #000;">
            <td class="calc-val">{{ fv($effectiveGold) }}</td>
            <td class="calc-label">ٹوٹل ایفیکٹو گولڈ</td>
        </tr>
    </table>

    <!-- ✅ NEW Section 5.5: Received Gold & Wasooli -->
    @if($receivedKhalis > 0 || $wasooli > 0)
    <div style="margin: 10px 0;">
        @if($receivedKhalis > 0)
        <div class="received-section">
            <table class="calc-table" style="margin:0;">
                <tr>
                    <td class="calc-val" style="color:#10b981;">+ {{ fv($receivedKhalis) }}</td>
                    <td class="calc-label">وصولی (سونے میں) ✨</td>
                </tr>
            </table>
        </div>
        @endif
        
        @if($wasooli > 0)
        <div class="wasooli-section">
            <table class="calc-table" style="margin:0;">
                <tr>
                    <td class="calc-val" style="color:#3b82f6;">- {{ fv($wasooli) }}</td>
                    <td class="calc-label">وصولی (کیش میں) 💰</td>
                </tr>
            </table>
        </div>
        @endif
    </div>
    @endif

    <!-- Section 6: Balance Calculation Block (FIXED FORMULA) -->
    <div class="balance-summary">
        <div style="text-align:center;font-weight:bold;margin-bottom:5px;font-family:'Noto Nastaliq Urdu',serif;">
            حساب کتاب - بیلنس
        </div>
        
        <div class="balance-row">
            <span class="calc-label" style="width:70%;">پچھلا بیلنس:</span>
            <span class="calc-val">{{ fv($prevBalance) }}</span>
        </div>
        <div class="balance-row">
            <span class="calc-label" style="width:70%;">+ ایفیکٹو گولڈ (دیا گیا):</span>
            <span class="calc-val" style="color:var(--gold-primary, #daa520);">+ {{ fv($effectiveGold) }}</span>
        </div>
        
        @if($receivedKhalis > 0)
        <div class="balance-row">
            <span class="calc-label" style="width:70%;">- وصولی (سونے میں):</span>
            <span class="calc-val" style="color:#10b981;">- {{ fv($receivedKhalis) }}</span>
        </div>
        @endif
        
        @if($wasooli > 0)
        <div class="balance-row">
            <span class="calc-label" style="width:70%;">- وصولی (کیش):</span>
            <span class="calc-val" style="color:#3b82f6;">- {{ fv($wasooli) }}</span>
        </div>
        @endif
        
        <div class="balance-row total">
            <span class="calc-label" style="width:70%;">باقی بیلنس:</span>
            <span class="calc-val" style="color:{{ $remainingBalance > 0 ? '#dc2626' : '#10b981' }};">
                {{ fv($remainingBalance) }}
            </span>
        </div>
        
        <div style="text-align:center;margin-top:5px;font-size:8pt;color:#666;">
            {{ $remainingBalance > 0 ? 'پارٹی آپ کی مقروض ہے' : 'آپ پارٹی کے مقروض ہیں' }}
        </div>
    </div>

    <!-- Section 7: Receive Details (if invoice has receive rows) -->
    @if($invoice->receives && $invoice->receives->count() > 0)
    <div style="margin-top:15px;border:1px solid #000;padding:5px;">
        <div style="font-weight:bold;margin-bottom:5px;font-family:'Noto Nastaliq Urdu',serif;">
            تفصیل وصولی (سونے کی)
        </div>
        <table class="calc-table" style="font-size:9pt;">
            <thead>
                <tr style="border-bottom:1px solid #000;">
                    <th style="text-align:left;">تفصیل</th>
                    <th style="text-align:right;">گراس</th>
                    <th style="text-align:right;">خالص</th>
                </tr>
            </thead>
            <tbody>
                @foreach($invoice->receives as $receive)
                <tr>
                    <td>{{ $receive->description ?? 'Gold' }}</td>
                    <td style="text-align:right;font-family:monospace;">{{ fv($receive->gross_weight ?? 0) }}</td>
                    <td style="text-align:right;font-family:monospace;color:#10b981;">{{ fv($receive->khalis_weight ?? 0) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    @if($invoice->remarks)
    <div style="margin-top:10px;font-size:9pt;border-top:1px dashed #000;padding-top:5px;">
        <strong>نوٹ:</strong> {{ $invoice->remarks }}
    </div>
    @endif

    <!-- Section 8: Footer -->
    <div class="footer-section">
        <div class="footer-disclaimer">براہ کرم مال وصول کرتے وقت چیک کریں بعد میں شکایت قابل قبول نہیں</div>
        <div>Messenger: {{ \App\Models\Setting::getSetting('phone', '0322-6796306') }}</div>
        <div>{{ \App\Models\Setting::getSetting('workshop_name', 'M.J Casting') }} یوٹیوب چینل</div>
        <div style="margin-top:3px;font-size:6pt;color:#999;">
            Printed: {{ now()->format('d-m-Y h:i A') }} | Invoice ID: {{ $invoice->id }}
        </div>
    </div>
@endsection