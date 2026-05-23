<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->boolean('ratti_auto')->default(false)->after('ratti');
            $table->boolean('waste_auto')->default(false)->after('waste_weight');
            $table->boolean('male_waste_auto')->default(false)->after('male_waste');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn(['ratti_auto', 'waste_auto', 'male_waste_auto']);
        });
    }
};
