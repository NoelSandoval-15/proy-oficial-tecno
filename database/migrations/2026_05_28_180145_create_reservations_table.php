<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reservations', function (Blueprint $table) {
            $table->id();
            $table->string('descriptions');
            $table->time('hour');
            $table->date('date');
            $table->string('state');
            $table->unsignedBigInteger('users_id');
            $table->unsignedBigInteger('users_cliente_id'); 

            $table->timestamps();
            $table->foreign('users_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('users_cliente_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reservations');
    }
};