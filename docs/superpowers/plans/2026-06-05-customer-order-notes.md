# Customer Order Notes Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build customer management, multiple customer addresses, order notes, and one-click order-note-to-invoice conversion with `invoice.total = paid + due` and `invoice.cod = due`.

**Architecture:** Add normalized customer, address, and order note tables while keeping existing invoice fields intact. Controllers will support manual customer selection and phone-based customer matching. Inertia/Vue pages will follow the existing invoice page style and reuse current app layout/navigation conventions.

**Tech Stack:** Laravel 12, Eloquent, Inertia, Vue 3, TypeScript, Ziggy routes, PHPUnit feature tests.

---

## File Structure

- Create `database/migrations/2026_06_05_000001_create_customers_table.php`: customer identity fields.
- Create `database/migrations/2026_06_05_000002_create_customer_addresses_table.php`: multiple single-line addresses per customer.
- Create `database/migrations/2026_06_05_000003_create_order_notes_table.php`: note data and invoice link.
- Create `database/migrations/2026_06_05_000004_add_customer_links_to_invoices_table.php`: optional invoice links.
- Create `app/Models/Customer.php`: customer relationships and spend helpers.
- Create `app/Models/CustomerAddress.php`: address relationship.
- Create `app/Models/OrderNote.php`: order note relationships, total calculation, conversion state.
- Modify `app/Models/Invoice.php`: customer and order note relationships plus fillable ids.
- Create `app/Http/Controllers/CustomerController.php`: customer CRUD and detail totals.
- Create `app/Http/Controllers/OrderNoteController.php`: order note CRUD and conversion.
- Modify `routes/web.php`: add customer and order note routes inside the auth group.
- Modify `resources/js/components/AppSidebar.vue`: add Customers and Order Notes links.
- Create customer Vue pages under `resources/js/pages/customers/`.
- Create order note Vue pages under `resources/js/pages/order-notes/`.
- Test `tests/Feature/CustomerManagementTest.php`: customer creation and detail rendering.
- Test `tests/Feature/OrderNoteTest.php`: note creation, conversion, and duplicate conversion prevention.

## Task 1: Customer Data Model

**Files:**
- Create: `database/migrations/2026_06_05_000001_create_customers_table.php`
- Create: `database/migrations/2026_06_05_000002_create_customer_addresses_table.php`
- Create: `app/Models/Customer.php`
- Create: `app/Models/CustomerAddress.php`
- Test: `tests/Feature/CustomerManagementTest.php`

- [ ] **Step 1: Write the failing customer creation test**

Create `tests/Feature/CustomerManagementTest.php` with:

```php
<?php

namespace Tests\Feature;

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

        $response->assertRedirect();
        $this->assertDatabaseHas('customers', [
            'name' => 'Rahim Uddin',
            'phone' => '01711111111',
        ]);
        $this->assertDatabaseHas('customer_addresses', [
            'address' => 'House 10, Road 1, Dhaka',
        ]);
        $this->assertDatabaseHas('customer_addresses', [
            'address' => 'Shop 2, New Market, Dhaka',
        ]);
    }
}
```

- [ ] **Step 2: Run the test to verify it fails**

Run: `php artisan test tests/Feature/CustomerManagementTest.php --filter=authenticated_user_can_create_customer_with_multiple_addresses`

Expected: FAIL because the `customers.store` route does not exist.

- [ ] **Step 3: Create migrations and models**

Create `database/migrations/2026_06_05_000001_create_customers_table.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('phone')->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
```

Create `database/migrations/2026_06_05_000002_create_customer_addresses_table.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->string('address');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_addresses');
    }
};
```

