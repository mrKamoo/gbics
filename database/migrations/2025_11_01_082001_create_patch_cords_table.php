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
        Schema::create('patch_cords', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fs_com_product_id')->nullable()->constrained()->nullOnDelete();
            $table->string('serial_number')->nullable();
            $table->string('barcode')->unique();
            $table->decimal('length', 8, 2); // en mètres
            $table->string('connector_type_a'); // LC, SC, ST, etc.
            $table->string('connector_type_b');
            $table->enum('fiber_type', ['single_mode', 'multi_mode']);
            $table->enum('status', ['in_stock', 'deployed', 'faulty', 'retired'])->default('in_stock');
            $table->date('purchase_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('patch_cords');
    }
};
