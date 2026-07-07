# Data Model

> **OKF Section**: Schema & Data Definitions  
> **Audience**: Developers working with the database, agents writing queries

---

## Entity Relationship Diagram

```
┌──────────┐       ┌───────────────┐       ┌────────────────┐
│  users   │──┐    │  ticket_orders│──────▶│   order_items  │
└──────────┘  │    └───────────────┘       └────────────────┘
              │           │                        │
              │           ▼                        │
              │    ┌───────────┐                   ▼
              │    │ payments  │          ┌────────────────┐
              │    └───────────┘          │  ticket_types  │──┐
              │           │              └────────────────┘  │
              │           │                       ▲           │
              │    ┌───────────┐         ┌────────┘           │
              └───▶│  tickets  │──────── │       events       │
                   └───────────┘  event  └────────────────────┘
                         │                      │    │    │
                         ▼                      │    │    │
                  ┌──────────────┐              │    │    │
                  │ check_in_logs│       coupons│ promotions│surveys
                  └──────────────┘              ▼    ▼    ▼
                                       ┌──────────────────────┐
                                       │  event-level records │
                                       └──────────────────────┘

event_user (pivot): events ↔ users (many-to-many, for admin assignment)
```

---

## Tables

### `users`

| Column | Type | Notes |
|--------|------|-------|
| `id` | bigint PK | |
| `name` | string(255) | Display name |
| `username` | string(80) nullable | Used for admin login |
| `email` | string nullable unique | |
| `phone` | string(40) nullable | Primary identifier for customers |
| `role` | string | `null` (customer), `super_admin`, `event_admin`, `gate_scanner` |
| `provider` | string nullable | `line`, `facebook`, `instagram`, `guest` |
| `provider_id` | string nullable | OAuth provider user ID |
| `avatar` | string nullable | Profile image URL |
| `password` | string nullable | Hashed — admin accounts only |
| `line_friend_status` | string nullable | `connected`, `blocked`, `unfollowed` |
| `line_followed_at` | timestamp nullable | |
| `line_blocked_at` | timestamp nullable | |
| `remember_token` | string nullable | |
| `email_verified_at` | timestamp nullable | |
| `created_at`, `updated_at` | timestamps | |

**Roles:**
- `null` / no role → regular customer
- `super_admin` → full access to all events
- `event_admin` → manages assigned events only
- `gate_scanner` → can only scan tickets for assigned events

---

### `events`

| Column | Type | Notes |
|--------|------|-------|
| `id` | bigint PK | |
| `created_by` | bigint FK → users | |
| `name` | string(255) | |
| `description` | text nullable | HTML or Markdown (see `description_format`) |
| `description_format` | string | `html` (default) or `markdown` |
| `social_description` | string(500) nullable | OG meta description |
| `venue` | string(255) | Display name of venue |
| `location` | string(255) nullable | Address |
| `location_url` | string nullable | Google Maps link |
| `hosted_by` | string(255) nullable | Organizer name |
| `hosted_by_url` | string nullable | Organizer website |
| `starts_at` | datetime | |
| `ends_at` | datetime | |
| `poster_path` | string nullable | Stored in `uploads` disk |
| `ticket_image_path` | string nullable | Custom ticket artwork |
| `social_image_path` | string nullable | OG share image |
| `bank_name` | string nullable | Legacy single-account field |
| `bank_account_name` | string nullable | Legacy |
| `bank_account_number` | string nullable | Legacy |
| `qr_payment_account_name` | string nullable | Legacy |
| `qr_payment_account` | string nullable | Legacy PromptPay ID |
| `qr_payment_image_path` | string nullable | Static QR image (legacy) |
| `payment_instructions` | text nullable | |
| `payment_methods` | json nullable | `["qr_payment","bank_transfer","cash"]` |
| `payment_accounts` | json nullable | New multi-account structure (see below) |
| `is_published` | boolean | `false` = draft, hidden from public |
| `show_countdown` | boolean | Show countdown timer on event page |
| `created_at`, `updated_at` | timestamps | |

**`payment_accounts` JSON structure:**
```json
[
  {
    "key": "qr-payment-0",
    "method": "qr_payment",
    "label": "QR Payment",
    "account_name": "John Doe",
    "account_number": "0812345678",
    "instructions": "Transfer and upload slip",
    "is_active": true
  }
]
```

**Scope `visible()`**: `is_published = true AND ends_at >= now()`

---

### `ticket_types`

| Column | Type | Notes |
|--------|------|-------|
| `id` | bigint PK | |
| `event_id` | bigint FK → events | |
| `name` | string(255) | e.g., "Early Bird", "VIP" |
| `description` | text nullable | |
| `price_thb` | integer | Price in Thai Baht (no decimals) |
| `full_price_thb` | integer nullable | Original price (for crossed-out display) |
| `capacity` | integer | Max tickets available |
| `sold_count` | integer | Incremented on approval |
| `sale_starts_at` | datetime nullable | |
| `sale_ends_at` | datetime nullable | |
| `status` | string | `active`, `inactive` |
| `created_at`, `updated_at` | timestamps | |

