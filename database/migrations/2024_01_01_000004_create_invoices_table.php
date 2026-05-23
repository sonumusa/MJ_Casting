<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_no', 50)->unique()->index();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->date('invoice_date')->index();
            
            // Weight fields
            $table->decimal('casting_weight', 10, 3)->default(0);
            $table->decimal('waste_weight', 10, 3)->default(0);
            $table->decimal('total_weight', 10, 3)->default(0);
            $table->decimal('ratti', 10, 3)->default(0);
            $table->decimal('ratti_rate', 10, 3)->default(0);
            $table->decimal('male_waste', 10, 3)->default(0);
            $table->decimal('gold_khalis', 10, 3)->default(0);
            
            // RP (Redemption Price) fields
            $table->decimal('rp_rate', 15, 2)->default(0);
            $table->decimal('rp_amount', 15, 2)->default(0);
            $table->decimal('rp_mazdori_weight', 10, 3)->default(0);
            $table->decimal('rp_mazdori_rate', 15, 2)->default(0);
            $table->decimal('rp_mazdori_amount', 15, 2)->default(0);
            
            // Casting Mazdori fields
            $table->decimal('casting_mazdori_weight', 10, 3)->default(0);
            $table->decimal('casting_mazdori_rate', 15, 2)->default(0);
            $table->decimal('casting_mazdori_amount', 15, 2)->default(0);
            
            // Calculated fields
            $table->decimal('effective_gold', 10, 3)->default(0);
            $table->decimal('grand_total', 15, 2)->default(0);
            
            // Payment fields
            $table->decimal('wasooli', 15, 2)->default(0);
            $table->decimal('previous_balance', 15, 2)->default(0);
            $table->decimal('remaining_balance', 15, 2)->default(0);
            
            // Additional fields
            $table->string('manual_book_no', 50)->nullable()->index();
            $table->text('remarks')->nullable();
            $table->enum('status', ['active', 'cancelled'])->default('active')->index();
            
            // Audit fields
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
