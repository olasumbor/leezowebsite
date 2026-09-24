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
        Schema::create('procurements', function (Blueprint $table) {
            $table->id();
            $table->string('procurement_id')->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('email');
            $table->string('phone');
            $table->text('details');
            $table->string('status')->default('pending');
            
            // New fields for details page
            $table->string('category')->nullable();
            $table->string('quantity')->nullable();
            $table->string('supplier')->nullable();
            $table->string('location')->nullable();
            $table->date('expected_date')->nullable();
            $table->date('delivered_date')->nullable();
            $table->string('recipient_location')->nullable();
            $table->decimal('cost', 10, 2)->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('procurements');
    }
};
