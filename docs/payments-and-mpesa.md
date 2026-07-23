# Payments, receipts, vendors, and M-Pesa (B2C / C2B)

Written after the payments module was added to the Commercial side of the app:
customers can now be invoiced and pay, vendors can be registered and paid, and
both directions of M-Pesa (Safaricom Daraja) are wired in behind a fake gateway
so the whole flow can be demoed and tested with no live Safaricom account.

## Short answer

Trooms can now:

- invoice a sales order and record payment against it (cash, M-Pesa, bank,
  cheque), issuing a numbered, printable **receipt** each time;
- register **vendors** (suppliers, transporters, service providers) with a
  payment-ready phone number, and issue a **payment voucher** — Trooms's own
  proof of paying them;
- **disburse to a vendor via M-Pesa B2C** with one click from an expense;
- **receive a customer's M-Pesa payment via C2B**, matched automatically to
  the invoice the customer quoted, or held for manual allocation if it wasn't
  matched;
- see every M-Pesa movement, in or out, in one reconciliation log.

None of this talks to Safaricom yet. A `FakeMpesaGateway` simulates every
response so the feature is fully working and testable today. Switching to the
real API is a scoped, well-marked task — see [Going live](#going-live-connecting-the-real-daraja-api).

## Why this exists

Sales were already happening with no invoice, no receipt, and no record of
what a customer had actually paid — `sales_orders` had quantities but no
money. Money paid out had a category and an amount but no payee — nothing in
the schema represented a vendor. This module closes both gaps, and adds the
M-Pesa rails on top so the two payment directions the estate actually uses
(paying vendors, receiving customer payments) work end to end.

## The data model

```
customers ──┐                          vendors ──┐
            │                                     │
            ▼                                     ▼
      sales_orders                            expenses
   (invoice_number,                      (vendor_id, voucher_number,
    total_amount,                         payment_mode)
    amount_paid,
    payment_status)
            │                                     │
            ▼                                     ▼
        payments ◄─────┐               mpesa_transactions
   (receipt_number,     │              (direction: b2c, payable → expenses)
    method, amount,      │
    voided_at)            │
            │              │
            └──────────────┘
        mpesa_transactions
     (direction: c2b, payable → sales_orders)
```

**`payments`** — one row per payment *received*. Doubles as the receipt: the
receipt number, the method, the amount, who received it. Morphs to its
`payable` (a `SalesOrder` today; the shape is ready for horse rides or any
future module). Written once — there is no update route. A mistake is voided
(`voided_at`, `voided_by`, `void_reason`) and re-recorded, never edited, so a
receipt already in a customer's hand keeps meaning what it said.

**`vendors`** — who Trooms pays. `phone` is stored normalised
(`2547XXXXXXXX`) because it is the literal B2C destination, not just a
contact detail. Deactivated, never deleted, so past expenses keep their
payee.

**`expenses.voucher_number`** — Trooms's own proof of paying a vendor, the
outbound mirror of a receipt. Requires a vendor; idempotent to issue.

**`mpesa_transactions`** — the system of record for every M-Pesa movement,
shaped to match what Safaricom's Daraja API itself reports (receipt number,
conversation ID, checkout request ID, raw callback payload). `direction` is
`b2c` or `c2b`; `payable` morphs to an `Expense` (money going out) or a
`SalesOrder` (money coming in).

Full detail per table: [SalesOrder](../app/Models/SalesOrder.php),
[Payment](../app/Models/Payment.php), [Vendor](../app/Models/Vendor.php),
[Expense](../app/Models/Expense.php), [MpesaTransaction](../app/Models/MpesaTransaction.php).

## The accounting change this required

`FinanceController::post()` used to post a fulfilled sales order as
`Dr Cash / Cr Revenue`. That silently assumed every delivered order had also
been collected. With real payments now recorded separately, debiting Cash at
fulfilment *and* at payment would count every sale twice.

Fulfilment now recognises the revenue without assuming collection:

```
Fulfilment:  Dr Accounts Receivable (1200)   Cr Produce Sales (4000)
Payment:     Dr M-Pesa Float (1100) or Cash (1000)   Cr Accounts Receivable (1200)
```

