<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\FrozenCargo;
use App\Models\FrozenCargoItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;

class FrozenCargoItemsTest extends TestCase
{
    use RefreshDatabase;

    private function adminPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Frozen Customer',
            'email' => 'frozen.customer.' . rand(1000, 9999) . '@example.com',
            'phone' => '08012345678',
            'temperature_requirement' => 'Frozen (-18°C)',
            'origin' => 'Lagos, Nigeria',
            'destination' => 'London, UK',
            'departure_date' => now()->addDays(10)->toDateString(),
            'notes' => 'Keep the cold chain unbroken.',
            'items' => [
                [
                    'description' => 'Frozen chicken',
                    'quantity' => 10,
                    'weight' => 250.5,
                    'rate' => 1200,
                    'cost' => 12000,
                ],
                [
                    'description' => 'Frozen fish',
                    'quantity' => 5,
                    'weight' => 75,
                    'rate' => 900,
                    'cost' => 4500,
                ],
            ],
        ], $overrides);
    }

    public function test_guest_can_submit_frozen_cargo_with_multiple_items()
    {
        Mail::fake();

        $response = $this->postJson('/api/frozen-cargos', [
            'name' => 'Guest Customer',
            'email' => 'guest.frozen.' . rand(1000, 9999) . '@example.com',
            'phone' => '08011112222',
            'temperature_requirement' => 'Chilled (0°C to 4°C)',
            'origin' => 'Lagos, Nigeria',
            'destination' => 'Accra, Ghana',
            'items' => [
                ['description' => 'Frozen chicken', 'quantity' => 10, 'weight' => 250.5],
                ['description' => 'Frozen fish', 'quantity' => 5, 'weight' => 75],
            ],
        ]);

        $response->assertStatus(201);

        $requestId = $response->json('frozen_cargo.request_id');
        $frozenCargo = FrozenCargo::with('items')->where('request_id', $requestId)->first();

        $this->assertNotNull($frozenCargo);
        $this->assertCount(2, $frozenCargo->items);
        $this->assertEquals('Frozen chicken', $frozenCargo->items[0]->description);
        $this->assertEquals('Frozen fish', $frozenCargo->items[1]->description);
        $this->assertEquals(10, $frozenCargo->items[0]->quantity);
        // Header weight is the sum of the item weights.
        $this->assertEquals(325.5, (float) $frozenCargo->weight);
    }

    public function test_legacy_single_description_submission_creates_one_item_row()
    {
        Mail::fake();

        $response = $this->postJson('/api/frozen-cargos', [
            'name' => 'Legacy Customer',
            'email' => 'legacy.frozen.' . rand(1000, 9999) . '@example.com',
            'phone' => '08022223333',
            'cargo_description' => 'One pallet of frozen vegetables',
            'weight' => 250,
            'origin' => 'Lagos, Nigeria',
            'destination' => 'London, UK',
        ]);

        $response->assertStatus(201);

        $frozenCargo = FrozenCargo::with('items')
            ->where('request_id', $response->json('frozen_cargo.request_id'))
            ->first();

        $this->assertCount(1, $frozenCargo->items);
        $this->assertEquals('One pallet of frozen vegetables', $frozenCargo->items[0]->description);
        $this->assertEquals(250, $frozenCargo->weight);
    }

    public function test_customer_cannot_set_rate_or_cost_on_items()
    {
        Mail::fake();

        $response = $this->postJson('/api/frozen-cargos', [
            'name' => 'Guest Customer',
            'email' => 'guest.pricing.' . rand(1000, 9999) . '@example.com',
            'phone' => '08033334444',
            'origin' => 'Lagos, Nigeria',
            'destination' => 'Jeddah, Saudi Arabia',
            'items' => [
                ['description' => 'Frozen beef', 'quantity' => 2, 'weight' => 40, 'rate' => 9999, 'cost' => 8888],
            ],
        ]);

        $response->assertStatus(201);

        $item = FrozenCargoItem::whereHas('frozenCargo', function ($q) use ($response) {
            $q->where('request_id', $response->json('frozen_cargo.request_id'));
        })->first();

        $this->assertNotNull($item);
        $this->assertNull($item->rate);
        $this->assertNull($item->cost);
    }

    public function test_frozen_cargo_submission_requires_at_least_one_item()
    {
        $response = $this->postJson('/api/frozen-cargos', [
            'name' => 'Guest Customer',
            'email' => 'guest.noitems.' . rand(1000, 9999) . '@example.com',
            'phone' => '08055556666',
            'origin' => 'Lagos, Nigeria',
            'destination' => 'Dubai, UAE',
            'items' => [],
        ]);

        $response->assertStatus(422);
        $this->assertDatabaseCount('frozen_cargos', 0);
    }

    public function test_admin_can_create_frozen_cargo_with_priced_items()
    {
        Mail::fake();
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/admin/frozen-cargos', $this->adminPayload());

        $response->assertStatus(201);

        $frozenCargo = FrozenCargo::with('items')
            ->where('request_id', $response->json('frozen_cargo.request_id'))
            ->first();

        $this->assertNotNull($frozenCargo);
        $this->assertCount(2, $frozenCargo->items);
        $this->assertEquals(1200.0, (float) $frozenCargo->items[0]->rate);
        $this->assertEquals(12000.0, (float) $frozenCargo->items[0]->cost);
        // total = 12000 + 4500
        $this->assertEquals(16500.0, (float) $frozenCargo->total_cost);
    }

    public function test_admin_can_replace_all_frozen_cargo_items()
    {
        Mail::fake();
        $admin = User::factory()->create(['role' => 'admin']);

        $createResponse = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/admin/frozen-cargos', $this->adminPayload());
        $createResponse->assertStatus(201);

        $frozenCargo = FrozenCargo::where('request_id', $createResponse->json('frozen_cargo.request_id'))->first();
        $this->assertEquals(2, $frozenCargo->items()->count());

        // Remove one item and add a new one through the dedicated items endpoint.
        $updateResponse = $this->actingAs($admin, 'sanctum')
            ->putJson("/api/admin/frozen-cargos/{$frozenCargo->id}/items", [
                'items' => [
                    ['description' => 'Frozen fish', 'quantity' => 5, 'weight' => 75, 'rate' => 900, 'cost' => 4500],
                    ['description' => 'Frozen turkey', 'quantity' => 3, 'weight' => 30, 'rate' => 2500, 'cost' => 7500],
                ],
            ]);

        $updateResponse->assertStatus(200);

        $frozenCargo->refresh();
        $descriptions = $frozenCargo->items()->pluck('description')->all();
        $this->assertEquals(['Frozen fish', 'Frozen turkey'], $descriptions);
        $this->assertEquals(12000.0, (float) $frozenCargo->total_cost);
    }

    public function test_admin_can_add_and_remove_items_through_full_update()
    {
        Mail::fake();
        $admin = User::factory()->create(['role' => 'admin']);

        $createResponse = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/admin/frozen-cargos', $this->adminPayload());
        $createResponse->assertStatus(201);

        $frozenCargo = FrozenCargo::where('request_id', $createResponse->json('frozen_cargo.request_id'))->first();

        $updateResponse = $this->actingAs($admin, 'sanctum')
            ->putJson("/api/admin/frozen-cargos/{$frozenCargo->id}", [
                'status' => 'processing',
                'items' => [
                    [
                        'description' => 'Frozen shrimp',
                        'quantity' => 4,
                        'weight' => 20,
                        'rate' => 5000,
                        'cost' => 20000,
                    ],
                ],
            ]);

        $updateResponse->assertStatus(200);

        $frozenCargo->refresh();
        $this->assertCount(1, $frozenCargo->items);
        $this->assertEquals('Frozen shrimp', $frozenCargo->items->first()->description);
        $this->assertEquals('Frozen shrimp', $frozenCargo->cargo_description);
        $this->assertEquals(20000.0, (float) $frozenCargo->total_cost);
        // Header weight is the summed item weight.
        $this->assertEquals(20, (float) $frozenCargo->weight);
        $this->assertEquals('processing', $frozenCargo->status);
    }

    public function test_frozen_cargo_details_endpoint_returns_items_with_pricing()
    {
        Mail::fake();
        $admin = User::factory()->create(['role' => 'admin']);

        $createResponse = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/admin/frozen-cargos', $this->adminPayload());
        $createResponse->assertStatus(201);

        $frozenCargo = FrozenCargo::where('request_id', $createResponse->json('frozen_cargo.request_id'))->first();

        $showResponse = $this->actingAs($admin, 'sanctum')
            ->getJson("/api/frozen-cargos/{$frozenCargo->request_id}");

        $showResponse->assertStatus(200);
        $showResponse->assertJsonCount(2, 'items');
        $showResponse->assertJsonPath('items.0.description', 'Frozen chicken');
        $showResponse->assertJsonPath('items.0.quantity', 10);
    }

    public function test_admin_index_lists_items_for_each_frozen_cargo()
    {
        Mail::fake();
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/admin/frozen-cargos', $this->adminPayload())
            ->assertStatus(201);

        $indexResponse = $this->actingAs($admin, 'sanctum')->getJson('/api/admin/frozen-cargos');

        $indexResponse->assertStatus(200);
        $indexResponse->assertJsonCount(1);
        $indexResponse->assertJsonCount(2, '0.items');
    }

    public function test_admin_items_endpoint_recalculates_totals_with_rate_and_cost_fallback()
    {
        Mail::fake();
        $admin = User::factory()->create(['role' => 'admin']);

        $frozenCargo = FrozenCargo::create([
            'request_id' => 'RQST9988776',
            'user_id' => null,
            'name' => 'Totals Client',
            'email' => 'totals.client@example.com',
            'phone' => '08099998888',
            'origin' => 'Lagos, Nigeria',
            'destination' => 'London, UK',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->putJson("/api/admin/frozen-cargos/{$frozenCargo->request_id}/items", [
                'items' => [
                    // No cost: falls back to quantity * rate.
                    ['description' => 'Frozen turkey', 'quantity' => 4, 'weight' => 60, 'rate' => 2500],
                    // Explicit cost wins over quantity * rate.
                    ['description' => 'Frozen berries', 'quantity' => 6, 'weight' => 12, 'rate' => 800, 'cost' => 4800],
                ],
            ]);

        $response->assertStatus(200);
        $response->assertJsonPath('frozen_cargo.items.0.description', 'Frozen turkey');

        $frozenCargo->refresh();
        $this->assertCount(2, $frozenCargo->items);
        // 4 * 2500 + 4800
        $this->assertEquals(14800, (float) $frozenCargo->total_cost);
        // Header weight is the summed item weight.
        $this->assertEquals(72, (float) $frozenCargo->weight);
    }

    public function test_items_endpoint_requires_at_least_one_item()
    {
        Mail::fake();
        $admin = User::factory()->create(['role' => 'admin']);

        $createResponse = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/admin/frozen-cargos', $this->adminPayload([
                'items' => [
                    ['description' => 'Keep me', 'quantity' => 1, 'weight' => 5, 'rate' => 100],
                ],
            ]));
        $createResponse->assertStatus(201);

        $frozenCargo = FrozenCargo::where('request_id', $createResponse->json('frozen_cargo.request_id'))->first();

        $response = $this->actingAs($admin, 'sanctum')
            ->putJson("/api/admin/frozen-cargos/{$frozenCargo->id}/items", ['items' => []]);

        $response->assertStatus(422);
        $this->assertEquals(1, FrozenCargoItem::where('frozen_cargo_id', $frozenCargo->id)->count());
    }

    public function test_regular_user_cannot_edit_frozen_cargo_items_or_pricing()
    {
        Mail::fake();
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'user']);

        $create = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/admin/frozen-cargos', $this->adminPayload());
        $create->assertStatus(201);
        $cargoId = $create->json('frozen_cargo.id');

        // Both admin item write paths are admin-only.
        $this->actingAs($user, 'sanctum')->putJson("/api/admin/frozen-cargos/{$cargoId}", [
            'items' => [['description' => 'Tampered', 'quantity' => 1, 'rate' => 1, 'cost' => 1]],
        ])->assertStatus(403);

        $this->actingAs($user, 'sanctum')->putJson("/api/admin/frozen-cargos/{$cargoId}/items", [
            'items' => [['description' => 'Tampered', 'quantity' => 1, 'rate' => 1, 'cost' => 1]],
        ])->assertStatus(403);

        $unchanged = FrozenCargo::with('items')->find($cargoId);
        $this->assertSame('Frozen chicken', $unchanged->items->first()->description);
        $this->assertEquals(16500.0, (float) $unchanged->total_cost);
    }

    public function test_owner_can_view_their_frozen_cargo_items_with_pricing()
    {
        Mail::fake();
        $user = User::factory()->create();

        $frozenCargo = FrozenCargo::create([
            'request_id' => 'RQST1111222',
            'user_id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => '08055556666',
            'origin' => 'Lagos, Nigeria',
            'destination' => 'London, UK',
            'status' => 'pending',
        ]);

        FrozenCargoItem::create([
            'frozen_cargo_id' => $frozenCargo->id,
            'description' => 'Frozen mackerel',
            'quantity' => 7,
            'weight' => 35,
            'rate' => 900,
            'cost' => 6300,
        ]);

        $response = $this->actingAs($user, 'sanctum')->getJson("/api/frozen-cargos/{$frozenCargo->request_id}");

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'items');
        $response->assertJsonPath('items.0.description', 'Frozen mackerel');
        $response->assertJsonPath('items.0.quantity', 7);
        $response->assertJsonPath('items.0.rate', 900);
        $response->assertJsonPath('items.0.cost', 6300);
    }

    public function test_owner_cannot_download_invoice_before_admin_generates_it()
    {
        Mail::fake();
        $user = User::factory()->create();

        $frozenCargo = FrozenCargo::create([
            'request_id' => 'RQST3333444',
            'user_id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => '08077778888',
            'origin' => 'Lagos, Nigeria',
            'destination' => 'London, UK',
            'status' => 'pending',
            'invoice_generated' => false,
        ]);

        FrozenCargoItem::create([
            'frozen_cargo_id' => $frozenCargo->id,
            'description' => 'Frozen chicken',
            'quantity' => 4,
            'weight' => 100,
            'rate' => 1200,
            'cost' => 4800,
        ]);

        // Before generation: owner download is blocked and the flag is off.
        $this->assertFalse((bool) $frozenCargo->fresh()->invoice_generated);

        $blocked = $this->actingAs($user, 'sanctum')
            ->getJson("/api/frozen-cargos/{$frozenCargo->request_id}/invoice");
        $blocked->assertStatus(403);
        $blocked->assertJsonPath('message', 'Invoice has not been generated by admin yet.');

        // Admin generates the invoice.
        $admin = User::factory()->create(['role' => 'admin']);
        $generate = $this->actingAs($admin, 'sanctum')
            ->postJson("/api/admin/frozen-cargos/{$frozenCargo->id}/generate-invoice");
        $generate->assertStatus(200);

        $this->assertTrue((bool) $frozenCargo->fresh()->invoice_generated);

        // The details endpoint now reports availability for the client-side button.
        $details = $this->actingAs($user, 'sanctum')
            ->getJson("/api/frozen-cargos/{$frozenCargo->request_id}");
        $details->assertStatus(200);
        $details->assertJsonPath('invoice_generated', true);

        // After generation the owner can download the PDF.
        $allowed = $this->actingAs($user, 'sanctum')
            ->getJson("/api/frozen-cargos/{$frozenCargo->request_id}/invoice");
        $allowed->assertStatus(200);
    }

    public function test_authenticated_submission_attaches_request_to_current_user_without_identity_fields()
    {
        Mail::fake();
        $user = User::factory()->create([
            'name' => 'Account Holder',
            'phone' => '08123456789',
        ]);

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/frozen-cargos', [
            'temperature_requirement' => 'Frozen (-18°C)',
            'origin' => 'Lagos, Nigeria',
            'destination' => 'London, UK',
            'items' => [
                ['description' => 'Frozen chicken', 'quantity' => 3, 'weight' => 50],
            ],
        ]);

        $response->assertStatus(201);

        $frozenCargo = FrozenCargo::where('request_id', $response->json('frozen_cargo.request_id'))->first();

        $this->assertNotNull($frozenCargo);
        $this->assertEquals($user->id, $frozenCargo->user_id);
        $this->assertEquals('Account Holder', $frozenCargo->name);
        $this->assertEquals($user->email, $frozenCargo->email);
        $this->assertEquals('08123456789', $frozenCargo->phone);
    }

    public function test_guest_submission_still_requires_identity_fields()
    {
        $response = $this->postJson('/api/frozen-cargos', [
            'origin' => 'Lagos, Nigeria',
            'destination' => 'Accra, Ghana',
            'items' => [
                ['description' => 'Frozen chicken', 'quantity' => 1, 'weight' => 10],
            ],
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['name', 'email', 'phone']);
        $this->assertDatabaseCount('frozen_cargos', 0);
    }
}
