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
        if (Schema::hasTable('shipments') && !Schema::hasColumn('shipments', 'invoice_generated')) {
            Schema::table('shipments', function (Blueprint $table) {
                $table->boolean('invoice_generated')->default(false)->after('shipping_cost');
            });
        }

        if (Schema::hasTable('procurements') && !Schema::hasColumn('procurements', 'invoice_generated')) {
            Schema::table('procurements', function (Blueprint $table) {
                $table->boolean('invoice_generated')->default(false)->after('cost');
            });
        }

        if (Schema::hasTable('pickup_deliveries') && !Schema::hasColumn('pickup_deliveries', 'invoice_generated')) {
            Schema::table('pickup_deliveries', function (Blueprint $table) {
                $table->boolean('invoice_generated')->default(false)->after('notes');
            });
        }

        if (Schema::hasTable('frozen_cargos') && !Schema::hasColumn('frozen_cargos', 'invoice_generated')) {
            Schema::table('frozen_cargos', function (Blueprint $table) {
                $table->boolean('invoice_generated')->default(false)->after('notes');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('shipments') && Schema::hasColumn('shipments', 'invoice_generated')) {
            Schema::table('shipments', function (Blueprint $table) {
                $table->dropColumn('invoice_generated');
            });
        }

        if (Schema::hasTable('procurements') && Schema::hasColumn('procurements', 'invoice_generated')) {
            Schema::table('procurements', function (Blueprint $table) {
                $table->dropColumn('invoice_generated');
            });
        }

        if (Schema::hasTable('pickup_deliveries') && Schema::hasColumn('pickup_deliveries', 'invoice_generated')) {
            Schema::table('pickup_deliveries', function (Blueprint $table) {
                $table->dropColumn('invoice_generated');
            });
        }

        if (Schema::hasTable('frozen_cargos') && Schema::hasColumn('frozen_cargos', 'invoice_generated')) {
            Schema::table('frozen_cargos', function (Blueprint $table) {
                $table->dropColumn('invoice_generated');
            });
        }
    }
};
