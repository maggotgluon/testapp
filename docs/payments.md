# Payment System

> **OKF Section**: Payment & Financial Flows  
> **Audience**: Developers implementing payment features, admins understanding slip review

---

## Supported Payment Methods

| Method | Value | Description |
|--------|-------|-------------|
| QR Payment | `qr_payment` | Thai PromptPay QR code — customer scans and pays, then uploads a slip |
| Bank Transfer | `bank_transfer` | Manual bank transfer — customer uploads a transfer slip |
| Cash | `cash` | Cash at the counter — no slip required, status is `cash_pending` |

---

## Payment Account Configuration

Events support **multiple payment accounts** per method via the `payment_accounts` JSON column.

### New Format (preferred)

```json
[
  {
    "key": "promptpay-main",
    "method": "qr_payment",
    "label": "Pay via PromptPay",
    "account_name": "EventOrg Co., Ltd.",
    "account_number": "0812345678",
    "instructions": "Scan QR and upload slip",
    "is_active": true
  },
  {
    "key": "kbank-transfer",
    "method": "bank_transfer",
    "label": "Kasikorn Bank",
    "bank_name": "Kasikornbank",
    "account_name": "EventOrg Co., Ltd.",
    "account_number": "123-4-56789-0",
    "instructions": "Transfer and take a screenshot",
    "is_active": true
  }
]
```

### Legacy Format (backwards compatible)

Older events use individual columns:
- `qr_payment_account` — PromptPay number
- `qr_payment_account_name` — Account name
- `bank_name`, `bank_account_name`, `bank_account_number` — Bank transfer info

The `Event::paymentOptions()` method normalizes both formats into the same structure.

---

## Checkout Payment Flow

```
1. Customer selects payment method at checkout
   ├── If QR / Bank Transfer: must upload slip image
   └── If Cash: no slip needed

2. POST /orders → OrderController::store()
   ├── Validates payment method is enabled for this event
   ├── Stores slip file via PaymentSlipStorageService
   │
   ├── Creates TicketOrder (status = pending)
   ├── Creates OrderItems + Tickets (status = pending)
   └── Creates Payment record (status = submitted)

3. If total == 0 (free ticket):
   └── Auto-approved immediately
       ├── TicketOrder.status = approved
       ├── Tickets.status = approved
       └── Payment.status = waived

4. SlipQrDecoderService::review() runs automatically
   └── Updates payment with QR decode results and review flags
```

---

## PromptPay QR Code Generation

The `QrCodeService` generates EMVCo-compliant PromptPay QR codes.

### Payment QR Code

Available at: `GET /payments/events/{event}/qr?amount=1500&account=promptpay-main`

Generates a dynamic QR code with:
- PromptPay receiver (phone/tax ID/e-wallet)
- Pre-filled amount
- Returns SVG

### Ticket QR Code

Available at: `GET /tickets/{uuid}/qr`

Generates a QR containing the ticket UUID. Used for gate check-in scanning.

---

## Slip QR Decoding

`SlipQrDecoderService` reads QR codes embedded in Thai bank transfer slips.

### Process

```
Slip uploaded → stored in payment-slips/
     ↓
SlipQrDecoderService::review(slipPath, paymentData, $payment)
     ↓
1. Load image from disk
2. Preprocess (greyscale, contrast, resize for QR readability)
3. Decode QR using chillerlan/php-qrcode
4. Parse EMVCo/PromptPay payload fields:
   - Amount
   - Date/time of transaction
   - Reference number
   - Receiver account info
   - Sending bank code
5. Validate against expected payment:
   - Amount ≠ expected → flag: amount_mismatch
   - Receiver doesn't match → flag: receiver_mismatch
   - Same QR seen before → flag: duplicate_slip
   - Same image hash → flag: duplicate_slip
   - CRC failed → flag: invalid_crc
6. Set review_status:
   - No flags → ok
   - Has flags → risky
   - Decode failed → needs_manual_review
```

### Review Status Meanings

| `slip_review_status` | Admin Action |
|---------------------|-------------|
| `ok` | Can approve immediately (but manual review recommended) |
| `risky` | ⚠️ Show warning to admin — admin must override to approve |
| `needs_manual_review` | Admin must manually verify the slip |

---

## Order Approval Flow (Admin)

```
POST /admin/orders/{order}/approve
     ↓
Check order status is 'pending'
     ↓
Check slip_review_status:
  If 'risky' → require force_approve=true in request
     ↓
DB Transaction:
  ├── TicketOrder.status = approved, approved_at = now(), approved_by = admin_id
  ├── All Tickets.status = approved
  ├── Increment TicketType.sold_count for each ticket
  └── Payment.status = approved
     ↓
Send notification to customer (LINE + Web Push)
     ↓
Push activity to CRM
```

---

## Order Rejection

```
POST /admin/orders/{order}/reject
     ↓
TicketOrder.status = rejected
All Tickets.status = rejected
Payment.status = rejected
```

---

## Order Cancellation & Refund

| Action | Endpoint | Status Transition |
|--------|----------|------------------|
| Cancel | `POST /admin/orders/{order}/cancel` | `approved → cancelled` |
| Refund | `POST /admin/orders/{order}/refund` | `approved → refunded` |

Tickets also update to `cancelled` or `refunded` respectively.

---

## Payment Slip Archive

After an event ends, admins can archive payment slips to free up active storage:

```
POST /admin/events/{event}/archive-payment-slips
     ↓
Event must have ended (ends_at < now())
     ↓
PaymentSlipStorageService::archiveApprovedSlipsForEndedEvent(event)
     ↓
For each approved order with a slip:
  ├── Copy file to payment-slips-archive/
  ├── Set slip_archived_path and slip_archived_at
  └── Remove from payment-slips/
     ↓
Returns: { archived: N, already_archived: N, missing: N }
```

---

## Manual Slip Re-check

```
POST /admin/orders/{order}/check-slip-qr
     ↓
Re-runs SlipQrDecoderService on the current slip
     ↓
Updates payment record with fresh decode results
```

---

## Updating Payment Slip (Admin)

```
POST /admin/orders/{order}/payment-slip
  { slip: file }
     ↓
Store new slip via PaymentSlipStorageService
     ↓
Delete old slip (if exists)
     ↓
Re-decode QR
     ↓
Update payment record
```

---

## Thai Bank Codes (Reference)

| Code | Bank |
|------|------|
| 002 | Bangkok Bank (ธนาคารกรุงเทพ) |
| 004 | Kasikornbank (ธนาคารกสิกรไทย) |
| 006 | Krungthai Bank (ธนาคารกรุงไทย) |
| 011 | TTB Bank (ธนาคารทหารไทยธนชาต) |
| 014 | Siam Commercial Bank (ธนาคารไทยพาณิชย์) |
| 022 | CIMB Thai |
| 024 | United Overseas Bank |
| 025 | Bank of Ayudhya (กรุงศรีอยุธยา) |
| 030 | Government Savings Bank (ธนาคารออมสิน) |
| 033 | Government Housing Bank (ธนาคารอาคารสงเคราะห์) |
| 034 | BAAC (ธ.ก.ส.) |
| 069 | Kiatnakin Phatra Bank |
| 073 | Land and Houses Bank |