Create `app/Models/Customer.php`:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    protected $fillable = [
        'name',
        'phone',
    ];

    public function addresses(): HasMany
    {
        return $this->hasMany(CustomerAddress::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function orderNotes(): HasMany
    {
        return $this->hasMany(OrderNote::class);
    }
}
```

Create `app/Models/CustomerAddress.php`:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerAddress extends Model
{
    protected $fillable = [
        'customer_id',
        'address',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
```

- [ ] **Step 4: Add minimal customer route and controller behavior**

Create `app/Http/Controllers/CustomerController.php` with the `store` method from Task 3 Step 3 and add `Route::resource('customers', CustomerController::class);` inside the auth group in `routes/web.php`.

The initial controller code:

```php
<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:255'],
            'addresses' => ['required', 'array', 'min:1'],
            'addresses.*.address' => ['required', 'string', 'max:255'],
        ]);

        $customer = Customer::create([
            'name' => $data['name'],
            'phone' => $data['phone'],
        ]);

        foreach ($data['addresses'] as $address) {
            $customer->addresses()->create(['address' => $address['address']]);
        }

        return redirect()->route('customers.show', $customer);
    }
}
```

- [ ] **Step 5: Run the customer test to verify it passes**

Run: `php artisan test tests/Feature/CustomerManagementTest.php --filter=authenticated_user_can_create_customer_with_multiple_addresses`

Expected: PASS.

- [ ] **Step 6: Commit Task 1**

Run:

```bash
git add app/Models/Customer.php app/Models/CustomerAddress.php app/Http/Controllers/CustomerController.php database/migrations/2026_06_05_000001_create_customers_table.php database/migrations/2026_06_05_000002_create_customer_addresses_table.php routes/web.php tests/Feature/CustomerManagementTest.php
git commit -m "feat: add customer data model"
```

## Task 2: Order Notes and Invoice Links

**Files:**
- Create: `database/migrations/2026_06_05_000003_create_order_notes_table.php`
- Create: `database/migrations/2026_06_05_000004_add_customer_links_to_invoices_table.php`
- Create: `app/Models/OrderNote.php`
- Modify: `app/Models/Invoice.php`
- Create: `app/Http/Controllers/OrderNoteController.php`
- Modify: `routes/web.php`
- Test: `tests/Feature/OrderNoteTest.php`

- [ ] **Step 1: Write failing order note creation and conversion tests**

Create `tests/Feature/OrderNoteTest.php`:

```php
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

        $response->assertRedirect();
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

        $response->assertRedirect();
        $this->assertDatabaseHas('invoices', [
            'customer_id' => $customer->id,
            'order_note_id' => $note->id,
            'name' => 'Rahim Uddin',
            'phone' => '01711111111',
            'address' => 'House 10, Road 1, Dhaka',
            'instruction' => "Shirt x 2\nPant x 1",
            'cod' => '80',
            'total' => '1280',
        ]);
        $this->assertNotNull($note->fresh()->invoice_id);
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
```

- [ ] **Step 2: Run the tests to verify they fail**

Run: `php artisan test tests/Feature/OrderNoteTest.php`

Expected: FAIL because the `OrderNote` model, migrations, and routes do not exist.

- [ ] **Step 3: Create migrations and model updates**

Create `database/migrations/2026_06_05_000003_create_order_notes_table.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('invoice_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('phone');
            $table->string('address');
            $table->text('product_list');
            $table->decimal('paid', 12, 2)->default(0);
            $table->decimal('due', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_notes');
    }
};
```

Create `database/migrations/2026_06_05_000004_add_customer_links_to_invoices_table.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->foreignId('customer_id')->nullable()->after('id')->constrained()->nullOnDelete();
            $table->foreignId('order_note_id')->nullable()->after('customer_id')->constrained('order_notes')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropConstrainedForeignId('order_note_id');
            $table->dropConstrainedForeignId('customer_id');
        });
    }
};
```

Create `app/Models/OrderNote.php`:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderNote extends Model
{
    protected $fillable = [
        'customer_id',
        'invoice_id',
        'name',
        'phone',
        'address',
        'product_list',
        'paid',
        'due',
        'total',
    ];

    protected $casts = [
        'paid' => 'decimal:2',
        'due' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }
}
```

Modify `app/Models/Invoice.php`:

```php
protected $fillable = [
    'customer_id',
    'order_note_id',
    'invoice_id',
    'stead_fast_id',
    'wgt',
    'name',
    'phone',
    'address',
    'cod',
    'delivery_type',
    'total',
    'instruction',
    'notes',
];

public function customer(): \Illuminate\Database\Eloquent\Relations\BelongsTo
{
    return $this->belongsTo(Customer::class);
}

public function orderNote(): \Illuminate\Database\Eloquent\Relations\BelongsTo
{
    return $this->belongsTo(OrderNote::class);
}
```

- [ ] **Step 4: Add order note controller and routes**

Create `app/Http/Controllers/OrderNoteController.php`:

```php
<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\OrderNote;
use App\Services\IdGenerateService;
use Illuminate\Http\Request;

class OrderNoteController extends Controller
{
    public function store(Request $request)
    {
        $data = $this->validated($request);
        $customer = $this->resolveCustomer($data);

        $note = $customer->orderNotes()->create([
            'name' => $data['name'],
            'phone' => $data['phone'],
            'address' => $data['address'],
            'product_list' => $data['product_list'],
            'paid' => $data['paid'],
            'due' => $data['due'],
            'total' => $data['paid'] + $data['due'],
        ]);

        $customer->addresses()->firstOrCreate(['address' => $data['address']]);

        return redirect()->route('order-notes.show', $note);
    }

    public function convert(OrderNote $orderNote, IdGenerateService $idGenerateService)
    {
        if ($orderNote->invoice_id) {
            return redirect()->route('invoices.show', $orderNote->invoice_id);
        }

        $invoice = Invoice::create([
            'customer_id' => $orderNote->customer_id,
            'order_note_id' => $orderNote->id,
            'invoice_id' => $idGenerateService->generateNextSaleInvoiceNo(),
            'wgt' => '0.5',
            'name' => $orderNote->name,
            'phone' => $orderNote->phone,
            'address' => $orderNote->address,
            'delivery_type' => 2,
            'cod' => $orderNote->due,
            'total' => $orderNote->total,
            'instruction' => $orderNote->product_list,
            'notes' => null,
        ]);

        $orderNote->update(['invoice_id' => $invoice->id]);

        return redirect()->route('invoices.show', $invoice);
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'customer_id' => ['nullable', 'exists:customers,id'],
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:255'],
            'address' => ['required', 'string', 'max:255'],
            'product_list' => ['required', 'string', 'max:5000'],
            'paid' => ['required', 'numeric', 'min:0'],
            'due' => ['required', 'numeric', 'min:0'],
        ]);
    }

    private function resolveCustomer(array $data): Customer
    {
        if (! empty($data['customer_id'])) {
            return Customer::findOrFail($data['customer_id']);
        }

        return Customer::firstOrCreate(
            ['phone' => $data['phone']],
            ['name' => $data['name']]
        );
    }
}
```

Modify `routes/web.php` inside the auth group:

```php
Route::resource('order-notes', OrderNoteController::class);
Route::post('/order-notes/{orderNote}/convert', [OrderNoteController::class, 'convert'])
    ->name('order-notes.convert');
