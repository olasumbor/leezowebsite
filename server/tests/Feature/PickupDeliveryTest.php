<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\PickupDelivery;
use App\Models\PickupDeliveryItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use App\Mail\PickupDeliveryCreatedMail;

class PickupDeliveryTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_user_can_submit_pickup_delivery_without_item_description()
    {
        Mail::fake();

        $response = $this->postJson('/api/pickup-deliveries', [
            'name' => 'John Doe',
            'email' => 'john.pickup.' . rand(1000, 9999) . '@example.com',
            'phone' => '08012345678',
            'pickup_address' => '123 Main Street, Ikeja, Lagos',
            'delivery_address' => '456 Commercial Avenue, Yaba, Lagos',
            'delivery_phone' => '08087654321',
        ]);

        $response->assertStatus(201);
        $response->assertJsonStructure([
            'message',
            'pickup_delivery' => [
                'id',
                'request_id',
                'name',
                'email',
                'phone',
                'pickup_address',
                'delivery_address',
                'delivery_phone',
                'status',
            ]
        ]);

        $requestId = $response->json('pickup_delivery.request_id');

        $pickupDelivery = PickupDelivery::where('request_id', $requestId)->first();
        $this->assertNotNull($pickupDelivery);
        $this->assertEquals('John Doe', $pickupDelivery->name);
        $this->assertEquals('123 Main Street, Ikeja, Lagos', $pickupDelivery->pickup_address);
        $this->assertEquals('456 Commercial Avenue, Yaba, Lagos', $pickupDelivery->delivery_address);

        Mail::assertSent(PickupDeliveryCreatedMail::class);
    }

    public function test_logged_in_user_can_submit_pickup_delivery_without_sending_identity_fields()
    {
        Mail::fake();
        $user = User::factory()->create([
            'email' => 'user.pickup.' . rand(1000, 9999) . '@example.com',
            'phone' => '08033334444',
        ]);

        // The dashboard form no longer sends sender name/email/phone:
        // they are derived from the logged-in account.
        $response = $this->actingAs($user, 'sanctum')->postJson('/api/pickup-deliveries', [
            'pickup_address' => '78 Allen Avenue, Ikeja, Lagos',
            'delivery_address' => '12 Marina Road, Lagos Island',
        ]);

        $response->assertStatus(201);
        $requestId = $response->json('pickup_delivery.request_id');

        $pickupDelivery = PickupDelivery::where('request_id', $requestId)->first();
        $this->assertNotNull($pickupDelivery);
        $this->assertEquals($user->id, $pickupDelivery->user_id);
        $this->assertEquals($user->name, $pickupDelivery->name);
        $this->assertEquals($user->email, $pickupDelivery->email);
        $this->assertEquals('08033334444', $pickupDelivery->phone);
    }

    public function test_admin_can_store_pickup_delivery_with_multiple_items()
    {
        Mail::fake();
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin, 'sanctum')->postJson('/api/admin/pickup-deliveries', [
            'name' => 'Admin Created User',
            'email' => 'admin.created.' . rand(1000, 9999) . '@example.com',
            'phone' => '08099990000',
            'pickup_address' => 'Admin Pickup Hub, Maryland',
            'delivery_address' => 'Customer Address, Victoria Island',
            'items' => [
                ['description' => 'Pick up and delivery', 'cost' => 10000],
                ['description' => 'Insurance cover', 'cost' => 2500],
            ],
        ]);

        $response->assertStatus(201);
        $requestId = $response->json('pickup_delivery.request_id');

        $pickupDelivery = PickupDelivery::with('items')->where('request_id', $requestId)->first();
        $this->assertNotNull($pickupDelivery);
        $this->assertCount(2, $pickupDelivery->items);
        $this->assertEquals('Pick up and delivery', $pickupDelivery->items[0]->description);
        $this->assertEquals('Insurance cover', $pickupDelivery->items[1]->description);
        // Header total and cost stay in sync with the item costs.
        $this->assertEquals(12500, (float) $pickupDelivery->total_cost);
        $this->assertEquals(12500, (float) $pickupDelivery->cost);
    }

    public function test_admin_can_store_pickup_delivery_from_selected_user_account()
    {
        Mail::fake();
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = User::factory()->create([
            'email' => 'account.sender.' . rand(1000, 9999) . '@example.com',
            'phone' => '08077776666',
        ]);

        // No name/email/phone in the payload: they come from the selected user.
        $response = $this->actingAs($admin, 'sanctum')->postJson('/api/admin/pickup-deliveries', [
            'user_id' => $customer->id,
            'pickup_address' => 'Admin Pickup Hub, Maryland',
            'delivery_address' => 'Customer Address, Victoria Island',
            'items' => [
                ['description' => 'Pick up and delivery', 'cost' => 8000],
            ],
        ]);

        $response->assertStatus(201);

        $pickupDelivery = PickupDelivery::with('items')
            ->where('request_id', $response->json('pickup_delivery.request_id'))
            ->first();

        $this->assertNotNull($pickupDelivery);
        $this->assertEquals($customer->id, $pickupDelivery->user_id);
        $this->assertEquals($customer->name, $pickupDelivery->name);
        $this->assertEquals($customer->email, $pickupDelivery->email);
        $this->assertEquals('08077776666', $pickupDelivery->phone);
        $this->assertEquals(8000, (float) $pickupDelivery->total_cost);
    }

    public function test_admin_can_store_pickup_delivery_without_item_description()
    {
        Mail::fake();
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin, 'sanctum')->postJson('/api/admin/pickup-deliveries', [
            'name' => 'Admin Created User',
            'email' => 'admin.legacy.' . rand(1000, 9999) . '@example.com',
            'phone' => '08099990000',
            'pickup_address' => 'Admin Pickup Hub, Maryland',
            'delivery_address' => 'Customer Address, Victoria Island',
            'cost' => 15000,
        ]);

        $response->assertStatus(201);
        $requestId = $response->json('pickup_delivery.request_id');

        $pickupDelivery = PickupDelivery::with('items')->where('request_id', $requestId)->first();
        $this->assertNotNull($pickupDelivery);
        $this->assertEquals(15000, (float) $pickupDelivery->cost);
        // A legacy header-level cost becomes a single item row.
        $this->assertCount(1, $pickupDelivery->items);
        $this->assertEquals(15000, (float) $pickupDelivery->items[0]->cost);
    }

    public function test_admin_can_update_pickup_delivery_items_and_recalculate_total()
    {
        Mail::fake();
        $admin = User::factory()->create(['role' => 'admin']);

        $pickup = PickupDelivery::create([
            'request_id' => 'PKD' . mt_rand(10000000, 99999999),
            'user_id' => null,
            'name' => 'Update Customer',
            'email' => 'update.customer.' . rand(1000, 9999) . '@example.com',
            'phone' => '08011112222',
            'pickup_address' => 'Origin Address',
            'delivery_address' => 'Destination Address',
            'status' => 'pending',
            'cost' => 5000,
        ]);
        PickupDeliveryItem::create([
            'pickup_delivery_id' => $pickup->id,
            'description' => 'Old item',
            'cost' => 5000,
        ]);
        $pickup->calculateAndUpdateTotals();

        $response = $this->actingAs($admin, 'sanctum')->putJson("/api/admin/pickup-deliveries/{$pickup->id}", [
            'status' => 'processing',
            'items' => [
                ['description' => 'Pick up and delivery', 'cost' => 7000],
                ['description' => 'Extra handling', 'cost' => 3000],
            ],
        ]);

        $response->assertStatus(200);

        $pickup->refresh()->load('items');
        $this->assertCount(2, $pickup->items);
        $this->assertEquals('processing', $pickup->status);
        $this->assertEquals(10000, (float) $pickup->total_cost);
        $this->assertEquals(10000, (float) $pickup->cost);
    }

    public function test_pickup_delivery_validation_does_not_require_item_description()
    {
        // Leaving out item_description should succeed when required fields are present
        $response = $this->postJson('/api/pickup-deliveries', [
            'name' => 'Valid Name',
            'email' => 'valid.email.' . rand(1000, 9999) . '@example.com',
            'phone' => '08000000000',
            'pickup_address' => 'Origin Address',
            'delivery_address' => 'Destination Address',
        ]);

        $response->assertStatus(201);
        $response->assertJsonMissingPath('errors.item_description');
    }

    public function test_guest_submission_still_requires_identity_fields()
    {
        $response = $this->postJson('/api/pickup-deliveries', [
            'pickup_address' => 'Origin Address',
            'delivery_address' => 'Destination Address',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['name', 'email', 'phone']);
    }

    public function test_user_can_download_receipt_and_invoice_for_pickup_delivery()
    {
        $user = User::factory()->create(['email' => 'download.receipt.' . rand(1000, 9999) . '@example.com']);
        $pickup = PickupDelivery::create([
            'request_id' => 'PKD' . mt_rand(10000000, 99999999),
            'user_id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => '08011112222',
            'pickup_address' => 'Origin Address',
            'delivery_address' => 'Destination Address',
            'cost' => 20000,
            'invoice_generated' => true,
        ]);
        PickupDeliveryItem::create([
            'pickup_delivery_id' => $pickup->id,
            'description' => 'Pick up and delivery',
            'cost' => 20000,
        ]);

        $receiptResponse = $this->actingAs($user, 'sanctum')->getJson("/api/pickup-deliveries/{$pickup->request_id}/receipt");
        $receiptResponse->assertStatus(200);

        $invoiceResponse = $this->actingAs($user, 'sanctum')->getJson("/api/pickup-deliveries/{$pickup->request_id}/invoice");
        $invoiceResponse->assertStatus(200);
    }
}
