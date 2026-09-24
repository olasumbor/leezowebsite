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
        if (Schema::hasTable('pickup_deliveries') && !Schema::hasColumn('pickup_deliveries', 'delivery_phone')) {
            Schema::table('pickup_deliveries', function (Blueprint $table) {
                $table->string('delivery_phone')->nullable()->after('delivery_address');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('pickup_deliveries') && Schema::hasColumn('pickup_deliveries', 'delivery_phone')) {
            Schema::table('pickup_deliveries', function (Blueprint $table) {
                $table->dropColumn('delivery_phone');
            });
        }
    }
};
