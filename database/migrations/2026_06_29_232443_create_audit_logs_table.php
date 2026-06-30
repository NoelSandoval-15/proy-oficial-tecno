<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->string('user_name')->nullable();
            $table->string('user_email')->nullable();
            $table->string('user_role')->nullable();

            $table->string('action', 60);
            $table->string('module', 120)->nullable();
            $table->string('description', 500)->nullable();

            $table->string('method', 20);
            $table->string('url', 2048);
            $table->string('route_name')->nullable();

            $table->string('ip_address', 80)->nullable();
            $table->text('user_agent')->nullable();

            $table->unsignedSmallInteger('status_code')->nullable();

            $table->json('metadata')->nullable();

            $table->timestamps();

            $table->index('user_id');
            $table->index('action');
            $table->index('module');
            $table->index('route_name');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
