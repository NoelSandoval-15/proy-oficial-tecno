<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE sales_notes ALTER COLUMN users_admin_id DROP NOT NULL');

        Schema::table('sales_notes', function (Blueprint $table) {
            if (!Schema::hasColumn('sales_notes', 'order_type')) {
                $table->string('order_type')->nullable()->after('status');
            }
        });
    }

    public function down(): void
    {
        Schema::table('sales_notes', function (Blueprint $table) {
            if (Schema::hasColumn('sales_notes', 'order_type')) {
                $table->dropColumn('order_type');
            }
        });

        DB::statement('ALTER TABLE sales_notes ALTER COLUMN users_admin_id SET NOT NULL');
    }
};
