# Customer Management and Order Notes Design

## Goal

Add customer management and order notes so staff can maintain reusable customer records, store multiple single-line addresses, create manual order notes, convert those notes into invoices, and see each customer's spend history with linked invoices.

## Existing Context

The app is a Laravel 12 + Inertia/Vue application. Invoices currently store customer fields directly on the `invoices` table: `name`, `phone`, `address`, `cod`, `total`, `instruction`, and `notes`. The new feature must preserve existing invoice behavior while adding optional links from invoices to customers and order notes.

The worktree already contains unrelated modifications to invoice files and routes. Implementation should avoid reverting those changes and should make scoped edits only.

## Data Model

Add a `customers` table with:

- `id`
- `name`
- `phone`
- timestamps

Add a `customer_addresses` table with:

- `id`
- `customer_id`
- `address`
- timestamps

Add an `order_notes` table with:

- `id`
- `customer_id`
- `invoice_id`, nullable
- `name`
- `phone`
- `address`
- `product_list`
- `paid`, decimal
- `due`, decimal
- `total`, decimal
- timestamps

Update the `invoices` table with nullable links:

- `customer_id`
- `order_note_id`

Customer phone should be usable for automatic matching, but manual customer selection must also be available. The first implementation will not enforce global phone uniqueness at the database level, because the user wants both phone matching and manual selection. Controllers should prefer an explicitly selected customer when provided; otherwise, they can find or create a customer by phone.

## Customer Module

Add customer CRUD pages:

- Customer index with search by name or phone.
- Customer create/edit form with name, phone, and multiple single-line addresses.
- Customer detail page showing customer fields, addresses, order notes, linked invoices, total spend, total paid, and total due.

Customer total spend is calculated from linked invoices. Customer total paid and total due are calculated from order notes, with invoice links displayed where available.

Existing invoices without a customer link must continue to work and display normally.

## Order Notes Module

Add an Order Notes module. The UI must use the name "Order Notes", not "Customer Notes".

An order note stores:

- customer selection or auto match by phone
- name
- phone
- one selected or typed single-line address
- product list as a textarea
- paid amount
- due amount
- calculated total

The rule is:

```text
total = paid + due
```

There is no separate delivery charge field in the first version. If only a delivery charge remains unpaid, staff will enter that amount manually into `due`.

## Note to Invoice Conversion

An order note can be converted to an invoice once.

When converting:

- Invoice `customer_id` is set from the order note customer.
- Invoice `order_note_id` is set to the order note id.
- Invoice `name`, `phone`, and `address` come from the order note.
- Invoice `instruction` is set from `product_list`.
- Invoice `total` is set to `paid + due`.
- Invoice `cod` is set to `due`.
- Invoice `delivery_type` should default to Delivery unless the conversion UI provides a different value.
- Invoice `wgt` can use the invoice form default unless the conversion UI provides a value.
- The order note stores the created `invoice_id`.

If an order note already has an invoice, the app must not create another invoice from it. Instead, it should redirect to or show the existing linked invoice.

## Navigation

Add sidebar entries:

- Customers
- Order Notes

Keep the existing Invoice navigation and screens operational.

## Validation and Error Handling

Customers require:

- `name`
- `phone`
- at least one non-empty address on create

Order notes require:

- customer selection or enough name/phone data to create or match a customer
- `name`
- `phone`
- `address`
- `product_list`
- numeric `paid` and `due`, both minimum 0

The app should calculate `total` server-side from `paid + due`; client-side calculation can be shown for convenience but must not be trusted.

Conversion must validate that the order note is not already converted before creating the invoice.

## Testing

Add Laravel feature tests for:

- Creating a customer with multiple addresses.
- Creating an order note and storing `total = paid + due`.
- Converting an order note to an invoice with `total = paid + due` and `cod = due`.
- Preventing duplicate conversion of the same order note.
- Showing customer detail totals and linked invoices.

Existing invoice tests or behavior should remain passing.
