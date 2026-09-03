<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\PickupDelivery;
use Illuminate\Support\Facades\Mail;
use App\Mail\PickupDeliveryCreatedMail;

class PickupDeliveryTest extends TestCase
{
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

    public function test_logged_in_user_can_submit_pickup_delivery_without_item_description()
    {
        Mail::fake();
        $user = User::factory()->create(['email' => 'user.pickup.' . rand(1000, 9999) . '@example.com']);

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/pickup-deliveries', [
            'name' => $user->name,
            'email' => $user->email,
            'phone' => '08033334444',
            'pickup_address' => '78 Allen Avenue, Ikeja, Lagos',
            'delivery_address' => '12 Marina Road, Lagos Island',
        ]);

        $response->assertStatus(201);
        $requestId = $response->json('pickup_delivery.request_id');

        $pickupDelivery = PickupDelivery::where('request_id', $requestId)->first();
        $this->assertNotNull($pickupDelivery);
        $this->assertEquals($user->id, $pickupDelivery->user_id);
    }

    public function test_admin_can_store_pickup_delivery_without_item_description()
    {
        Mail::fake();
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin, 'sanctum')->postJson('/api/admin/pickup-deliveries', [
            'name' => 'Admin Created User',
            'email' => 'admin.created.' . rand(1000, 9999) . '@example.com',
            'phone' => '08099990000',
            'pickup_address' => 'Admin Pickup Hub, Maryland',
            'delivery_address' => 'Customer Address, Victoria Island',
            'cost' => 15000,
        ]);

        $response->assertStatus(201);
        $requestId = $response->json('pickup_delivery.request_id');

        $pickupDelivery = PickupDelivery::where('request_id', $requestId)->first();
        $this->assertNotNull($pickupDelivery);
        $this->assertEquals(15000, $pickupDelivery->cost);
    }

    public function test_pickup_delivery_validation_does_not_require_item_description()
    {
        // Leaving out item_description should succeed when required fields are present
        $response = $this->postJson('/api/pickup-deliveries', [
            'name' => 'Valid Name',
            'email' => 'valid.email@example.com',
            'phone' => '08000000000',
            'pickup_address' => 'Origin Address',
            'delivery_address' => 'Destination Address',
        ]);

        $response->assertStatus(201);
        $response->assertJsonMissingPath('errors.item_description');
    }
}
