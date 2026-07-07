# Models Reference

> **OKF Section**: Data Entities  
> **Audience**: Developers writing queries, agents understanding relationships

---

## `User`

**File:** `app/Models/User.php`  
**Table:** `users`

### Relationships
| Relation | Type | Returns |
|----------|------|---------|
| `orders()` | hasMany | Collection of `TicketOrder` |
| `tickets()` | hasMany | Collection of `Ticket` |
| `assignedEvents()` | belongsToMany | Collection of `Event` (via `event_user` pivot) |
| `pushSubscriptions()` | (WebPush trait) | Push subscription records |

### Key Methods
| Method | Returns | Description |
|--------|---------|-------------|
| `isAdmin()` | bool | True if role is `super_admin`, `event_admin`, or `gate_scanner` |
| `canManageEvent(Event\|int)` | bool | True if super_admin, or if assigned to the event |

### Notable Attributes
- `role` — nullable, values: `super_admin`, `event_admin`, `gate_scanner`, or null (customer)
- `provider` — OAuth provider: `line`, `facebook`, `instagram`, `guest`
- `password` — hashed, hidden from JSON serialization

---

## `Event`

**File:** `app/Models/Event.php`  
**Table:** `events`

### Relationships
| Relation | Type | Returns |
|----------|------|---------|
| `ticketTypes()` | hasMany | Collection of `TicketType` |
| `coupons()` | hasMany | Collection of `Coupon` |
| `promotions()` | hasMany | Collection of `Promotion` |
| `assignedUsers()` | belongsToMany | Admin users assigned to this event |

### Scopes
| Scope | Description |
|-------|-------------|
| `scopeVisible($query)` | Filters: `is_published = true AND ends_at >= now()` |

### Key Methods
| Method | Returns | Description |
|--------|---------|-------------|
| `enabledPaymentMethods()` | array | `['qr_payment', 'bank_transfer', 'cash']` subset that is enabled |
| `paymentOptions()` | array | Normalized list of payment account objects from `payment_accounts` JSON (falls back to legacy columns) |
| `paymentOption(?string $key, ?string $method)` | ?array | Find a specific payment account by key or method |

### `paymentOptions()` Return Shape
```php
[
  [
    'key' => 'qr-payment-0',
    'method' => 'qr_payment',
    'label' => 'QR Payment',
    'account_name' => 'John Doe',
    'account_number' => '0812345678',
    'instructions' => '...',
    'is_active' => true,
  ],
  ...
]
```

---

## `TicketType`

**File:** `app/Models/TicketType.php`  
**Table:** `ticket_types`

### Relationships
| Relation | Type | Returns |
|----------|------|---------|
| `event()` | belongsTo | `Event` |

### Key Methods
| Method | Returns | Description |
|--------|---------|-------------|
| `isOnSale()` | bool | Checks `status = active`, sale date windows, and capacity |
| `availableQuantity()` | int | `capacity - sold_count` |

---

## `TicketOrder`

**File:** `app/Models/TicketOrder.php`  
**Table:** `ticket_orders`

### Relationships
| Relation | Type | Returns |
|----------|------|---------|
| `user()` | belongsTo | `User` (nullable) |
| `coupon()` | belongsTo | `Coupon` (nullable) |
| `items()` | hasMany | Collection of `OrderItem` |
| `tickets()` | hasMany | Collection of `Ticket` |
| `payments()` | hasMany | Collection of `Payment` |

### Status Values
`pending` → `approved` / `rejected` / `cancelled` / `refunded`

---

## `OrderItem`

**File:** `app/Models/OrderItem.php`  
**Table:** `order_items`

Line item within an order. Records the price **at time of purchase** (not the current `TicketType.price_thb`).

### Relationships
| Relation | Type | Returns |
|----------|------|---------|
| `order()` | belongsTo | `TicketOrder` |
| `event()` | belongsTo | `Event` |
| `ticketType()` | belongsTo | `TicketType` |

---

## `Ticket`

**File:** `app/Models/Ticket.php`  
**Table:** `tickets`

One `Ticket` = one physical ticket (one attendee). An order with 3 tickets has 3 `Ticket` records.

### Relationships
| Relation | Type | Returns |
|----------|------|---------|
| `order()` | belongsTo | `TicketOrder` |
| `event()` | belongsTo | `Event` |
| `ticketType()` | belongsTo | `TicketType` |
| `logs()` | hasMany | Collection of `CheckInLog` |

### Status Lifecycle
```
pending → approved → checked_in → checked_out
        → rejected
        → cancelled
        → refunded
        → expired
```

### UUID

