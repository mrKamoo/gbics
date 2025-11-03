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
        Schema::create('fs_com_products', function (Blueprint $table) {
            $table->id();
            $table->string('fs_com_id')->unique();
            $table->string('sku')->unique();
            $table->string('name');
            $table->enum('category', ['gbic', 'patch_cord']);
            $table->text('description')->nullable();
            $table->json('specifications')->nullable();
            $table->decimal('price', 10, 2)->nullable();
            $table->string('currency', 3)->nullable();
            $table->string('url')->nullable();
            $table->string('image_url')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fs_com_products');
    }
};
