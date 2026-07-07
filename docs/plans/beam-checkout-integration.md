# Implementation Plan: Beam Checkout Payment Gateway

> **Status:** Ready for implementation  
> **Target:** TicketFlow (Laravel 13, PHP 8.3)

---

## Overview

Integrate **Beam Checkout** (Thai payment gateway) as a payment method. Beam supports PromptPay QR, credit cards, mobile banking, and e-wallets. This implementation uses the **Beam Charges API** for maximum control over the checkout flow.

### Key Features

- **Beam as a payment option** alongside existing `qr_payment`, `bank_transfer`, `cash`
- **Per-event fee behavior**: each event can choose to absorb the Beam fee or pass it to the customer
- **Automatic order approval** on `charge.succeeded` webhook (no manual admin review needed for Beam payments)
- **QR PromptPay display** — Beam returns a base64 QR image shown directly on the order page
- **Webhook-based status updates** — `charge.succeeded` / `charge.failed` events

### Beam API Details

| Item | Value |
|------|-------|
| Base URL (playground) | `https://playground.api.beamcheckout.com` |
| Base URL (production) | `https://api.beamcheckout.com` |
| Auth | HTTP Basic (`merchantId:apiKey`) |
| Charge endpoint | `POST /api/v1/charges` |
| Get charge | `GET /api/v1/charges/{chargeId}` |
| Refund | `POST /api/v1/charges/{chargeId}/refund` |
| Webhook auth | HMAC-SHA256, key from Lighthouse |
| Amount format | Satang (smallest currency unit: THB → satang) |

---

## Files to Create

| File | Purpose |
|------|---------|
| `app/Services/BeamService.php` | Beam API wrapper — create/get/refund charges, verify webhooks |
| `app/Http/Controllers/BeamWebhookController.php` | Handle `charge.succeeded` / `charge.failed` webhooks |
| `resources/views/beam/pay.blade.php` | Beam payment page (QR display for PromptPay) |
| `database/migrations/2026_07_06_000001_add_beam_checkout_fields.php` | Migration for beam columns |

## Files to Modify

| File | Change |
|------|--------|
| `.env.example` | Add `BEAM_MERCHANT_ID`, `BEAM_API_KEY`, `BEAM_WEBHOOK_KEY`, `BEAM_ENVIRONMENT` |
| `config/services.php` | Add `beam` config block |
| `app/Models/Event.php` | Add `beam_enabled`, `beam_fee_behavior`, `beam_fee_percent` fillable + casts |
| `app/Models/TicketOrder.php` | Add `beam_fee_thb`, `beam_charge_id` fillable |
| `app/Models/Payment.php` | Add `beam_charge_id` fillable |
| `routes/web.php` | Add webhook route + beam payment flow routes |
| `app/Http/Controllers/OrderController.php` | Integrate Beam as payment method in `store()`, add `beamPayment()` |
| `app/Http/Controllers/Admin/EventController.php` | Add beam settings to validation |

---

## Database Schema Changes

### `events` table

| Column | Type | Default | Description |
|--------|------|---------|-------------|
| `beam_enabled` | boolean | `false` | Enable Beam Checkout for this event |
| `beam_fee_behavior` | string(20) | `'merchant_absorb'` | `merchant_absorb` or `customer_pay` |
| `beam_fee_percent` | decimal(5,2) | `null` | Override fee % (null = use config default) |

### `ticket_orders` table

| Column | Type | Default | Description |
|--------|------|---------|-------------|
| `beam_fee_thb` | integer | `null` | Beam fee amount in THB (when customer_pay) |
| `beam_charge_id` | string(80) | `null` | Beam's charge ID for this order |

### `payments` table

| Column | Type | Default | Description |
|--------|------|---------|-------------|
| `beam_charge_id` | string(80) | `null` | Beam charge ID (populated on creation) |
| `beam_qr_image` | text | `null` | Base64-encoded QR image from Beam |

---

## Fee Calculation Logic

### `merchant_absorb` (merchant pays the fee)

```
total_charged = subtotal - discount
fee_paid_by_merchant = total_charged * fee_rate / 100
merchant_receives = total_charged - fee_paid_by_merchant
```

Customer sees: `subtotal - discount = total_charged`
The Beam fee is taken from what the merchant receives.

### `customer_pay` (customer pays the fee)

```
total_before_fee = subtotal - discount
fee_amount = ceil(total_before_fee * fee_rate / 100)
total_charged = total_before_fee + fee_amount
```

Customer sees: `total_before_fee + fee_amount` (fee shown as separate line item)
Merchant receives approximately `total_before_fee` after Beam deducts their fee.

---

## Integration Flow

### 1. Checkout with Beam

```
Customer selects tickets → fills customer info
  → selects "Beam Checkout" as payment method
  → POST /orders
  → OrderController::store()
      → If beam_fee_behavior === 'customer_pay':
          total += ceil(beam_fee)
      → Create TicketOrder (status: pending)
      → Create Payment (status: pending)
      → Create Beam charge via BeamService
      → Store beam_charge_id on Payment
      → If QR PromptPay: redirect to order page with QR display
      → If Card/Redirect: return redirect URL
      → Notify admins
```

### 2. Payment Completion

```
Customer pays via Beam (QR scan or card form)
  → Beam sends webhook: charge.succeeded
  → BeamWebhookController::handle()
      → Verify HMAC signature
      → Find Order by referenceId (order number)
      → Auto-approve order
      → Notify customer
      → Update Payment status
```

### 3. Failed Payment

```
Beam sends webhook: charge.failed
  → BeamWebhookController::handle()
      → Verify signature
      → Find Order by referenceId
      → Update Payment with failure details
      → Order stays pending for retry
```

### 4. Refund

```
Admin clicks "Refund" on order
  → AdminOrderController::refund()
      → Check if beam_charge_id exists
      → Call BeamService::refundCharge(chargeId)
      → Mark order as refunded
```

---

## Webhook Authentication

Beam uses HMAC-SHA256:

```php
$expected = base64_encode(
    hash_hmac('sha256', $requestBody, $decodedKey, true)
);
// Compare with X-Beam-Signature header
```

---

## Testing

1. Set `BEAM_ENVIRONMENT=playground` and use test credentials
2. Create an event with `beam_enabled = true`
3. Complete a checkout selecting "Beam Checkout"
4. Verify QR code displays on order page
5. Use Beam test data to simulate payment
6. Verify webhook auto-approves the order