```

Also add `use App\Http\Controllers\OrderNoteController;` at the top.

- [ ] **Step 5: Run order note tests to verify they pass**

Run: `php artisan test tests/Feature/OrderNoteTest.php`

Expected: PASS.

- [ ] **Step 6: Commit Task 2**

Run:

```bash
git add app/Models/Invoice.php app/Models/OrderNote.php app/Http/Controllers/OrderNoteController.php database/migrations/2026_06_05_000003_create_order_notes_table.php database/migrations/2026_06_05_000004_add_customer_links_to_invoices_table.php routes/web.php tests/Feature/OrderNoteTest.php
git commit -m "feat: add order notes conversion"
```

## Task 3: Complete Customer Controller and Pages

**Files:**
- Modify: `app/Http/Controllers/CustomerController.php`
- Create: `resources/js/pages/customers/Index.vue`
- Create: `resources/js/pages/customers/Create.vue`
- Create: `resources/js/pages/customers/Edit.vue`
- Create: `resources/js/pages/customers/Show.vue`
- Create: `resources/js/pages/customers/partials/CustomerForm.vue`
- Test: `tests/Feature/CustomerManagementTest.php`

- [ ] **Step 1: Add failing customer page tests**

Append to `CustomerManagementTest`:

```php
public function test_authenticated_user_can_visit_customer_index_and_detail(): void
{
    $user = User::factory()->create();
    $customer = \App\Models\Customer::create(['name' => 'Rahim Uddin', 'phone' => '01711111111']);
    $customer->addresses()->create(['address' => 'House 10, Road 1, Dhaka']);

    $this->actingAs($user)->get(route('customers.index'))->assertOk();
    $this->actingAs($user)->get(route('customers.show', $customer))->assertOk();
}
```

- [ ] **Step 2: Run the customer page tests to verify they fail**

Run: `php artisan test tests/Feature/CustomerManagementTest.php --filter=authenticated_user_can_visit_customer_index_and_detail`

Expected: FAIL because controller methods and Vue pages are missing.

- [ ] **Step 3: Complete CustomerController**

Replace `app/Http/Controllers/CustomerController.php` with controller methods for `index`, `create`, `store`, `show`, `edit`, `update`, and `destroy`. The controller must:

- Search by `name` or `phone`.
- Load addresses on index and show.
- Calculate show totals with:
  - `$totalSpend = (float) $customer->invoices()->sum('total');`
  - `$totalPaid = (float) $customer->orderNotes()->sum('paid');`
  - `$totalDue = (float) $customer->orderNotes()->sum('due');`
- Replace addresses on update by deleting existing addresses and creating the submitted non-empty addresses.
- Redirect to `customers.show` after create/update.

- [ ] **Step 4: Create customer Vue pages**

Create pages that follow the existing invoice page structure:

- `Index.vue`: search input, table columns `Name`, `Phone`, `Addresses`, `Actions`, and `+ New` link.
- `Create.vue`: wraps `CustomerForm` and posts to `customers.store`.
- `Edit.vue`: wraps `CustomerForm` and puts to `customers.update`.
- `Show.vue`: customer summary, addresses, order notes table, invoice table, and totals.
- `partials/CustomerForm.vue`: fields for `name`, `phone`, and repeatable address inputs with add/remove buttons.

Use these form payload shapes:

```ts
const form = useForm({
    name: "",
    phone: "",
    addresses: [{ address: "" }],
});
```

```ts
const form = useForm({
    name: props.customer.name ?? "",
    phone: props.customer.phone ?? "",
    addresses: props.customer.addresses?.length
        ? props.customer.addresses.map((row: any) => ({ address: row.address }))
        : [{ address: "" }],
});
```

- [ ] **Step 5: Run customer feature tests**

Run: `php artisan test tests/Feature/CustomerManagementTest.php`

Expected: PASS.

- [ ] **Step 6: Commit Task 3**

Run:

```bash
git add app/Http/Controllers/CustomerController.php resources/js/pages/customers tests/Feature/CustomerManagementTest.php
git commit -m "feat: add customer management pages"
```

## Task 4: Complete Order Note Controller and Pages

**Files:**
- Modify: `app/Http/Controllers/OrderNoteController.php`
- Create: `resources/js/pages/order-notes/Index.vue`
- Create: `resources/js/pages/order-notes/Create.vue`
- Create: `resources/js/pages/order-notes/Edit.vue`
- Create: `resources/js/pages/order-notes/Show.vue`
- Create: `resources/js/pages/order-notes/partials/OrderNoteForm.vue`
- Test: `tests/Feature/OrderNoteTest.php`

- [ ] **Step 1: Add failing order note page tests**

Append to `OrderNoteTest`:

```php
public function test_authenticated_user_can_visit_order_note_index_and_create_pages(): void
{
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('order-notes.index'))->assertOk();
    $this->actingAs($user)->get(route('order-notes.create'))->assertOk();
}
```

- [ ] **Step 2: Run the order note page tests to verify they fail**

Run: `php artisan test tests/Feature/OrderNoteTest.php --filter=authenticated_user_can_visit_order_note_index_and_create_pages`

Expected: FAIL because controller methods and Vue pages are missing.

- [ ] **Step 3: Complete OrderNoteController**

Add controller methods:

- `index(Request $request)`: search by `name`, `phone`, or `product_list`; load customer and invoice; paginate 15.
- `create()`: pass all customers with addresses for selection.
- `show(OrderNote $orderNote)`: load customer and invoice.
- `edit(OrderNote $orderNote)`: pass note and customers with addresses.
- `update(Request $request, OrderNote $orderNote)`: validate, resolve customer, recalculate `total = paid + due`, update note, add address to customer if missing.
- `destroy(OrderNote $orderNote)`: delete only if not converted; if converted, redirect back with an error message.

Keep `store` and `convert` behavior from Task 2.

- [ ] **Step 4: Create order note Vue pages**

Create pages that follow the existing invoice page structure:

- `Index.vue`: search input, table columns `Name`, `Phone`, `Address`, `Paid`, `Due`, `Total`, `Invoice`, `Actions`, and `+ New` link.
- `Create.vue`: wraps `OrderNoteForm` and posts to `order-notes.store`.
- `Edit.vue`: wraps `OrderNoteForm` and puts to `order-notes.update`.
- `Show.vue`: note detail, product list, paid/due/total, linked customer, linked invoice, and Convert to Invoice button if not converted.
- `partials/OrderNoteForm.vue`: customer selector, name, phone, address, product textarea, paid, due, read-only computed total.

Use this form payload shape:

```ts
const form = useForm({
    customer_id: "",
    name: "",
    phone: "",
    address: "",
    product_list: "",
    paid: 0,
    due: 0,
});
```

The computed display total should be:

```ts
const total = computed(() => Number(form.paid || 0) + Number(form.due || 0));
```

When customer selection changes, populate `name`, `phone`, and the first address from the selected customer unless the fields already contain user-entered text.

- [ ] **Step 5: Run order note feature tests**

Run: `php artisan test tests/Feature/OrderNoteTest.php`

Expected: PASS.

- [ ] **Step 6: Commit Task 4**

Run:

```bash
git add app/Http/Controllers/OrderNoteController.php resources/js/pages/order-notes tests/Feature/OrderNoteTest.php
git commit -m "feat: add order notes pages"
```

## Task 5: Navigation and Invoice Detail Links

**Files:**
- Modify: `resources/js/components/AppSidebar.vue`
- Modify: `resources/js/pages/invoices/Show.vue`
- Modify: `app/Http/Controllers/InvoiceController.php`

- [ ] **Step 1: Update invoice show loading**

Modify `InvoiceController::show` to load customer and order note:

```php
$invoice->load(['customer', 'orderNote']);
```

Place this before `return Inertia::render('invoices/Show', [...])`.

- [ ] **Step 2: Update invoice show page links**

In `resources/js/pages/invoices/Show.vue`, add display rows for linked customer and order note when present:

```vue
<div v-if="invoice.customer">
    <span class="font-medium">Customer:</span>
    <Link :href="route('customers.show', invoice.customer.id)" class="underline">
        {{ invoice.customer.name }}
    </Link>
