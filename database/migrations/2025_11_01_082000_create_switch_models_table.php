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
        Schema::create('switch_models', function (Blueprint $table) {
            $table->id();
            $table->string('manufacturer'); // Cisco, Juniper, Arista, etc.
            $table->string('model');
            $table->integer('port_count');
            $table->json('port_types'); // [{type: 'SFP+', count: 24}, {type: 'QSFP', count: 4}]
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('switch_models');
    }
};
