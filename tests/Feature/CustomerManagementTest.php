<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_create_customer_with_multiple_addresses(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('customers.store'), [
            'name' => 'Rahim Uddin',
            'phone' => '01711111111',
            'addresses' => [
                ['address' => 'House 10, Road 1, Dhaka'],
                ['address' => 'Shop 2, New Market, Dhaka'],
            ],
        ]);

        $response->assertRedirect(route('dashboard'));
        $response->assertSessionHas('success', 'Customer created successfully.');
        $this->assertDatabaseHas('customers', [
            'name' => 'Rahim Uddin',
            'phone' => '01711111111',
        ]);

        $customer = Customer::where('phone', '01711111111')->firstOrFail();

        $this->assertDatabaseHas('customer_addresses', [
            'customer_id' => $customer->id,
            'address' => 'House 10, Road 1, Dhaka',
        ]);
        $this->assertDatabaseHas('customer_addresses', [
            'customer_id' => $customer->id,
            'address' => 'Shop 2, New Market, Dhaka',
        ]);
    }
}
