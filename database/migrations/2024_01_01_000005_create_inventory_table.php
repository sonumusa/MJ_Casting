<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory', function (Blueprint $table) {
            $table->id();
            $table->decimal('opening_balance', 10, 3)->default(0);
            $table->decimal('received', 10, 3)->default(0);
            $table->decimal('given_invoices', 10, 3)->default(0);
            $table->decimal('closing_balance', 10, 3)->default(0);
            $table->string('period_label', 100)->nullable();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory');
    }
};