**Key methods:**
- `isOnSale()` — checks status, sale dates, and capacity
- `availableQuantity()` — `capacity - sold_count`

---

### `ticket_orders`

| Column | Type | Notes |
|--------|------|-------|
| `id` | bigint PK | |
| `order_number` | string | e.g., `EVT-0629-001` |
| `user_id` | bigint FK → users, nullable | Null for guest checkout |
| `coupon_id` | bigint FK → coupons, nullable | Applied coupon |
| `customer_name` | string(255) | |
| `customer_phone` | string(40) | |
| `customer_email` | string nullable | |
| `status` | string | `pending`, `approved`, `rejected`, `cancelled`, `refunded` |
| `subtotal_thb` | integer | Before discounts |
| `discount_thb` | integer | Coupon + promotion total discount |
| `total_thb` | integer | `subtotal - discount` (minimum 0) |
| `payment_method` | string | `qr_payment`, `bank_transfer`, `cash` |
| `payment_note` | text nullable | Customer note |
| `payment_slip_path` | string nullable | Legacy slip path (see `payments` table) |
| `approved_at` | timestamp nullable | |
| `approved_by` | bigint FK → users, nullable | |
| `created_at`, `updated_at` | timestamps | |

**Order Status Transitions:**
```
pending → approved (admin approves)
pending → rejected (admin rejects)
approved → cancelled (admin cancels)
approved → refunded (admin refunds)
```

---

### `order_items`

| Column | Type | Notes |
|--------|------|-------|
| `id` | bigint PK | |
| `ticket_order_id` | bigint FK → ticket_orders | |
| `event_id` | bigint FK → events | Denormalized for easy querying |
| `ticket_type_id` | bigint FK → ticket_types | |
| `quantity` | integer | |
| `unit_price_thb` | integer | Price at time of purchase |
| `line_total_thb` | integer | `unit_price × quantity` |
| `created_at`, `updated_at` | timestamps | |

---

### `tickets`

| Column | Type | Notes |
|--------|------|-------|
| `id` | bigint PK | |
| `uuid` | string unique | Used as QR code payload |
| `ticket_order_id` | bigint FK → ticket_orders | |
| `order_item_id` | bigint FK → order_items | |
| `event_id` | bigint FK → events | Denormalized |
| `ticket_type_id` | bigint FK → ticket_types | |
| `user_id` | bigint FK → users, nullable | |
| `holder_name` | string(255) | Name printed on ticket |
| `holder_phone` | string(40) | |
| `status` | string | See lifecycle below |
| `checked_in_at` | timestamp nullable | |
| `checked_out_at` | timestamp nullable | |
| `created_at`, `updated_at` | timestamps | |

**Ticket Status Lifecycle:**
```
pending → approved → checked_in → checked_out
        → rejected
        → refunded
        → cancelled
        → expired
```

---

### `payments`

| Column | Type | Notes |
|--------|------|-------|
| `id` | bigint PK | |
| `ticket_order_id` | bigint FK | |
| `method` | string | `qr_payment`, `bank_transfer`, `cash` |
| `payment_account_key` | string(80) nullable | Matches event `payment_accounts[].key` |
| `payment_account_label` | string nullable | |
| `payment_account_name` | string nullable | |
| `payment_account_number` | string nullable | |
| `amount_thb` | decimal(10,2) | Actual amount |
| `expected_amount_thb` | decimal(10,2) nullable | Order total at time of payment |
| `expected_promptpay_id` | string nullable | Normalized PromptPay ID |
| `status` | string | `submitted`, `approved`, `rejected`, `waived` (free), `cash_pending` |
| `slip_path` | string nullable | Active slip storage path |
| `slip_archived_path` | string nullable | Archived path (post-event) |
| `slip_archived_at` | timestamp nullable | |
| `slip_deleted_at` | timestamp nullable | Soft-delete for slip privacy |
| `slip_image_sha256` | string nullable | For duplicate detection |
| `note` | text nullable | |
| `slip_qr_status` | string nullable | `decoded`, `no_qr`, `decode_error`, `mismatch` |
| `slip_qr_payload` | string nullable | Raw decoded QR string |
| `slip_qr_payload_sha256` | string nullable | For deduplication |
| `slip_qr_data` | json nullable | Parsed PromptPay fields |
| `slip_qr_amount_thb` | decimal(10,2) nullable | Amount from QR data |
| `slip_qr_paid_at` | timestamp nullable | Parsed transaction time |
| `slip_qr_reference` | string nullable | Transaction reference |
| `slip_qr_reference_normalized` | string nullable | Cleaned reference for matching |
| `slip_qr_receiver` | string nullable | Receiver account from QR |
| `slip_review_status` | string nullable | `ok`, `risky`, `needs_manual_review` |
| `slip_review_flags` | json nullable | Dictionary of review flags and messages |
| `slip_reviewed_at` | timestamp nullable | |
| `created_at`, `updated_at` | timestamps | |

---

### `coupons`

