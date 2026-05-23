<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receipt {{ $goldReceipt->receipt_no }}</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;600&display=swap');
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Inter', sans-serif;
            font-size: 13px;
            line-height: 1.5;
            color: #1a1a2e;
            background: #fff;
            padding: 24px;
            max-width: 800px;
            margin: 0 auto;
        }
        .header { text-align: center; margin-bottom: 24px; padding-bottom: 16px; border-bottom: 2px solid #DAA520; }
        .header h1 { font-size: 1.6rem; font-weight: 700; color: #1a1a2e; margin-bottom: 4px; }
        .header .urdu { font-size: 1.1rem; color: #444; margin-bottom: 6px; }
        .header .contact { font-size: 0.8rem; color: #666; }
        .badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 12px;
            font-size: 0.7rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            margin-left: 8px;
        }
        .badge.customer { background: #e0f2fe; color: #0369a1; border: 1px solid #bae6fd; }
        .badge.dukandar { background: #fef3c7; color: #92400e; border: 1px solid #fde68a; }
        .badge.karigar { background: #d1fae5; color: #065f46; border: 1px solid #a7f3d0; }
        .meta { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 20px; }
        .meta-item { padding: 10px 12px; background: #f8f9fa; border-radius: 8px; border: 1px solid #e9ecef; }
        .meta-label { font-size: 0.65rem; text-transform: uppercase; color: #888; font-weight: 600; margin-bottom: 3px; }
        .meta-value { font-weight: 600; font-size: 0.95rem; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
        th { background: #1a1a2e; color: #DAA520; text-align: left; padding: 10px 12px; font-size: 0.7rem; text-transform: uppercase; letter-spacing: 0.05em; font-weight: 600; }
        td { padding: 10px 12px; border-bottom: 1px solid #eee; font-size: 0.9rem; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .font-mono { font-family: 'JetBrains Mono', monospace; }
        .total-row { background: #f8f9fa; font-weight: 700; }
        .total-row td { border-top: 2px solid #DAA520; border-bottom: 2px solid #DAA520; }
        .highlight { background: #fffbeb; }
        .highlight td { color: #92400e; font-weight: 700; }
        .signature { margin-top: 40px; display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .signature-box { border-top: 1px solid #ccc; padding-top: 8px; font-size: 0.8rem; color: #666; }
        .formula { font-size: 0.7rem; color: #999; font-style: italic; text-align: center; margin-top: 16px; padding-top: 12px; border-top: 1px dashed #ddd; }
        @media print {
            body { padding: 0; }
            .no-print { display: none; }
        }
        .print-btn {
            display: inline-flex; align-items: center; gap: 8px;
            padding: 10px 20px; background: linear-gradient(135deg, #B8860B, #DAA520);
            color: white; border: none; border-radius: 8px;
            font-weight: 600; cursor: pointer; font-size: 0.9rem;
            margin-bottom: 16px;
        }
        .print-btn:hover { filter: brightness(1.1); }
    </style>
</head>
<body>
    <div class="no-print" style="text-align:center;">
        <button class="print-btn" onclick="window.print()">
            <svg width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M2.5 8a.5.5 0 1 0 0-1 .5.5 0 0 0 0 1z"/><path d="M5 1a2 2 0 0 0-2 2v2H2a2 2 0 0 0-2 2v3a2 2 0 0 0 2 2h1v1a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2v-1h1a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-1V3a2 2 0 0 0-2-2H5zM4 3a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v2H4V3zm1 5a2 2 0 0 0-2 2v1H2a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1h12a1 1 0 0 1 1 1v3a1 1 0 0 1-1 1h-1v-1a2 2 0 0 0-2-2H5zm7 2v3H5v-3a1 1 0 0 1 1-1h5a1 1 0 0 1 1 1z"/></svg>
            Print Receipt
        </button>
    </div>

    <div class="header">
        <h1>{{ $workshopSettings['name'] }}</h1>
        <div class="urdu">{{ $workshopSettings['name_urdu'] }}</div>
        <div class="contact">
            @if($workshopSettings['address']) {{ $workshopSettings['address'] }} &middot; @endif
            @if($workshopSettings['phone']) {{ $workshopSettings['phone'] }} @endif
            @if($workshopSettings['phone2']) / {{ $workshopSettings['phone2'] }} @endif
            @if($workshopSettings['city']) &middot; {{ $workshopSettings['city'] }} @endif
        </div>
    </div>

    <div style="text-align:center; margin-bottom:20px;">
        <h2 style="font-size:1.1rem; font-weight:700; color:#1a1a2e;">Gold Receive Voucher / سونا وصولی رسید</h2>
    </div>

    <div class="meta">
        <div class="meta-item">
            <div class="meta-label">Receipt No / رسید نمبر</div>
            <div class="meta-value">{{ $goldReceipt->receipt_no }}
                <span class="badge {{ $goldReceipt->receipt_type }}">{{ ucfirst($goldReceipt->receipt_type) }}</span>
            </div>
        </div>
        <div class="meta-item">
            <div class="meta-label">Date / تاریخ</div>
            <div class="meta-value">{{ $goldReceipt->receipt_date->format('d/m/Y') }}</div>
        </div>
        <div class="meta-item">
            <div class="meta-label">Party / گاہک</div>
            <div class="meta-value">{{ $goldReceipt->customer->name ?? 'Unknown' }}</div>
        </div>
        <div class="meta-item">
            <div class="meta-label">Items / اشیاء</div>
            <div class="meta-value">{{ $goldReceipt->items->count() }}</div>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Description / تفصیل</th>
                <th class="text-right">Gross Wt / کچا</th>
                <th class="text-right">Ratti / رتی</th>
                <th class="text-right">Khalis / خالص</th>
                <th class="text-right">Deduction / کٹوتی</th>
            </tr>
        </thead>
        <tbody>
            @foreach($goldReceipt->items as $item)
            <tr>
                <td>{{ $loop->iteration }}</td>
                <td>{{ $item->description ?: '-' }}</td>
                <td class="text-right font-mono">{{ number_format($item->gross_weight, 3) }} g</td>
                <td class="text-right font-mono">{{ number_format($item->ratti_impurity, 2) }} r</td>
                <td class="text-right font-mono" style="font-weight:700;">{{ number_format($item->khalis_weight, 3) }} g</td>
                <td class="text-right font-mono" style="color:#dc2626;">{{ number_format($item->gross_weight - $item->khalis_weight, 3) }} g</td>
            </tr>
            @endforeach
            <tr class="total-row">
                <td colspan="2" class="text-right">TOTALS / کل</td>
                <td class="text-right font-mono">{{ number_format($goldReceipt->total_gross_weight, 3) }} g</td>
                <td></td>
                <td class="text-right font-mono">{{ number_format($goldReceipt->total_khalis_weight, 3) }} g</td>
                <td class="text-right font-mono" style="color:#dc2626;">{{ number_format($goldReceipt->total_gross_weight - $goldReceipt->total_khalis_weight, 3) }} g</td>
            </tr>
        </tbody>
    </table>

    <div style="display:grid; grid-template-columns: 1fr 1fr 1fr; gap:12px; margin-top:16px;">
        <div class="meta-item" style="text-align:center;">
            <div class="meta-label">Total Gross / کل کچا</div>
            <div class="meta-value font-mono">{{ number_format($goldReceipt->total_gross_weight, 3) }} g</div>
        </div>
        <div class="meta-item" style="text-align:center;">
            <div class="meta-label">Total Deduction / کل کٹوتی</div>
            <div class="meta-value font-mono" style="color:#dc2626;">{{ number_format($goldReceipt->total_gross_weight - $goldReceipt->total_khalis_weight, 3) }} g</div>
        </div>
        <div class="meta-item" style="text-align:center; border-color:#DAA520;">
            <div class="meta-label">Total Khalis / کل خالص</div>
            <div class="meta-value font-mono" style="color:#92400e;">{{ number_format($goldReceipt->total_khalis_weight, 3) }} g</div>
        </div>
    </div>

    @if($goldReceipt->remarks)
    <div style="margin-top:16px; padding:12px; background:#f8f9fa; border-radius:8px; border:1px solid #e9ecef;">
        <strong>Remarks / ریمارکس:</strong> {{ $goldReceipt->remarks }}
    </div>
    @endif

    <div class="signature">
        <div class="signature-box">
            <strong>Party Signature / گاہک دستخط</strong><br>
            ________________________
        </div>
        <div class="signature-box" style="text-align:right;">
            <strong>Authorized Signature / مجاز دستخط</strong><br>
            ________________________
        </div>
    </div>

    <div class="formula">
        Formula: Khalis = Gross - (Gross / 96 × Ratti) | خالص = کچا - (کچا / ۹۶ × رتی)
    </div>
</body>
</html>
