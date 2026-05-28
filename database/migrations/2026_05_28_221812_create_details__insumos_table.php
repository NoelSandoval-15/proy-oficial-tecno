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
        Schema::create('details_insumos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('insumos_id');
            $table->unsignedBigInteger('insumos_notes_id');
            $table->integer('amount');

            $table->foreign('insumos_id')->references('id')->on('insumos')->onDelete('cascade');
            $table->foreign('insumos_notes_id')->references('id')->on('insumos_notes')->onDelete('cascade');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('details_insumos');
    }
};
