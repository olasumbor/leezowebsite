<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Procurement;
use App\Models\Shipment;
use App\Models\PickupDelivery;
use App\Models\FrozenCargo;
use App\Models\Quote;

class AdminOverviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_fetch_overview_metrics_and_revenue()
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        Procurement::create([
            'procurement_id' => 'PR12345678',
            'user_id' => $admin->id,
            'name' => 'John',
            'email' => 'john@example.com',
            'phone' => '0800000000',
            'details' => 'Items',
            'cost' => 5000,
        ]);

        Shipment::create([
            'user_id' => $admin->id,
            'tracking_id' => 'TRK12345',
            'origin' => 'Lagos',
            'destination' => 'London',
            'status' => 'pending',
            'shipping_cost' => 3000,
        ]);

        PickupDelivery::create([
            'request_id' => 'PKD-12345',
            'user_id' => $admin->id,
            'name' => 'John',
            'email' => 'john@example.com',
            'phone' => '0800000000',
            'pickup_address' => 'Addr A',
            'delivery_address' => 'Addr B',
            'cost' => 2000,
        ]);
        FrozenCargo::create([
            'request_id' => 'FRZ-12345',
            'user_id' => $admin->id,
            'name' => 'John',
            'email' => 'john@example.com',
            'phone' => '0800000000',
            'cargo_description' => 'Frozen Fish',
            'origin' => 'Lagos',
            'destination' => 'Abuja',
            'cost' => 4000,
        ]);

        Quote::create([
            'name' => 'Jane',
            'email' => 'jane@example.com',
            'phone' => '0800000000',
            'origin_country' => 'NG',
            'destination_country' => 'UK',
            'shipping_type' => 'Air Freight',
            'weight' => 10.0,
            'width' => 10.0,
            'length' => 10.0,
            'shipping_details' => 'Test quote details',
            'calculated_cost' => 1000,
        ]);




        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/admin/overview');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'users_count',
                'procurements_count',
                'shipments_count',
                'pickups_count',
                'frozen_count',
                'quotes_count',
                'total_revenue',
                'activity'
            ])
            ->assertJson([
                'users_count' => 1,
                'procurements_count' => 1,
                'shipments_count' => 1,
                'pickups_count' => 1,
                'frozen_count' => 1,
                'quotes_count' => 1,
                'total_revenue' => 15000,
            ]);
    }
}
