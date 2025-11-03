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
        Schema::create('switches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('switch_model_id')->constrained()->restrictOnDelete();
            $table->foreignId('bay_id')->nullable()->constrained()->nullOnDelete();
            $table->string('serial_number')->unique();
            $table->string('asset_tag')->unique()->nullable();
            $table->string('barcode')->unique();
            $table->string('hostname')->nullable();
            $table->enum('status', ['in_stock', 'deployed', 'maintenance', 'retired'])->default('in_stock');
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
        Schema::dropIfExists('switches');
    }
};