</div>
<div v-if="invoice.order_note">
    <span class="font-medium">Order Note:</span>
    <Link :href="route('order-notes.show', invoice.order_note.id)" class="underline">
        #{{ invoice.order_note.id }}
    </Link>
</div>
```

- [ ] **Step 3: Update sidebar navigation**

Modify `resources/js/components/AppSidebar.vue` imports:

```ts
import { LayoutGrid, NotebookPen, Settings, ShoppingCart, Users } from 'lucide-vue-next';
```

Add nav items:

```ts
{
    title: 'Customers',
    href: '/customers',
    icon: Users,
},
{
    title: 'Order Notes',
    href: '/order-notes',
    icon: NotebookPen,
},
```

- [ ] **Step 4: Run frontend build**

Run: `npm run build`

Expected: build completes without TypeScript or Vite errors.

- [ ] **Step 5: Commit Task 5**

Run:

```bash
git add app/Http/Controllers/InvoiceController.php resources/js/components/AppSidebar.vue resources/js/pages/invoices/Show.vue
git commit -m "feat: link customers and order notes in navigation"
```

## Task 6: Full Verification

**Files:**
- No planned edits.

- [ ] **Step 1: Run Laravel feature tests**

Run: `php artisan test tests/Feature/CustomerManagementTest.php tests/Feature/OrderNoteTest.php`

Expected: PASS.

- [ ] **Step 2: Run full test suite**

Run: `php artisan test`

Expected: PASS.

- [ ] **Step 3: Run formatter check**

Run: `vendor/bin/pint --test`

Expected: PASS.

- [ ] **Step 4: Run frontend build**

Run: `npm run build`

Expected: PASS.

- [ ] **Step 5: Check git status**

Run: `git status --short`

Expected: only known unrelated pre-existing user changes remain, or a clean worktree if those changes were handled separately.

## Self-Review

Spec coverage:

- Customer CRUD with multiple single-line addresses is covered by Tasks 1 and 3.
- Order Notes naming and UI module are covered by Tasks 2 and 4.
- Manual customer selection and phone-based customer matching are covered by Task 2 controller behavior and Task 4 form behavior.
- `total = paid + due` is covered by Task 2 tests and controller logic.
- `cod = due` during conversion is covered by Task 2 tests and controller logic.
- One-time conversion and invoice linking are covered by Task 2 tests and Task 4 show page behavior.
- Customer spend, paid, due, notes, and linked invoices are covered by Task 3 customer detail work.
- Navigation is covered by Task 5.

Placeholder scan:

- No unresolved placeholders are present.
- No task uses an undefined model or route without first creating it in an earlier task.

Type consistency:

- The module is consistently named `Order Notes` in UI and `order_notes` in routes/database.
- Invoice link fields use `customer_id` and `order_note_id`.
- Order note link back to invoice uses `invoice_id`.
