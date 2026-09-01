<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('quotes', function (Blueprint $table) {
            if (DB::getDriverName() === 'mysql') {
                DB::statement('ALTER TABLE quotes MODIFY height DECIMAL(8, 2) NULL;');
            } else {
                $table->decimal('height', 8, 2)->nullable()->change();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quotes', function (Blueprint $table) {
            if (DB::getDriverName() === 'mysql') {
                DB::statement('ALTER TABLE quotes MODIFY height DECIMAL(8, 2) NOT NULL;');
            } else {
                $table->decimal('height', 8, 2)->nullable(false)->change();
            }
        });
    }
};
