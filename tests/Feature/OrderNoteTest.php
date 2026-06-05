<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
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

        $note = \App\Models\OrderNote::where('phone', '01711111111')->firstOrFail();

        $response->assertRedirectToRoute('order-notes.show', $note);
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

    public function test_authenticated_user_can_visit_order_note_pages(): void
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

        $this->actingAs($user)->get(route('order-notes.index'))->assertOk();
        $this->actingAs($user)->get(route('order-notes.create'))->assertOk();
        $this->actingAs($user)->get(route('order-notes.show', $note))->assertOk();
        $this->actingAs($user)->get(route('order-notes.edit', $note))->assertOk();
    }

    public function test_authenticated_user_can_update_order_note_and_recalculate_total(): void
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

        $response = $this->actingAs($user)->put(route('order-notes.update', $note), [
            'customer_id' => $customer->id,
            'name' => 'Karim Uddin',
            'phone' => '01822222222',
            'address' => 'House 20, Road 2, Dhaka',
            'product_list' => "Shoe x 1\nBag x 1",
            'paid' => 2000,
            'due' => 120,
        ]);

        $response->assertRedirectToRoute('order-notes.show', $note);
        $this->assertDatabaseHas('order_notes', [
            'id' => $note->id,
            'name' => 'Karim Uddin',
            'phone' => '01822222222',
            'address' => 'House 20, Road 2, Dhaka',
            'paid' => 2000,
            'due' => 120,
            'total' => 2120,
        ]);
        $this->assertDatabaseHas('customer_addresses', [
            'customer_id' => $customer->id,
            'address' => 'House 20, Road 2, Dhaka',
        ]);
    }

    public function test_invoice_show_includes_linked_customer_and_order_note(): void
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
        $invoice = Invoice::create([
            'customer_id' => $customer->id,
            'order_note_id' => $note->id,
            'invoice_id' => 'SI-2606050001',
            'wgt' => '0.5',
            'name' => 'Rahim Uddin',
            'phone' => '01711111111',
            'address' => 'House 10, Road 1, Dhaka',
            'delivery_type' => 2,
            'cod' => '80.00',
            'total' => '1280.00',
            'instruction' => 'Shirt x 2',
        ]);
        $note->update(['invoice_id' => $invoice->id]);

        $this->actingAs($user)
            ->get(route('invoices.show', $invoice))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('invoices/Show')
                ->where('invoice.customer.id', $customer->id)
                ->where('invoice.order_note.id', $note->id)
            );
    }
}
