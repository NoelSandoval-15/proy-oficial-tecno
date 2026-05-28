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
        Schema::create('purchase_notes', function (Blueprint $table) {
            $table->id();
            $table->time('hour');
            $table->date('date');
            $table->float('total_price');

            $table->unsignedBigInteger('users_admin_id');
            $table->unsignedBigInteger('suppliers_id')->nullable();

            $table->timestamps();

            $table->foreign('users_admin_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('suppliers_id')->references('id')->on('suppliers')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_notes');
    }
};