| Column | Type | Notes |
|--------|------|-------|
| `id` | bigint PK | |
| `event_id` | bigint FK → events, nullable | Null = global coupon |
| `ticket_type_id` | bigint FK → ticket_types, nullable | Scoped to specific type |
| `name` | string(255) | Display name |
| `code` | string(40) | Case-insensitive code. Stored/matched as uppercase |
| `discount_type` | string | `percent` or `fixed` |
| `discount_scope` | string | `order` (whole order) or `item` (per ticket) |
| `discount_value` | integer | Percentage (0–100) or THB amount |
| `usage_limit` | integer nullable | Max uses. Null = unlimited |
| `used_count` | integer | Incremented atomically |
| `starts_at` | datetime nullable | |
| `expires_at` | datetime nullable | |
| `is_active` | boolean | Manual on/off toggle |
| `show_on_checkout` | boolean | Show in checkout suggestions |
| `created_at`, `updated_at` | timestamps | |

---

### `promotions`

| Column | Type | Notes |
|--------|------|-------|
| `id` | bigint PK | |
| `event_id` | bigint FK → events, nullable | Null = applies to all events |
| `ticket_type_id` | bigint FK → ticket_types, nullable | Null = all ticket types |
| `name` | string(255) | |
| `description` | text nullable | |
| `promotion_type` | string | `buy_x_get_y`, `percent`, `fixed` |
| `discount_scope` | string | `order` or `item` |
| `buy_quantity` | integer nullable | For buy_x_get_y |
| `get_quantity` | integer nullable | Free tickets in buy_x_get_y |
| `min_quantity` | integer nullable | Minimum purchase to activate |
| `discount_value` | integer nullable | Percent or THB amount |
| `max_discount_thb` | integer nullable | Cap on discount amount |
| `usage_limit` | integer nullable | |
| `used_count` | integer | |
| `starts_at` | datetime nullable | |
| `expires_at` | datetime nullable | |
| `combines_with_coupons` | boolean | Can be stacked with coupon? |
| `is_active` | boolean | |
| `show_on_event_page` | boolean | Show promotion banner on event page |
| `created_at`, `updated_at` | timestamps | |

---

### `surveys`

| Column | Type | Notes |
|--------|------|-------|
| `id` | bigint PK | |
| `event_id` | bigint FK → events, nullable | Null = global survey |
| `created_by` | bigint FK → users | |
| `title` | string | |
| `description` | text nullable | |
| `placement` | string | See placements below |
| `questions` | json | Array of question objects |
| `is_active` | boolean | |
| `starts_at` | datetime nullable | |
| `ends_at` | datetime nullable | |
| `created_at`, `updated_at` | timestamps | |

**Placements:**
- `before_event_view` — Before the event detail page loads
- `before_ticket_selection` — Before showing the ticket picker
- `before_payment` — Before the checkout form
- `before_free_order_approval` — Before approving free ticket orders
- `after_payment` — After order submission
- `on_login` — Immediately after login

**`questions` JSON structure:**
```json
[
  {
    "id": "q1",
    "type": "text",
    "label": "How did you hear about us?",
    "required": true
  },
  {
    "id": "q2",
    "type": "choice",
    "label": "Rate your experience",
    "options": ["1","2","3","4","5"],
    "required": false
  }
]
```

---

### `survey_responses`

| Column | Type | Notes |
|--------|------|-------|
| `id` | bigint PK | |
| `survey_id` | bigint FK → surveys | |
| `user_id` | bigint FK → users, nullable | Null for guest |
| `session_id` | string | Laravel session ID |
| `status` | string | `draft`, `completed` |
| `answers` | json | Map of question ID → answer |
| `started_at` | timestamp nullable | |
| `completed_at` | timestamp nullable | |
| `created_at`, `updated_at` | timestamps | |

---

### `check_in_logs`

| Column | Type | Notes |
|--------|------|-------|
| `id` | bigint PK | |
| `ticket_id` | bigint FK → tickets | |
| `scanned_by` | bigint FK → users | Admin who scanned |
| `action` | string | `check_in`, `check_out`, `manual_check_in`, `manual_check_out` |
| `gate` | string(120) nullable | Gate identifier (e.g., "Gate A") |
| `note` | text nullable | |
| `created_at`, `updated_at` | timestamps | |

---

### `event_user` (Pivot)

| Column | Type | Notes |
|--------|------|-------|
| `event_id` | bigint FK → events | |
| `user_id` | bigint FK → users | |
| `created_at`, `updated_at` | timestamps | |

Used to assign `event_admin` and `gate_scanner` users to specific events.

---

## Storage

Files are stored on the `uploads` disk (configured in `config/filesystems.php`).

| Path pattern | Contents |
|-------------|----------|
| `event-posters/*` | Event poster images |
| `ticket-art/*` | Custom ticket artwork |
| `social-share/*` | OG share images |
| `payment-qr/*` | Static QR images (legacy) |
| `payment-slips/*` | Customer payment slip uploads |
| `payment-slips-archive/*` | Post-event archived slips |
