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
        // Create procurement_items table
        Schema::create('procurement_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('procurement_id')->constrained()->cascadeOnDelete();
            $table->string('description');
            $table->string('category')->nullable();
            $table->string('supplier')->nullable();
            $table->integer('quantity')->default(0);
            $table->decimal('weight', 10, 2)->nullable();
            $table->decimal('rate', 10, 2)->nullable();
            $table->decimal('cost', 10, 2)->nullable();
            $table->decimal('shipment_fee', 10, 2)->default(0);
            $table->decimal('transportation', 10, 2)->default(0);
            $table->timestamps();
        });

        // Add new date fields to procurements table
        Schema::table('procurements', function (Blueprint $table) {
            $table->text('details')->nullable()->change();

            $table->date('request_date')->nullable()->after('recipient_location');
            $table->date('expected_delivery')->nullable()->after('request_date');
            $table->date('delivery_date')->nullable()->after('expected_delivery');
            $table->date('receipt_date')->nullable()->after('delivery_date');
            
            // Add total fields for calculated values
            $table->decimal('total_cost', 10, 2)->nullable()->after('cost');
            $table->decimal('total_shipment_fee', 10, 2)->default(0)->after('total_cost');
            $table->decimal('total_transportation', 10, 2)->default(0)->after('total_shipment_fee');
            $table->decimal('grand_total', 10, 2)->nullable()->after('total_transportation');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('procurement_items');
        
        Schema::table('procurements', function (Blueprint $table) {
            $table->text('details')->nullable(false)->change();
            $table->dropColumn(['request_date', 'expected_delivery', 'delivery_date', 'receipt_date', 'total_cost', 'total_shipment_fee', 'total_transportation', 'grand_total']);
        });
    }
};