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
        // Multi-item support: one frozen cargo can carry many items,
        // each with its own description/quantity/weight and admin-only rate/cost.
        Schema::create('frozen_cargo_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('frozen_cargo_id')->constrained('frozen_cargos')->cascadeOnDelete();
            $table->string('description');
            $table->integer('quantity')->default(0);
            $table->decimal('weight', 10, 2)->nullable();
            $table->decimal('rate', 12, 2)->nullable();
            $table->decimal('cost', 12, 2)->nullable();
            $table->timestamps();
        });

        Schema::table('frozen_cargos', function (Blueprint $table) {
            $table->text('cargo_description')->nullable()->change();
            $table->decimal('total_cost', 12, 2)->nullable()->after('cost');
        });

        // Backfill: turn every legacy single-description booking into one item row.
        $cargos = \App\Models\FrozenCargo::all();
        foreach ($cargos as $cargo) {
            $description = trim((string) ($cargo->cargo_description ?? ''));
            if ($description === '') {
                $description = 'Frozen cargo item';
            }
            \App\Models\FrozenCargoItem::create([
                'frozen_cargo_id' => $cargo->id,
                'description' => $description,
                'quantity' => 1,
                'weight' => is_numeric($cargo->weight) ? $cargo->weight : null,
                'rate' => null,
                'cost' => is_numeric($cargo->cost) ? $cargo->cost : null,
            ]);
            if ($cargo->total_cost === null) {
                $cargo->update(['total_cost' => is_numeric($cargo->cost) ? $cargo->cost : 0]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('frozen_cargo_items');

        Schema::table('frozen_cargos', function (Blueprint $table) {
            $table->dropColumn('total_cost');
        });
    }
};
