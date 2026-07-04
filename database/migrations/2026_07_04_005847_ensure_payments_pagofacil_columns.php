<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('payments')) {
            Schema::create('payments', function (Blueprint $table) {
                $table->id();

                $table->foreignId('sales_note_id')
                    ->constrained('sales_notes')
                    ->cascadeOnDelete();

                $table->string('payment_method', 50);
                $table->double('amount_received')->nullable();
                $table->double('change')->nullable()->default(0);
                $table->timestamp('payment_date')->nullable();
                $table->string('transaction_id', 100)->nullable();

                $table->string('payment_number', 100)->nullable();
                $table->string('status', 50)->default('pending');

                $table->string('pagofacil_transaction_id', 100)->nullable();
                $table->string('payment_method_transaction_id', 100)->nullable();

                $table->text('qr_base64')->nullable();
                $table->string('checkout_url', 2048)->nullable();
                $table->string('deep_link', 2048)->nullable();
                $table->string('qr_content_url', 2048)->nullable();
                $table->string('universal_url', 2048)->nullable();

                $table->timestamp('expiration_date')->nullable();
                $table->timestamp('paid_at')->nullable();

                $table->json('callback_payload')->nullable();
                $table->json('query_payload')->nullable();

                $table->text('error_message')->nullable();

                $table->foreignId('generated_by')
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();

                $table->timestamps();
            });
        } else {
            if (Schema::hasColumn('payments', 'amount_received')) {
                DB::statement('ALTER TABLE payments ALTER COLUMN amount_received DROP NOT NULL');
            }

            if (Schema::hasColumn('payments', 'payment_date')) {
                DB::statement('ALTER TABLE payments ALTER COLUMN payment_date DROP NOT NULL');
            }

            if (Schema::hasColumn('payments', 'change')) {
                DB::statement('ALTER TABLE payments ALTER COLUMN "change" DROP NOT NULL');
                DB::statement('ALTER TABLE payments ALTER COLUMN "change" SET DEFAULT 0');
            }

            Schema::table('payments', function (Blueprint $table) {
                if (!Schema::hasColumn('payments', 'payment_number')) {
                    $table->string('payment_number', 100)->nullable();
                }

                if (!Schema::hasColumn('payments', 'status')) {
                    $table->string('status', 50)->default('pending');
                }

                if (!Schema::hasColumn('payments', 'pagofacil_transaction_id')) {
                    $table->string('pagofacil_transaction_id', 100)->nullable();
                }

                if (!Schema::hasColumn('payments', 'payment_method_transaction_id')) {
                    $table->string('payment_method_transaction_id', 100)->nullable();
                }

                if (!Schema::hasColumn('payments', 'qr_base64')) {
                    $table->text('qr_base64')->nullable();
                }

                if (!Schema::hasColumn('payments', 'checkout_url')) {
                    $table->string('checkout_url', 2048)->nullable();
                }

                if (!Schema::hasColumn('payments', 'deep_link')) {
                    $table->string('deep_link', 2048)->nullable();
                }

                if (!Schema::hasColumn('payments', 'qr_content_url')) {
                    $table->string('qr_content_url', 2048)->nullable();
                }

                if (!Schema::hasColumn('payments', 'universal_url')) {
                    $table->string('universal_url', 2048)->nullable();
                }

                if (!Schema::hasColumn('payments', 'expiration_date')) {
                    $table->timestamp('expiration_date')->nullable();
                }

                if (!Schema::hasColumn('payments', 'paid_at')) {
                    $table->timestamp('paid_at')->nullable();
                }

                if (!Schema::hasColumn('payments', 'callback_payload')) {
                    $table->json('callback_payload')->nullable();
                }

                if (!Schema::hasColumn('payments', 'query_payload')) {
                    $table->json('query_payload')->nullable();
                }

                if (!Schema::hasColumn('payments', 'error_message')) {
                    $table->text('error_message')->nullable();
                }

                if (!Schema::hasColumn('payments', 'generated_by')) {
                    $table->unsignedBigInteger('generated_by')->nullable();
                }
            });
        }

        DB::statement('
            CREATE UNIQUE INDEX IF NOT EXISTS payments_payment_number_unique
            ON payments (payment_number)
            WHERE payment_number IS NOT NULL
        ');

        DB::statement('
            CREATE INDEX IF NOT EXISTS payments_sales_note_id_index
            ON payments (sales_note_id)
        ');

        DB::statement('
            CREATE INDEX IF NOT EXISTS payments_status_index
            ON payments (status)
        ');

        DB::statement('
            CREATE INDEX IF NOT EXISTS payments_pagofacil_transaction_id_index
            ON payments (pagofacil_transaction_id)
        ');

        DB::statement("
            DO $$
            BEGIN
                IF NOT EXISTS (
                    SELECT 1
                    FROM pg_constraint
                    WHERE conname = 'payments_generated_by_foreign'
                ) THEN
                    ALTER TABLE payments
                    ADD CONSTRAINT payments_generated_by_foreign
                    FOREIGN KEY (generated_by)
                    REFERENCES users(id)
                    ON DELETE SET NULL;
                END IF;
            END $$;
        ");
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS payments_payment_number_unique');
        DB::statement('DROP INDEX IF EXISTS payments_sales_note_id_index');
        DB::statement('DROP INDEX IF EXISTS payments_status_index');
        DB::statement('DROP INDEX IF EXISTS payments_pagofacil_transaction_id_index');

        DB::statement("
            DO $$
            BEGIN
                IF EXISTS (
                    SELECT 1
                    FROM pg_constraint
                    WHERE conname = 'payments_generated_by_foreign'
                ) THEN
                    ALTER TABLE payments
                    DROP CONSTRAINT payments_generated_by_foreign;
                END IF;
            END $$;
        ");

        Schema::table('payments', function (Blueprint $table) {
            $columns = [
                'payment_number',
                'status',
                'pagofacil_transaction_id',
                'payment_method_transaction_id',
                'qr_base64',
                'checkout_url',
                'deep_link',
                'qr_content_url',
                'universal_url',
                'expiration_date',
                'paid_at',
                'callback_payload',
                'query_payload',
                'error_message',
                'generated_by',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('payments', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
