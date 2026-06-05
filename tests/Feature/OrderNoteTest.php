<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderNoteTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_create_order_note_with_calculated_total(): void
    {
        $user = User::factory()->create();
        $customer = Customer::create(['name' => 'Rahim Uddin', 'phone' => '01711111111']);
        $customer->addresses()->create(['address' => 'House 10, Road 1, Dhaka']);

        $response = $this->actingAs($user)->post(route('order-notes.store'), [
            'customer_id' => $customer->id,
            'name' => 'Rahim Uddin',
            'phone' => '01711111111',
            'address' => 'House 10, Road 1, Dhaka',
            'product_list' => "Shirt x 2\nPant x 1",
            'paid' => 1200,
            'due' => 80,
        ]);

        $response->assertRedirectToRoute('dashboard');
        $this->assertDatabaseHas('order_notes', [
            'customer_id' => $customer->id,
            'phone' => '01711111111',
            'paid' => 1200,
            'due' => 80,
            'total' => 1280,
        ]);
    }

    public function test_order_note_converts_to_invoice_with_due_as_cod(): void
    {
        $user = User::factory()->create();
        $customer = Customer::create(['name' => 'Rahim Uddin', 'phone' => '01711111111']);
        $note = $customer->orderNotes()->create([
            'name' => 'Rahim Uddin',
            'phone' => '01711111111',
            'address' => 'House 10, Road 1, Dhaka',
            'product_list' => "Shirt x 2\nPant x 1",
            'paid' => 1200,
            'due' => 80,
            'total' => 1280,
        ]);

        $response = $this->actingAs($user)->post(route('order-notes.convert', $note));

        $note->refresh();

        $response->assertRedirectToRoute('invoices.show', $note->invoice_id);
        $this->assertDatabaseHas('invoices', [
            'customer_id' => $customer->id,
            'order_note_id' => $note->id,
            'name' => 'Rahim Uddin',
            'phone' => '01711111111',
            'address' => 'House 10, Road 1, Dhaka',
            'instruction' => "Shirt x 2\nPant x 1",
            'cod' => '80.00',
            'total' => '1280.00',
        ]);
        $this->assertNotNull($note->invoice_id);
    }

    public function test_converted_order_note_cannot_create_duplicate_invoice(): void
    {
        $user = User::factory()->create();
        $customer = Customer::create(['name' => 'Rahim Uddin', 'phone' => '01711111111']);
        $note = $customer->orderNotes()->create([
            'name' => 'Rahim Uddin',
            'phone' => '01711111111',
            'address' => 'House 10, Road 1, Dhaka',
            'product_list' => 'Shirt x 2',
            'paid' => 1200,
            'due' => 80,
            'total' => 1280,
        ]);

        $this->actingAs($user)->post(route('order-notes.convert', $note));
        $this->actingAs($user)->post(route('order-notes.convert', $note->fresh()));

        $this->assertDatabaseCount('invoices', 1);
    }
}
