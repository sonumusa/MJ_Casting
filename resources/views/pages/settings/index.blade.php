@extends('layouts.app')

@section('title', 'Settings')

@section('content')
    <div class="page-header" style="margin-bottom: 24px;">
        <div class="page-title-group">
            <h1>Workshop Settings <span class="font-urdu">ترتیبات</span></h1>
        </div>
    </div>

    <div class="card">
        <form method="POST" action="{{ route('settings.update') }}">
            @csrf
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px;">
                <div class="form-group">
                    <label for="shop_name">Shop Name <span class="font-urdu">دکان کا نام</span></label>
                    <input id="shop_name" type="text" name="shop_name" value="{{ old('shop_name', $settings['shop_name']) }}" required>
                </div>
                <div class="form-group">
                    <label for="shop_phone">Phone <span class="font-urdu">فون</span></label>
                    <input id="shop_phone" type="text" name="shop_phone" value="{{ old('shop_phone', $settings['shop_phone']) }}">
                </div>
            </div>

            <div class="form-group">
                <label for="shop_address">Shop Address <span class="font-urdu">دکان کا پتہ</span></label>
                <textarea id="shop_address" name="shop_address" rows="3">{{ old('shop_address', $settings['shop_address']) }}</textarea>
            </div>

            <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 24px;">
                <div class="form-group">
                    <label for="currency">Currency <span class="font-urdu">کرنسی</span></label>
                    <input id="currency" type="text" name="currency" value="{{ old('currency', $settings['currency']) }}" required>
                </div>
                <div class="form-group">
                    <label for="default_rp_rate">Default RP Rate <span class="font-urdu">پاسہ ریٹ</span></label>
                    <input id="default_rp_rate" type="number" step="0.01" name="default_rp_rate" value="{{ old('default_rp_rate', $settings['default_rp_rate']) }}" required>
                </div>
                <div class="form-group">
                    <label for="default_gram_rate">Default Gram Rate <span class="font-urdu">گرام ریٹ</span></label>
                    <input id="default_gram_rate" type="number" step="0.01" name="default_gram_rate" value="{{ old('default_gram_rate', $settings['default_gram_rate']) }}" required>
                </div>
            </div>

            <div class="sidebar-divider" style="margin: 32px 0;"></div>

            <h3 style="margin-bottom: 20px; font-family: 'Playfair Display', serif;">Calculation Rules <span class="font-urdu">حسابی قواعد</span></h3>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px; max-width: 600px;">
                <div class="form-group">
                    <label for="default_waste_rate">Default Waste Rate (Factor) <span class="font-urdu">ویسٹ ریٹ</span></label>
                    <input id="default_waste_rate" type="number" step="0.001" name="default_waste_rate" value="{{ old('default_waste_rate', $settings['default_waste_rate']) }}" required>
                    <p style="font-size: 0.75rem; color: var(--text-muted); margin-top: 4px;">e.g. 0.125 means 12.5% of casting weight.</p>
                </div>
                <div class="form-group">
                    <label for="default_ratti_rate">Default Ratti Deduction Rate (g) <span class="font-urdu">ڈیفالٹ رتی کٹوتی</span></label>
                    <input id="default_ratti_rate" type="number" step="0.001" name="default_ratti_rate" value="{{ old('default_ratti_rate', $settings['default_ratti_rate']) }}" required>
                    <p style="font-size: 0.75rem; color: var(--text-muted); margin-top: 4px;">Standard grams per ratti unit.</p>
                </div>
            </div>

            <div class="form-group">
                <label>Auto-Ratti Tiers <span class="font-urdu">رتی ٹیرز</span></label>
                <div style="background: var(--bg-surface); border: 1px solid var(--border-color); border-radius: 8px; padding: 20px;">
                    <table style="width: 100%; border-collapse: collapse;" id="ratti-tiers-table">
                        <thead>
                            <tr>
                                <th style="text-align: left; padding: 10px;">Max Total Weight (g)</th>
                                <th style="text-align: left; padding: 10px;">Ratti Value</th>
                                <th style="width: 50px;"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($settings['ratti_tiers'] as $index => $tier)
                                <tr class="ratti-tier-row">
                                    <td style="padding: 5px;">
                                        <input type="number" step="0.001" name="ratti_tiers[{{ $index }}][max_weight]" value="{{ $tier['max_weight'] }}" class="filter-control" required>
                                    </td>
                                    <td style="padding: 5px;">
                                        <input type="number" step="0.01" name="ratti_tiers[{{ $index }}][ratti]" value="{{ $tier['ratti'] }}" class="filter-control" required>
                                    </td>
                                    <td style="padding: 5px; text-align: center;">
                                        <button type="button" class="btn-outline" style="padding: 4px 8px; color: var(--error);" onclick="removeTier(this)">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    <button type="button" class="btn-outline" style="margin-top: 15px;" onclick="addTier()">
                        <i class="bi bi-plus"></i> Add Tier
                    </button>
                </div>
            </div>

            <div style="margin-top: 32px; display: flex; justify-content: flex-end;">
                <button type="submit" class="btn-gold">
                    <i class="bi bi-save"></i> Save All Settings
                </button>
            </div>
        </form>
    </div>
@endsection

@section('extra_js')
<script>
    function addTier() {
        const table = document.getElementById('ratti-tiers-table').getElementsByTagName('tbody')[0];
        const rowCount = table.rows.length;
        const row = table.insertRow(rowCount);
        row.className = 'ratti-tier-row';
        
        row.innerHTML = `
            <td style="padding: 5px;">
                <input type="number" step="0.001" name="ratti_tiers[${rowCount}][max_weight]" class="filter-control" required>
            </td>
            <td style="padding: 5px;">
                <input type="number" step="0.01" name="ratti_tiers[${rowCount}][ratti]" class="filter-control" required>
            </td>
            <td style="padding: 5px; text-align: center;">
                <button type="button" class="btn-outline" style="padding: 4px 8px; color: var(--error);" onclick="removeTier(this)">
                    <i class="bi bi-trash"></i>
                </button>
            </td>
        `;
    }

    function removeTier(btn) {
        const row = btn.closest('tr');
        row.remove();
        // Re-index rows
        const rows = document.querySelectorAll('.ratti-tier-row');
        rows.forEach((r, i) => {
            r.querySelector('input[name*="[max_weight]"]').name = `ratti_tiers[${i}][max_weight]`;
            r.querySelector('input[name*="[ratti]"]').name = `ratti_tiers[${i}][ratti]`;
        });
    }
</script>
@endsection