M-Pesa money is posted to its own float account (`1100`), separate from the
till — it reconciles against Safaricom's statement, not the cash drawer.
Voiding a payment posts the exact mirror-image pair rather than deleting
anything, so the ledger's history is never edited, only added to. See
[`PaymentPosting`](../app/Support/PaymentPosting.php).

## The two payment flows

### Customer pays (invoice → payment → receipt)

1. A sales order is invoiced (`SalesOrder::issueInvoice()`) — freezes the
   line total as `total_amount` and assigns `INV-000001`.
2. A payment is recorded against it, manually (`PaymentController::store()`)
   or automatically via a matched C2B payment (below). Either way a
   `Payment` row is created, a `RCT-000001` receipt number assigned, and
   `PaymentPosting::post()` writes the ledger pair.
3. `SalesOrder::refreshPaymentStatus()` recomputes `amount_paid` and
   `payment_status` (`unpaid` / `partial` / `paid`) **from the payments on
   record**, not by incrementing — so a voided payment corrects the order
   automatically rather than needing to be subtracted by hand.

### Trooms pays a vendor (expense → voucher → B2C)

1. An expense is logged with a `vendor_id`.
2. `MpesaController::disburse()` calls the bound `MpesaGatewayContract`,
   writes an `mpesa_transactions` row (`direction = b2c`), and — on
   success — sets `payment_mode = mpesa` and issues the voucher
   automatically.
3. The voucher (`resources/views/expenses/voucher.blade.php`) is Trooms's
   own printable proof of payment: it explicitly says it is not a receipt
   issued *by* the vendor.

## M-Pesa: how the demo works, and what's real

**The business logic is real.** Matching a C2B payment to an invoice, posting
it to the ledger, generating a receipt, disbursing a B2C payout, issuing a
voucher — none of that is mocked. What's simulated is only the one seam that
needs a live Safaricom account: the actual network call to Daraja.

```
app/Services/Mpesa/MpesaGatewayContract.php   ← the interface the rest of the
        │                                        app depends on
        ├── FakeMpesaGateway    (bound by default — resolves B2C
        │                        synchronously with a fake receipt code)
        └── DarajaGateway       (real client, not wired up — see below)
```

Everything downstream of the contract — `MpesaController`, `C2BAllocation`,
`PaymentPosting`, the ledger, the vouchers, the receipts — has no idea which
implementation is behind it. That is the entire point of the split: going
live is filling in one class, not rewriting the feature.

### B2C — paying a vendor

`MpesaController::disburse()` calls `$gateway->initiateB2C(...)`. Real
Daraja B2C is asynchronous — Safaricom accepts the request immediately and
confirms success or failure later via a callback. `FakeMpesaGateway` instead
resolves the whole thing synchronously with a plausible fake receipt code
(e.g. `SGH4KLM9XZ`) so the demo doesn't need to wait on anything.

### C2B — receiving a customer payment

C2B has no "initiate" — a customer pays at the paybill menu with no app
involved, and Safaricom posts a **confirmation** to a URL Trooms registers.
[`C2BAllocation::receive()`](../app/Support/C2BAllocation.php) is the one
place that payload is turned into a `Payment`: it looks up a `SalesOrder` by
`invoice_number` against whatever the customer typed as the account
reference, and either settles the order or leaves the transaction
**unallocated** for a human to match later — a mistyped or blank reference
is treated as an expected outcome, not an error.

Two things call this exact same method:

- `MpesaWebhookController::c2bConfirmation()` — the real webhook, POSTed to
  by Safaricom;
- `MpesaController::simulateC2B()` — the demo form at
  **Commercial → M-Pesa → Simulate C2B Payment**, which builds an identical
  payload by hand.

That means the demo button and the real integration are provably running the
same logic — the form isn't a separate mock of the behaviour, it's a manual
trigger for the real one.

### Where the real webhooks already live

`routes/api.php` (stateless, no CSRF — same pattern as the existing
weigh-scale ingest endpoint) already exposes the four URLs a real Daraja
integration needs to register with Safaricom:

| Route name | Purpose |
|---|---|
| `api.mpesa.c2b.validation` | Safaricom asks before completing a C2B payment; always accepted |
| `api.mpesa.c2b.confirmation` | Fires once a C2B payment has completed — calls `C2BAllocation::receive()` |
| `api.mpesa.b2c.result` | Fires once a B2C payout resolves — idempotent on `conversation_id` |
| `api.mpesa.b2c.timeout` | Fires if a B2C request times out in Safaricom's queue |

