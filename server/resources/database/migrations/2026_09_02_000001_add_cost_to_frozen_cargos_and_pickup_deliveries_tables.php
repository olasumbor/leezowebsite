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
        Schema::table('frozen_cargos', function (Blueprint $table) {
            if (!Schema::hasColumn('frozen_cargos', 'cost')) {
                $table->decimal('cost', 12, 2)->nullable()->after('status');
            }
        });

        Schema::table('pickup_deliveries', function (Blueprint $table) {
            if (!Schema::hasColumn('pickup_deliveries', 'cost')) {
                $table->decimal('cost', 12, 2)->nullable()->after('status');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('frozen_cargos', function (Blueprint $table) {
            if (Schema::hasColumn('frozen_cargos', 'cost')) {
                $table->dropColumn('cost');
            }
        });

        Schema::table('pickup_deliveries', function (Blueprint $table) {
            if (Schema::hasColumn('pickup_deliveries', 'cost')) {
                $table->dropColumn('cost');
            }
        });
    }
};
