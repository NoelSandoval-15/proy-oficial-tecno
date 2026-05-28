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
        Schema::create('details_purchases', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('insumos_id');
            $table->unsignedBigInteger('purchase_notes_id')->nullable();
            $table->integer('amount');
            $table->float('price_purchase');


            $table->timestamps();

            $table->foreign('insumos_id')->references('id')->on('insumos')->onDelete('cascade');
            $table->foreign('purchase_notes_id')->references('id')->on('purchase_notes')->onDelete('cascade');


        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('details_purchases');
    }
};
