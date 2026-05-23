<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->enum('invoice_type', ['customer', 'dukandar', 'karigar'])->default('customer')->after('customer_id');
            $table->decimal('total_received_khalis', 10, 3)->default(0)->after('gold_khalis');
            $table->index('invoice_type');
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn(['invoice_type', 'total_received_khalis']);
        });
    }
};