The `uuid` field is the QR code payload. It is also used in:
- `GET /tickets/{uuid}` — view ticket
- `GET /tickets/{uuid}/qr` — get QR SVG
- `POST /admin/scanner` — scan for check-in

---

## `Payment`

**File:** `app/Models/Payment.php`  
**Table:** `payments`

One `Payment` record per order (created at checkout). Holds the slip, QR decode results, and review status.

### Relationships
| Relation | Type | Returns |
|----------|------|---------|
| `order()` | belongsTo | `TicketOrder` |

### Status Values
`submitted` → `approved` / `rejected`  
`waived` — for free orders  
`cash_pending` — for cash payment orders

---

## `Coupon`

**File:** `app/Models/Coupon.php`  
**Table:** `coupons`

### Relationships
| Relation | Type | Returns |
|----------|------|---------|
| `event()` | belongsTo | `Event` (nullable — global coupon) |
| `ticketType()` | belongsTo | `TicketType` (nullable — scoped coupon) |

### Key Methods
| Method | Signature | Description |
|--------|-----------|-------------|
| `isValidFor(int $subtotal)` | bool | Checks active, usage limit, date range, and subtotal > 0 |
| `eligibleSubtotal($items, $ticketTypes)` | int | Sum of eligible line totals (respects ticket_type_id filter) |
| `discountForItems($items, $ticketTypes)` | int | Calculates discount in THB for the given cart |
| `discountFor(int $subtotal)` | int | Simple discount calculation for a flat subtotal |

### Discount Calculation

```
discount_type = 'percent':
  discount = min(subtotal, round(subtotal * discount_value / 100))

discount_type = 'fixed':
  discount = min(subtotal, discount_value)

discount_scope = 'item':
  discount applied per-unit (not per order)

discount_scope = 'order':
  discount applied once to eligible subtotal
```

---

## `Promotion`

**File:** `app/Models/Promotion.php`  
**Table:** `promotions`

### Relationships
| Relation | Type | Returns |
|----------|------|---------|
| `event()` | belongsTo | `Event` |
| `ticketType()` | belongsTo | `TicketType` |

### Promotion Types

| Type | Description |
|------|-------------|
| `buy_x_get_y` | Buy X, get Y cheapest tickets free |
| `percent` | Percentage discount |
| `fixed` | Fixed THB discount |

### Key Methods
| Method | Description |
|--------|-------------|
| `isValidFor(bool $hasCoupon)` | Checks active, date range, usage limit, and `combines_with_coupons` |
| `discountForItems(Collection $items, Collection $ticketTypes, bool $hasCoupon)` | Main entry: returns discount in THB |
| `eligibleQuantity(...)` | How many tickets qualify |
| `eligibleSubtotal(...)` | Sum of eligible line totals |
| `displaySummary()` | Human-readable string: "Buy 2 get 1 free", "20% off", "THB 100 off" |

### Promo + Coupon Stacking

Promotions have `combines_with_coupons`. If a coupon is applied and `combines_with_coupons = false`, the promotion is skipped. Multiple promotions can stack but their combined discount cannot exceed the eligible subtotal.

---

## `Survey`

**File:** `app/Models/Survey.php`  
**Table:** `surveys`

### Relationships
| Relation | Type | Returns |
|----------|------|---------|
| `event()` | belongsTo | `Event` (nullable) |
| `creator()` | belongsTo | `User` |
| `responses()` | hasMany | Collection of `SurveyResponse` |

### Scopes
| Scope | Description |
|-------|-------------|
| `scopeAvailable($query)` | Active + within date range |
| `scopeForPlacement($query, $placement, $eventId)` | Filters by placement and event (or global) |

### `placementLabel()`

Returns the human-readable label from `PLACEMENTS` constant.

---

## `SurveyResponse`

**File:** `app/Models/SurveyResponse.php`  
**Table:** `survey_responses`

### Relationships
| Relation | Type | Returns |
|----------|------|---------|
| `survey()` | belongsTo | `Survey` |
| `user()` | belongsTo | `User` (nullable — guest allowed) |

### Statuses

- `draft` — started but not submitted
- `completed` — submitted

---

## `CheckInLog`

**File:** `app/Models/CheckInLog.php`  
**Table:** `check_in_logs`

### Relationships
| Relation | Type | Returns |
|----------|------|---------|
| `ticket()` | belongsTo | `Ticket` |

### Action Values

| Action | Trigger |
|--------|---------|
| `check_in` | Scanner: QR scan → check-in |
| `check_out` | Scanner: QR scan → check-out |
| `manual_check_in` | Admin: event overview → set status manually |
| `manual_check_out` | Admin: event overview → set status manually |
