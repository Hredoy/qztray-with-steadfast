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

        $customer = Customer::where('phone', '01711111111')->firstOrFail();

        $response->assertRedirectToRoute('customers.show', $customer);
        $response->assertSessionHas('success', 'Customer created successfully.');
        $this->assertDatabaseHas('customers', [
            'name' => 'Rahim Uddin',
            'phone' => '01711111111',
        ]);

        $this->assertDatabaseHas('customer_addresses', [
            'customer_id' => $customer->id,
            'address' => 'House 10, Road 1, Dhaka',
        ]);
        $this->assertDatabaseHas('customer_addresses', [
            'customer_id' => $customer->id,
            'address' => 'Shop 2, New Market, Dhaka',
        ]);
    }

    public function test_authenticated_user_can_visit_customer_pages(): void
    {
        $user = User::factory()->create();
        $customer = Customer::create(['name' => 'Rahim Uddin', 'phone' => '01711111111']);
        $customer->addresses()->create(['address' => 'House 10, Road 1, Dhaka']);

        $this->actingAs($user)->get(route('customers.index'))->assertOk();
        $this->actingAs($user)->get(route('customers.create'))->assertOk();
        $this->actingAs($user)->get(route('customers.show', $customer))->assertOk();
        $this->actingAs($user)->get(route('customers.edit', $customer))->assertOk();
    }

    public function test_authenticated_user_can_update_customer_and_replace_addresses(): void
    {
        $user = User::factory()->create();
        $customer = Customer::create(['name' => 'Rahim Uddin', 'phone' => '01711111111']);
        $customer->addresses()->create(['address' => 'Old Address']);

        $response = $this->actingAs($user)->put(route('customers.update', $customer), [
            'name' => 'Karim Uddin',
            'phone' => '01822222222',
            'addresses' => [
                ['address' => 'House 20, Road 2, Dhaka'],
                ['address' => 'Shop 5, Chittagong'],
            ],
        ]);

        $response->assertRedirectToRoute('customers.show', $customer);
        $this->assertDatabaseHas('customers', [
            'id' => $customer->id,
            'name' => 'Karim Uddin',
            'phone' => '01822222222',
        ]);
        $this->assertDatabaseMissing('customer_addresses', [
            'customer_id' => $customer->id,
            'address' => 'Old Address',
        ]);
        $this->assertDatabaseHas('customer_addresses', [
            'customer_id' => $customer->id,
            'address' => 'House 20, Road 2, Dhaka',
        ]);
        $this->assertDatabaseHas('customer_addresses', [
            'customer_id' => $customer->id,
            'address' => 'Shop 5, Chittagong',
        ]);
    }
}
