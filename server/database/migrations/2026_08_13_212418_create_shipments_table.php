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
        Schema::create('shipments', function (Blueprint $table) {
            $table->id();
            $table->string('tracking_id')->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('origin')->nullable();
            $table->string('destination')->nullable();
            $table->string('status')->default('pending');
            $table->date('expected_delivery_date')->nullable();
            
            // New fields for details page
            $table->string('service')->nullable();
            $table->string('weight')->nullable();
            $table->integer('packages')->nullable();
            $table->date('shipped_date')->nullable();
            $table->date('delivered_date')->nullable();
            $table->string('recipient_name')->nullable();
            $table->string('recipient_location')->nullable();
            $table->decimal('shipping_cost', 10, 2)->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shipments');
    }
};
