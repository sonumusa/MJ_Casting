<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gold_receipts', function (Blueprint $table) {
            $table->id();
            $table->string('receipt_no', 50)->unique()->index();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->enum('receipt_type', ['customer', 'dukandar', 'karigar'])->default('customer');
            $table->date('receipt_date');
            $table->decimal('total_gross_weight', 10, 3)->default(0);
            $table->decimal('total_khalis_weight', 10, 3)->default(0);
            $table->text('remarks')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gold_receipts');
    }
};
