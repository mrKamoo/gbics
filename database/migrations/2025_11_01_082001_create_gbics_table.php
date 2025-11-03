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
        Schema::create('gbics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fs_com_product_id')->nullable()->constrained()->nullOnDelete();
            $table->string('serial_number')->unique();
            $table->string('barcode')->unique();
            $table->enum('status', ['in_stock', 'assigned', 'faulty', 'retired'])->default('in_stock');
            $table->date('purchase_date')->nullable();
            $table->date('warranty_end')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gbics');
    }
};
