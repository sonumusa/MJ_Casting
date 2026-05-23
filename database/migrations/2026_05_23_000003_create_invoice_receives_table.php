<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoice_receives', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained('invoices')->cascadeOnDelete();
            $table->string('description', 255)->nullable();
            $table->decimal('gross_weight', 10, 3)->default(0);
            $table->decimal('ratti_impurity', 10, 3)->default(0); // ratti count like 11
            $table->decimal('khalis_weight', 10, 3)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_receives');
    }
};
