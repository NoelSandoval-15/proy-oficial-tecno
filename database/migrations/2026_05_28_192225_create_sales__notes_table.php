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
        Schema::create('sales_notes', function (Blueprint $table) {
            $table->id();
            $table->time('hour');
            $table->date('date');
            $table->float('total_price');
            $table->string('status');


            $table->unsignedBigInteger('users_admin_id');
            $table->unsignedBigInteger('users_client_id')->nullable();
            $table->unsignedBigInteger('tables_id')->nullable();
            $table->unsignedBigInteger('reservations_id')->nullable();

            $table->timestamps();

            $table->foreign('users_admin_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('users_client_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('tables_id')->references('id')->on('tables')->onDelete('cascade');
            $table->foreign('reservations_id')->references('id')->on('reservations')->onDelete('cascade');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales_notes');
    }
};
