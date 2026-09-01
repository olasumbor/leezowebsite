<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Shipment;
use App\Models\Quote;
use Illuminate\Support\Facades\Mail;
use App\Mail\QuoteSubmittedMail;

class QuoteSubmissionTest extends TestCase
{
    public function test_guest_user_can_submit_quote_and_creates_unlinked_shipment()
    {
        Mail::fake();

        $response = $this->postJson('/api/quotes/calculate', [
            'name' => 'Guest User Quote',
            'email' => 'guest.quote.' . rand(1000, 9999) . '@example.com',
            'phone' => '08012345678',
            'shippingType' => 'Air Freight',
            'originCountry' => 'Nigeria',
            'destinationCountry' => 'Canada',
            'shippingWeight' => 15,
            'shippingWidth' => 25,
            'shippingLength' => 25,
            'shippingDetails' => 'Traditional food items',
        ]);

        $response->assertStatus(201);
        $response->assertJsonStructure(['message', 'tracking_id', 'quote_id']);

        $trackingId = $response->json('tracking_id');

        // Verify Shipment was created with null user_id
        $shipment = Shipment::where('tracking_id', $trackingId)->first();
        $this->assertNotNull($shipment);
        $this->assertNull($shipment->user_id);
        $this->assertEquals('Guest User Quote', $shipment->recipient_name);

        // Verify tracking endpoint is publicly accessible
        $trackResp = $this->getJson("/api/track/{$trackingId}");
        $trackResp->assertStatus(200);
        $trackResp->assertJsonPath('tracking_id', $trackingId);
    }

    public function test_logged_in_user_can_submit_quote_and_creates_user_linked_shipment()
    {
        Mail::fake();
        $user = User::factory()->create(['email' => 'auth.quote.' . rand(1000, 9999) . '@example.com']);

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/quotes/calculate', [
            'name' => $user->name,
            'email' => $user->email,
            'phone' => '08099998888',
            'shippingType' => 'Sea Freight',
            'originCountry' => 'Nigeria',
            'destinationCountry' => 'UK',
            'shippingWeight' => 50,
            'shippingWidth' => 40,
            'shippingLength' => 40,
            'shippingDetails' => 'Commercial shipment box',
        ]);

        $response->assertStatus(201);
        $trackingId = $response->json('tracking_id');

        // Verify Shipment was created with user_id = $user->id
        $shipment = Shipment::where('tracking_id', $trackingId)->first();
        $this->assertNotNull($shipment);
        $this->assertEquals($user->id, $shipment->user_id);

        // Verify user can fetch it in their shipment list
        $listResp = $this->actingAs($user, 'sanctum')->getJson('/api/shipments');
        $listResp->assertStatus(200);
        $this->assertTrue(collect($listResp->json())->contains('tracking_id', $trackingId));
    }
}
