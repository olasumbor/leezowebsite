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
        Schema::table('shipments', function (Blueprint $table) {
            if (!Schema::hasColumn('shipments', 'shipment_type')) {
                $table->string('shipment_type')->nullable()->after('service');
            }
            if (!Schema::hasColumn('shipments', 'recipient_email')) {
                $table->string('recipient_email')->nullable()->after('recipient_name');
            }
            if (!Schema::hasColumn('shipments', 'recipient_phone')) {
                $table->string('recipient_phone')->nullable()->after('recipient_email');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            if (Schema::hasColumn('shipments', 'shipment_type')) {
                $table->dropColumn('shipment_type');
            }
            if (Schema::hasColumn('shipments', 'recipient_email')) {
                $table->dropColumn('recipient_email');
            }
            if (Schema::hasColumn('shipments', 'recipient_phone')) {
                $table->dropColumn('recipient_phone');
            }
        });
    }
};