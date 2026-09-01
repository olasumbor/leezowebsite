<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Procurement;
use App\Models\Shipment;
use App\Models\Quote;
use App\Models\ContactMessage;
use App\Models\NewsletterSubscriber;
use App\Models\PickupDelivery;
use App\Models\FrozenCargo;
use Illuminate\Support\Facades\Mail;
use App\Mail\WelcomeMail;
use App\Mail\ProcurementCreatedMail;
use App\Mail\ProcurementStatusUpdatedMail;
use App\Mail\ShipmentCreatedMail;
use App\Mail\ShipmentStatusUpdatedMail;
use App\Mail\QuoteSubmittedMail;
use App\Mail\QuoteRateUpdatedMail;
use App\Mail\ContactAcknowledgmentMail;
use App\Mail\ContactAdminNotificationMail;
use App\Mail\NewsletterSubscribedMail;
use App\Mail\PickupDeliveryCreatedMail;
use App\Mail\FrozenCargoCreatedMail;

class EmailSystemTest extends TestCase
{
    public function test_welcome_email_is_sent_on_registration()
    {
        Mail::fake();

        $response = $this->postJson('/api/register', [
            'name' => 'Email Test User',
            'email' => 'testuser' . time() . '@example.com',
            'phone' => '1234567890',
            'password' => 'password123',
        ]);

        $response->assertStatus(201);
        Mail::assertSent(WelcomeMail::class);
    }

    public function test_procurement_emails_are_sent()
    {
        Mail::fake();
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/procurements', [
            'name' => 'John Procure',
            'email' => 'john.procure@example.com',
            'phone' => '08012345678',
            'details' => 'Bulk organic food items',
        ]);

        $response->assertStatus(201);
        Mail::assertSent(ProcurementCreatedMail::class, function ($mail) {
            return $mail->hasTo('john.procure@example.com');
        });

        $procurementId = $response->json('procurement.id');

        $admin = User::factory()->create(['role' => 'admin']);
        $updateResp = $this->actingAs($admin, 'sanctum')->putJson("/api/admin/procurements/{$procurementId}/status", [
            'status' => 'processing'
        ]);

        $updateResp->assertStatus(200);
        Mail::assertSent(ProcurementStatusUpdatedMail::class);
    }

    public function test_shipment_emails_are_sent()
    {
        Mail::fake();
        $user = User::factory()->create(['email' => 'shipment.user' . rand(1000, 99999) . '@example.com']);
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin, 'sanctum')->postJson('/api/admin/shipments', [
            'user_id' => $user->id,
            'origin' => 'Lagos, Nigeria',
            'destination' => 'London, UK',
            'service' => 'Air Freight',
            'weight' => 50,
            'packages' => 2,
            'recipient' => 'Jane Receiver',
        ]);

        $response->assertStatus(201);
        Mail::assertSent(ShipmentCreatedMail::class, function ($mail) use ($user) {
            return $mail->hasTo($user->email);
        });

        $shipmentId = $response->json('shipment.id');
        $eventResp = $this->actingAs($admin, 'sanctum')->postJson("/api/admin/shipments/{$shipmentId}/events", [
            'location' => 'Heathrow Airport',
            'description' => 'Cargo arrived at customs depot',
        ]);

        $eventResp->assertStatus(200);
        Mail::assertSent(ShipmentStatusUpdatedMail::class);
    }

    public function test_quote_emails_are_sent()
    {
        Mail::fake();

        $response = $this->postJson('/api/quotes/calculate', [
            'name' => 'Quote Client',
            'email' => 'quote.client@example.com',
            'phone' => '08098765432',
            'shippingType' => 'air',
            'originCountry' => 'Nigeria',
            'destinationCountry' => 'USA',
            'shippingWeight' => 10,
            'shippingWidth' => 20,
            'shippingLength' => 20,
            'shippingDetails' => 'Frozen fish boxes',
        ]);

        $response->assertStatus(201);
        Mail::assertSent(QuoteSubmittedMail::class);

        $quoteId = $response->json('quote_id');
        $admin = User::factory()->create(['role' => 'admin']);

        $rateResp = $this->actingAs($admin, 'sanctum')->putJson("/api/admin/quotes/{$quoteId}/cost", [
            'calculated_cost' => 450.00,
            'status' => 'quoted'
        ]);

        $rateResp->assertStatus(200);
        Mail::assertSent(QuoteRateUpdatedMail::class);
    }

    public function test_contact_and_newsletter_emails_are_sent()
    {
        Mail::fake();

        $contactResp = $this->postJson('/api/contact', [
            'name' => 'Contact Client',
            'email' => 'contact.client@example.com',
            'phone' => '08011112222',
            'subject' => 'Freight Inquiry',
            'message' => 'Hello, I need info about export packaging.',
        ]);

        $contactResp->assertStatus(201);
        Mail::assertSent(ContactAcknowledgmentMail::class);
        Mail::assertSent(ContactAdminNotificationMail::class);

        $newsResp = $this->postJson('/api/newsletter/subscribe', [
            'email' => 'subscriber' . time() . '@example.com',
        ]);

        $newsResp->assertStatus(201);
        Mail::assertSent(NewsletterSubscribedMail::class);
    }
}
