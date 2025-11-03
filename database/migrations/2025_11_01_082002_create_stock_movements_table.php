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
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->string('movable_type'); // Gbic, PatchCord ou Switch (polymorphic)
            $table->unsignedBigInteger('movable_id');
            $table->enum('movement_type', ['in', 'out', 'transfer', 'return', 'adjustment']);
            $table->foreignId('from_site_id')->nullable()->constrained('sites')->nullOnDelete();
            $table->foreignId('to_site_id')->nullable()->constrained('sites')->nullOnDelete();
            $table->integer('quantity')->default(1);
            $table->text('reason')->nullable();
            $table->foreignId('performed_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('performed_at')->useCurrent();
            $table->timestamps();

            $table->index(['movable_type', 'movable_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};
