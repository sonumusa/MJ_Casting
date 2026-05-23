<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Inventory;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $userRole = Role::firstOrCreate(['name' => 'user']);

        $admin = User::firstOrCreate(
            ['email' => 'admin@goldworkshop.test'],
            [
                'name' => 'Administrator',
                'email_verified_at' => now(),
                'password' => Hash::make('admin123'),
            ]
        );
        $admin->assignRole($adminRole);

        $defaultUser = User::firstOrCreate(
            ['email' => 'user@goldworkshop.test'],
            [
                'name' => 'Normal User',
                'email_verified_at' => now(),
                'password' => Hash::make('user12345'),
            ]
        );
        $defaultUser->assignRole($userRole);

        Setting::firstOrCreate(['setting_key' => 'workshop_name'], [
            'setting_value' => 'Gold Workshop & Jewelry',
            'setting_type' => 'text',
        ]);
        Setting::firstOrCreate(['setting_key' => 'workshop_address'], [
            'setting_value' => 'Sarafa Bazar, Pakistan',
            'setting_type' => 'text',
        ]);
        Setting::firstOrCreate(['setting_key' => 'workshop_phone'], [
            'setting_value' => '0300-1234567',
            'setting_type' => 'text',
        ]);
        Setting::firstOrCreate(['setting_key' => 'workshop_city'], [
            'setting_value' => 'Karachi',
            'setting_type' => 'text',
        ]);
        Setting::firstOrCreate(['setting_key' => 'default_rp_rate'], [
            'setting_value' => '65000',
            'setting_type' => 'number',
        ]);
        Setting::firstOrCreate(['setting_key' => 'default_ratti_rate'], [
            'setting_value' => '0.1',
            'setting_type' => 'number',
        ]);
        Setting::firstOrCreate(['setting_key' => 'currency_symbol'], [
            'setting_value' => 'Rs.',
            'setting_type' => 'text',
        ]);
        Setting::firstOrCreate(['setting_key' => 'invoice_prefix'], [
            'setting_value' => 'INV',
            'setting_type' => 'text',
        ]);
        Setting::firstOrCreate(['setting_key' => 'financial_year_start'], [
            'setting_value' => '01/07',
            'setting_type' => 'text',
        ]);

        Inventory::firstOrCreate(
            ['id' => 1],
            [
                'opening_balance' => 0,
                'received' => 0,
                'given_invoices' => 0,
                'closing_balance' => 0,
            ]
        );

        $customerA = Customer::firstOrCreate(
            ['phone' => '0300-1112222'],
            [
                'name' => 'Ali Khan',
                'cnic' => '42101-1234567-1',
                'address' => 'Shop 5, Sarafa Bazar',
                'city' => 'Karachi',
                'opening_balance' => 12000.00,
                'status' => 'active',
            ]
        );

        $customerB = Customer::firstOrCreate(
            ['phone' => '0300-8887777'],
            [
                'name' => 'Sadia Bibi',
                'cnic' => '42101-7654321-9',
                'address' => 'House 12, Clifton',
                'city' => 'Karachi',
                'opening_balance' => 4500.00,
                'status' => 'active',
            ]
        );

        Invoice::firstOrCreate(
            ['invoice_no' => 'INV-1001'],
            [
                'customer_id' => $customerA->id,
                'invoice_date' => Carbon::now()->subDays(20)->toDateString(),
                'casting_weight' => 8.500,
                'waste_weight' => 0.200,
                'total_weight' => 8.700,
                'ratti' => 1.100,
                'ratti_rate' => 0.12,
                'male_waste' => 0.140,
                'gold_khalis' => 8.560,
                'rp_rate' => 65000,
                'rp_amount' => 8.560 * 65000,
                'rp_mazdori_weight' => 0.080,
                'rp_mazdori_rate' => 1200,
                'rp_mazdori_amount' => 96.00,
                'casting_mazdori_weight' => 0.050,
                'casting_mazdori_rate' => 1500,
                'casting_mazdori_amount' => 75.00,
                'effective_gold' => 8.560,
                'grand_total' => 8.560 * 65000 + 96.00 + 75.00,
                'wasooli' => 30000.00,
                'previous_balance' => 12000.00,
                'remaining_balance' => (8.560 * 65000 + 96.00 + 75.00) - 30000.00 + 12000.00,
                'manual_book_no' => 'MB-101',
                'remarks' => 'Sample jewellery invoice for Ali Khan.',
                'status' => 'active',
                'created_by' => $admin->id,
                'updated_by' => $admin->id,
            ]
        );

        Invoice::firstOrCreate(
            ['invoice_no' => 'INV-1002'],
            [
                'customer_id' => $customerB->id,
                'invoice_date' => Carbon::now()->subDays(12)->toDateString(),
                'casting_weight' => 5.200,
                'waste_weight' => 0.120,
                'total_weight' => 5.320,
                'ratti' => 0.900,
                'ratti_rate' => 0.10,
                'male_waste' => 0.090,
                'gold_khalis' => 5.230,
                'rp_rate' => 65000,
                'rp_amount' => 5.230 * 65000,
                'rp_mazdori_weight' => 0.050,
                'rp_mazdori_rate' => 1200,
                'rp_mazdori_amount' => 60.00,
                'casting_mazdori_weight' => 0.040,
                'casting_mazdori_rate' => 1500,
                'casting_mazdori_amount' => 60.00,
                'effective_gold' => 5.230,
                'grand_total' => 5.230 * 65000 + 60.00 + 60.00,
                'wasooli' => 20000.00,
                'previous_balance' => 4500.00,
                'remaining_balance' => (5.230 * 65000 + 60.00 + 60.00) - 20000.00 + 4500.00,
                'manual_book_no' => 'MB-102',
                'remarks' => 'Sample invoice for Sadia Bibi.',
                'status' => 'active',
                'created_by' => $admin->id,
                'updated_by' => $admin->id,
            ]
        );
    }
}