They are unused while `MPESA_DRIVER=fake` (the default) but are fully
implemented and covered by tests today — see
[`MpesaWebhookController`](../app/Http/Controllers/Api/MpesaWebhookController.php).

## Going live: connecting the real Daraja API

Everything needed is documented in the docblock of
[`app/Services/Mpesa/DarajaGateway.php`](../app/Services/Mpesa/DarajaGateway.php).
In short:

1. Register a Daraja app at developer.safaricom.co.ke and get a consumer
   key/secret, an initiator name + password, the certificate to encrypt the
   initiator password, and a confirmed paybill/till shortcode.
2. Fill in `MPESA_*` in `.env` (see `.env.example` for the full list —
   shortcode, consumer key/secret, passkey, initiator credentials, cert
   path).
3. Implement `DarajaGateway::getAccessToken()` (OAuth, cached) and
   `initiateB2C()` against the endpoints already noted inline in that file.
4. Register the four URLs above as the C2B Validation/Confirmation URLs and
   the B2C ResultURL/QueueTimeOutURL with Safaricom.
5. Set `MPESA_DRIVER=daraja` — `AppServiceProvider` binds `DarajaGateway`
   instead of `FakeMpesaGateway` from that point on. No other code changes.

### Open questions before going live (not technical)

- **Paybill/till**: is there an existing one already collecting the sales
  happening now, or is a new one being applied for? Matters for whether
  in-flight C2B history can be backfilled against the invoices this module
  now creates.
- **B2C approval**: who initiates a disbursement vs. who approves it? B2C
  moves real money irreversibly. Today `disburse()` is gated to the
  `finance` module only (same standard as voiding a receipt) — a
  maker–checker split (initiator ≠ approver, plus a per-transaction/per-day
  cap) is the natural next step once a real payout can actually fail or be
  disputed, but wasn't built now because there was nothing real to check
  against yet.
- **eTIMS**: Trooms isn't VAT-registered, so receipts are plain sequential
  documents today. `payments` carries three nullable, unused
  `etims_*` columns so KRA e-invoicing can be added later without
  retrofitting an already-issued receipt series.

## Where to look

| Concern | File |
|---|---|
| Sales order money fields, invoicing | [`app/Models/SalesOrder.php`](../app/Models/SalesOrder.php) |
| Receiving a payment, voiding one | [`app/Http/Controllers/PaymentController.php`](../app/Http/Controllers/PaymentController.php) |
| Ledger posting for payments | [`app/Support/PaymentPosting.php`](../app/Support/PaymentPosting.php) |
| Vendor registry | [`app/Models/Vendor.php`](../app/Models/Vendor.php), [`app/Http/Controllers/VendorController.php`](../app/Http/Controllers/VendorController.php) |
| Payment vouchers | `Expense::issueVoucher()` in [`app/Models/Expense.php`](../app/Models/Expense.php) |
| M-Pesa gateway contract + fake/real implementations | [`app/Services/Mpesa/`](../app/Services/Mpesa/) |
| B2C disbursement, C2B simulate form, transaction log | [`app/Http/Controllers/MpesaController.php`](../app/Http/Controllers/MpesaController.php) |
| Real Safaricom webhooks | [`app/Http/Controllers/Api/MpesaWebhookController.php`](../app/Http/Controllers/Api/MpesaWebhookController.php) |
| C2B → invoice matching rule | [`app/Support/C2BAllocation.php`](../app/Support/C2BAllocation.php) |
| Tests | `tests/Feature/PaymentReceiptTest.php`, `VendorTest.php`, `PaymentVoucherTest.php`, `MpesaB2CDisbursementTest.php`, `MpesaC2BTest.php`, `MpesaPagesRenderTest.php`, `DemoDataSeederTest.php` |

## Testing this

A step-by-step manual checklist (invoicing, paying, voiding, disbursing,
simulating a C2B payment, checking the ledger) was written into the
conversation this module was built in — ask for it again if it's needed, or
run `./vendor/bin/phpunit --no-coverage`, which covers the same ground
automatically (99 tests at time of writing).
