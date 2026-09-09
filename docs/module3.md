# Module 3 - Cart, Orders & Payment

**Owner:** Minidu Rajapaksha (SE/2023/039)
**Status:** Built per `docs/PROJECT_BRIEF.md`, Section 6, Module 3, and Section 7 (payment gateway spec).

## What this module contains

1. **Cart** (`orders/cart.php`, `orders/cart_action.php`) - persistent
   cart backed by `cart`/`cart_item`, quantity capped at `stockQty`,
   adding an already-present part increases its quantity instead of
   duplicating the row.
2. **Checkout & the order transaction** (`orders/checkout.php`,
   `orders/place_order.php`, `orders/lib/order_helper.php::placeOrder()`)
   - the core deliverable. `placeOrder()` is one database transaction:
   creates the order, one `order_item` per cart line (copying the
   *current* price into `unitPrice`), decrements stock with a race-safe
   `stockQty >= ?` guard, empties the cart, and creates a `Pending`
   payment row. Any failure - including someone else buying the last
   unit mid-checkout - rolls back everything.
3. **Payment** (`payment/pay.php`, `payment/payhere_checkout.php`,
   `payment/payment_return.php`, `payment/payment_cancel.php`,
   `payment/payhere_notify.php`, `payment/mock_gateway.php`,
   `payment/receipt.php`) - both gateways from Section 7: PayHere
   sandbox (full hash-based integration, `return_url` confirms on this
   WAMP demo since `notify_url` can't reach localhost - both files carry
   the required production-vs-demo comment) and a built-in simulated
   card gateway with a "simulate a failed payment" checkbox for testing
   the failure path without needing a specific PayHere test card.
4. **Order history & cancellation** (`orders/my_orders.php`,
   `orders/order_details.php`, `orders/cancel_order.php`) - status
   filter, a visual status timeline, and cancellation (Pending/Confirmed
   only) that restores stock and marks the payment Refunded in one
   transaction.
5. **Admin order management** (`admin/orders/manage_orders.php`,
   `update_order_status.php`, `process_refund.php`) and **gateway
   management** (`admin/gateways/manage_gateways.php`).

## Design notes and decisions worth knowing

- **Why `payment/pay.php` exists between checkout and the actual
  gateway.** `payment.gatewayID` is `NOT NULL` in the frozen schema, so
  the gateway has to be chosen *before* `place_order.php`'s transaction
  creates the payment row - it's picked via radio buttons on
  `checkout.php`. `payment/pay.php` (listed in the brief as the
  "Gateway selection page") is what a customer actually sees right
  after their order is placed: it confirms the order total and the
  already-chosen gateway, then dispatches to `payhere_checkout.php` or
  `mock_gateway.php`. This keeps the payment row's `gatewayID`
  well-defined at every point without contradicting the schema.
- **`database/seed_gateways.sql` duplicates `seed_core.sql`'s gateway
  rows on paper** - the brief's own file table assigns "the two payment
  gateway rows" to both Module 1's `seed_core.sql` and this module's
  `seed_gateways.sql`. This file's inserts are guarded with
  `WHERE NOT EXISTS`, so importing both in either order never creates
  duplicate rows.
- **The navbar cart badge only appears on this module's own pages.**
  `includes/navbar.php` (Module 1, frozen) calls `cartItemCount()`
  through a `function_exists()` guard rather than a hard `require`, so
  it never fatal-errors before this module exists. That also means the
  function is only *defined* on requests where something already loaded
  `orders/lib/order_helper.php` - i.e. every page under `orders/` and
  `payment/`. On Module 2's catalogue pages or Module 1's own auth/admin
  pages, the badge simply doesn't render (no count shown, not a wrong
  count) since the frozen navbar can't be edited now to load this
  module's file globally. This is a known, harmless limitation of the
  four-module split, not a bug.
- **Admin-cancelling an order also restores stock.** `cancelOrder()` in
  `order_helper.php` handles customer-initiated cancellation.
  `admin/orders/update_order_status.php` independently restores stock
  (via the shared `restoreStockForOrder()`) whenever an admin manually
  sets a still-active order's status to `Cancelled`, so both paths stay
  consistent.
- **PayHere's currency code (`LKR`) is defined in
  `payment/lib/payment_helper.php`**, not in the frozen
  `config/config.php`, since the site's `CURRENCY` constant is a display
  symbol (`"Rs"`), not the ISO code PayHere's API expects.

## How to test it

1. Import `schema.sql`, `seed_core.sql`, `seed_catalogue.sql` (Module 2),
   then `seed_gateways.sql` - in that order.
2. Set `PAYHERE_MERCHANT_ID` / `PAYHERE_MERCHANT_SECRET` in
   `config/config.local.php` if you want to test the real PayHere
   sandbox form (optional - the simulated gateway needs no credentials
   at all and is the recommended path for a demo/viva).
3. Log in as `john_doe` / `Password123`, add a few parts to the cart
   from `catalogue/product_details.php`, adjust quantities on
   `orders/cart.php`.
4. Go to `orders/checkout.php`, confirm the address, choose **Simulated
   Card Payment**, place the order.
5. On `payment/mock_gateway.php`, submit without ticking "simulate a
   failed payment" - lands on `orders/order_confirmation.php`, order
   status is now `Confirmed`, stock has been decremented. Check
   `payment/receipt.php` and print-preview it.
6. Place a second order and this time tick "simulate a failed payment" -
   payment shows `Failed`, order stays `Pending` in `orders/my_orders.php`,
   with a way back to `payment/pay.php` to retry.
7. From `orders/order_details.php` on a `Pending`/`Confirmed` order,
   click **Cancel Order** - stock is restored (check
   `admin/reports/inventory_report.php`, Module 1) and the payment shows
   `Refunded`.
8. As `admin`/`Admin@123`, open `admin/orders/manage_orders.php`, view
   an order, update its status/tracking number, and try
   `admin/orders/process_refund.php` on a `Success` payment.
9. `admin/gateways/manage_gateways.php` - toggle a gateway off and
   confirm it disappears from the checkout radio list.

## Files owned

`orders/{cart,cart_action,checkout,place_order,order_confirmation,my_orders,order_details,cancel_order}.php`,
`orders/lib/order_helper.php`,
`payment/{pay,payhere_checkout,payment_return,payment_cancel,payhere_notify,mock_gateway,receipt}.php`,
`payment/lib/payment_helper.php`,
`admin/orders/{manage_orders,update_order_status,process_refund}.php`,
`admin/gateways/manage_gateways.php`,
`assets/css/orders.css`, `assets/js/cart.js`,
`database/seed_gateways.sql`, `docs/module3.md`.
