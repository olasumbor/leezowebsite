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
        // Multi-item support: one pickup & delivery request can carry many items,
        // each with its own description and admin-assigned cost.
        Schema::create('pickup_delivery_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pickup_delivery_id')->constrained('pickup_deliveries')->cascadeOnDelete();
            $table->string('description');
            $table->decimal('cost', 12, 2)->nullable();
            $table->timestamps();
        });

        Schema::table('pickup_deliveries', function (Blueprint $table) {
            if (!Schema::hasColumn('pickup_deliveries', 'total_cost')) {
                $table->decimal('total_cost', 12, 2)->nullable()->after('cost');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pickup_deliveries', function (Blueprint $table) {
            if (Schema::hasColumn('pickup_deliveries', 'total_cost')) {
                $table->dropColumn('total_cost');
            }
        });

        Schema::dropIfExists('pickup_delivery_items');
    }
};
