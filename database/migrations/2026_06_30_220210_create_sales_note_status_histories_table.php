<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_note_status_histories', function (Blueprint $table) {
            $table->id();

            $table->foreignId('sales_notes_id')
                ->constrained('sales_notes')
                ->cascadeOnDelete();

            $table->foreignId('users_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->string('status');
            $table->string('title');
            $table->text('description')->nullable();

            $table->timestamps();

            $table->index(['sales_notes_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_note_status_histories');
    }
};
