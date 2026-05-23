<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Contracts\View\View;

class SettingController extends Controller
{
    public function index(): View
    {
        $settings = [
            'shop_name' => Setting::getSetting('shop_name', config('app.name', 'Gold Workshop')),
            'shop_address' => Setting::getSetting('shop_address', ''),
            'shop_phone' => Setting::getSetting('shop_phone', ''),
            'currency' => Setting::getSetting('currency', 'PKR'),
            'default_rp_rate' => Setting::getSetting('default_rp_rate', 0),
            'default_gram_rate' => Setting::getSetting('default_gram_rate', 0),
            'default_waste_rate' => Setting::getSetting('default_waste_rate', 0.125),
            'default_ratti_rate' => Setting::getSetting('default_ratti_rate', 0),
            'ratti_tiers' => Setting::getSetting('ratti_tiers', [
                ['max_weight' => 15, 'ratti' => 0.1],
                ['max_weight' => 25, 'ratti' => 0.2],
                ['max_weight' => 40, 'ratti' => 0.3],
                ['max_weight' => 60, 'ratti' => 0.4],
                ['max_weight' => 9999, 'ratti' => 0.5],
            ]),
        ];

        return view('pages.settings.index', compact('settings'));
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'shop_name' => 'required|string|max:150',
            'shop_address' => 'nullable|string|max:255',
            'shop_phone' => 'nullable|string|max:50',
            'currency' => 'required|string|max:10',
            'default_rp_rate' => 'required|numeric|min:0',
            'default_gram_rate' => 'required|numeric|min:0',
            'default_waste_rate' => 'required|numeric|min:0',
            'default_ratti_rate' => 'required|numeric|min:0',
            'ratti_tiers' => 'required|array',
            'ratti_tiers.*.max_weight' => 'required|numeric|min:0',
            'ratti_tiers.*.ratti' => 'required|numeric|min:0',
        ]);

        Setting::setSetting('shop_name', $validated['shop_name'], 'text');
        Setting::setSetting('shop_address', $validated['shop_address'] ?? '', 'text');
        Setting::setSetting('shop_phone', $validated['shop_phone'] ?? '', 'text');
        Setting::setSetting('currency', $validated['currency'], 'text');
        Setting::setSetting('default_rp_rate', $validated['default_rp_rate'], 'number');
        Setting::setSetting('default_gram_rate', $validated['default_gram_rate'], 'number');
        Setting::setSetting('default_waste_rate', $validated['default_waste_rate'], 'number');
        Setting::setSetting('default_ratti_rate', $validated['default_ratti_rate'], 'number');
        Setting::setSetting('ratti_tiers', json_encode($validated['ratti_tiers']), 'json');

        return redirect()->route('settings.index')->with('success', 'Settings saved successfully.');
    }
}
