<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gold_receipt_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('receipt_id')->constrained('gold_receipts')->cascadeOnDelete();
            $table->string('description', 255)->nullable();
            $table->decimal('gross_weight', 10, 3)->default(0);
            $table->decimal('ratti_impurity', 10, 3)->default(0);
            $table->decimal('khalis_weight', 10, 3)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gold_receipt_items');
    }
};
