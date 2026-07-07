# Controllers Reference

> **OKF Section**: Request Handlers  
> **Audience**: Developers understanding business logic, agents tracing request flows

---

## Public Controllers (`app/Http/Controllers/`)

### `EventController`

**Routes:** `/`, `/events/{event}`, `/profile`, `/orders/lookup`

| Method | Route | Description |
|--------|-------|-------------|
| `index()` | GET `/` | Lists all visible events (`scopeVisible`). Loads `ticketTypes`, `coupons`, `promotions` |
| `show(Event)` | GET `/events/{event}` | Event detail page with ticket selection form. Checks survey gates: `before_event_view` and `before_ticket_selection` |
| `lookup()` | GET `/orders/lookup` | Allows guest to look up orders by phone number |
| `profile()` | GET `/profile` | Shows user profile, orders, and tickets. Supports `admin/users/{user}/profile` for super_admin to view other users |
| `updateProfile(Request)` | PATCH `/profile` | Updates name, phone, email, and avatar |

---

### `OrderController`

**Routes:** `/orders`, `/orders/{order}`, `/tickets/{uuid}`

| Method | Route | Description |
|--------|-------|-------------|
| `store(Request, ...)` | POST `/orders` | Main checkout handler — validates, calculates discounts, creates order/tickets/payment, decodes QR slip |
| `show(TicketOrder)` | GET `/orders/{order}` | Order confirmation page. Accessible by owner, phone match, or admin |
| `ticket(string $uuid)` | GET `/tickets/{uuid}` | Individual ticket view. Accessible by ticket owner, phone match, or admin |
| `updateTicketHolder(Request, TicketOrder, Ticket)` | PATCH `/orders/{order}/tickets/{ticket}/holder` | Updates ticket holder name. Only after order is approved |
| `ticketQr(string $uuid, QrCodeService)` | GET `/tickets/{uuid}/qr` | Returns SVG QR code for a ticket |
| `paymentQr(Event, Request, QrCodeService)` | GET `/payments/events/{event}/qr` | Returns SVG PromptPay QR for a payment amount |

#### `store()` — Detailed Flow

```
1. Validate request (customer info, items, payment method, slip file)
2. Filter out zero-quantity items
3. Check survey gate: 'before_payment'
4. Store slip file (if provided)
5. DB Transaction:
   a. Sync customer profile via CRM
   b. Lock ticket types for update (prevents race conditions)
   c. Validate payment method is enabled for event
   d. Calculate subtotal per item
   e. Apply coupon discount (if code provided)
   f. Apply promotion discounts (automatic, stacking)
   g. total = subtotal - coupon - promotions
   h. Auto-approve if total == 0
   i. Validate slip required for non-cash payments
   j. Create TicketOrder
   k. Create OrderItems
   l. Create Tickets (one per unit)
   m. Create Payment record
   n. Decode slip QR (SlipQrDecoderService::review())
6. Push order activity to CRM
7. Notify admins (Web Push)
8. Check survey gate: 'after_payment'
9. Redirect to order page (or survey)
```

#### Order Number Format

`{EVENT_INITIALS}-{MMDD}-{SEQ}`

Example: `BCVT-0629-001`
- `BCVT` = first letter of each word in event name (up to 4 chars, padded with X)
- `0629` = month+day
- `001` = sequential counter for that prefix+date combo

---

### `AuthController`

See [auth-roles.md](./auth-roles.md) for full authentication flow documentation.

| Method | Description |
|--------|-------------|
| `show()` | Customer login page |
| `login(Request, CrmSyncService, SurveyGate)` | Phone-based login or register |
| `adminShow()` | Admin login page |
| `adminLogin(Request)` | Admin login with username + phone |
| `social(Request, string $provider)` | OAuth redirect |
| `socialCallback(Request, string $provider, ...)` | OAuth callback, user upsert |
| `lineLiff(Request, ...)` | LINE LIFF token verification + login (returns JSON) |
| `logout(Request)` | Invalidates session |

---

### `SurveyController` (Public)

| Method | Route | Description |
|--------|-------|-------------|
| `show(Survey)` | GET `/surveys/{survey}` | Survey form page. Creates draft response |
| `store(Request, Survey)` | POST `/surveys/{survey}` | Submit answers. Marks response completed. Redirects back |

---

### `CrmController`

Used as a CRM-facing API. Not for regular users.

| Method | Route | Description |
|--------|-------|-------------|
| `lookupCustomer(Request)` | GET `/crm/customers/lookup` | Find local user by phone/email/provider_id |
| `upsertCustomer(Request)` | POST `/crm/customers/upsert` | Create or update user from CRM data |
| `showOrder(TicketOrder)` | GET `/crm/orders/{order}` | Returns order data as JSON |

---

### `LineWebhookController`

**Route:** `POST /line/webhook`  
Handles LINE follow/unfollow webhook events. Updates `line_friend_status` on the user.

---

### `SeoController`

| Method | Route | Description |
|--------|-------|-------------|
| `robots()` | GET `/robots.txt` | Serves robots.txt |
| `sitemap()` | GET `/sitemap.xml` | Generates XML sitemap with all visible event URLs |

---

### `LegalDocumentController`

| Method | Route | Description |
|--------|-------|-------------|
| `show(Request, string $document)` | GET `/legal/*` | Renders a legal document from `resources/legal-docs/` |

---

### `PushSubscriptionController`

| Method | Route | Description |
|--------|-------|-------------|
| `store(Request)` | POST `/push-subscriptions` | Registers a Web Push subscription for the current user |
| `destroy(Request)` | DELETE `/push-subscriptions` | Removes all push subscriptions for the current user |

---

## Admin Controllers (`app/Http/Controllers/Admin/`)

### `DashboardController`

**Route:** `GET /admin`  
Shows event statistics: count by status, recent orders, quick links.

---

### `Admin\EventController`

Full CRUD for events plus attendee tools.

| Method | Description |
|--------|-------------|
| `index()` | Lists events. super_admin sees all. event_admin sees only assigned events |
| `create()` | Shows event creation form |
| `store(Request)` | Creates event + ticket types + assigns creator as manager |
| `edit(Event)` | Edit form. Checks `canManageEvent()` |
| `update(Request, Event)` | Updates event data + ticket types |
| `destroy(Request, Event, PaymentSlipStorageService)` | Deletes event + orders + slips. super_admin only |
| `overview(Request, Event)` | Stats dashboard: orders, tickets, revenue, check-in counts |
| `emailAttendees(Request, Event)` | Sends email to all/approved attendees |
| `messageAttendees(Request, Event, CustomerNotificationService)` | Sends LINE/Web Push to attendees |
| `archivePaymentSlips(Request, Event, PaymentSlipStorageService)` | Archives slips post-event |
| `updateTicketStatus(Request, Event, Ticket)` | Manual ticket status override |
| `updateTicketHolder(Request, Event, Ticket)` | Admin updates ticket holder name/phone |
| `destroyTicket(Request, Event, Ticket)` | Hard-deletes a ticket |

#### Ticket Type Sync (`syncTicketTypes`)

Called on event create/update. Handles:
- Creating new ticket types from `tickets[]` in request
- Updating existing types (by ID)
- Marking removed types as `status = inactive` (not deleted, preserving history)

---

### `Admin\OrderController`

| Method | Description |
|--------|-------------|
| `index(Request)` | Paginated order list with filters (status, event, ticket type) |
| `show(Request, TicketOrder)` | Order detail with prev/next navigation |
| `approve(Request, TicketOrder, ...)` | Approve order — transitions tickets, notifies customer |
| `reject(Request, TicketOrder, ...)` | Reject order |
| `cancel(Request, TicketOrder, ...)` | Cancel approved order |
| `refund(Request, TicketOrder, ...)` | Mark as refunded |
| `checkSlipQr(Request, TicketOrder, ...)` | Re-run QR decode on payment slip |
| `updatePaymentSlip(Request, TicketOrder, ...)` | Replace slip file + re-decode |
| `destroy(Request, TicketOrder)` | Hard-delete order (super_admin only) |

#### Risky Payment Override

When approving an order with `slip_review_status = 'risky'`:
- Admin sees a warning with the review flags
- Must explicitly include `force_approve=true` to override

---

### `Admin\ScannerController`

| Method | Description |
|--------|-------------|
| `index(Request)` | Scanner page with recent scan history (paginated) |
| `scan(Request, CrmSyncService)` | JSON endpoint — validates ticket UUID, performs check-in/check-out |

#### `scan()` — Detailed Logic

```
1. Receive: { code, action?, event_id?, gate? }
2. Parse UUID from code (supports full URL or bare UUID)
3. Find ticket by UUID
4. Check: admin can manage this ticket's event
5. If event_id provided: verify ticket belongs to that event
6. If no action: return ticket info only (QR lookup mode)
7. DB Transaction (lockForUpdate):
   a. check_in: ticket.status must be 'approved'
   b. check_out: ticket.status must be 'checked_in'
   c. Update ticket.status + checked_in/out_at
   d. Create CheckInLog record
8. Push activity to CRM
9. Return JSON response
```

---

### `Admin\CouponController`

Standard resource controller (index, create, store, edit, update, destroy) for managing coupons. No `show` route.

---

### `Admin\PromotionController`

Standard resource controller for managing promotions. No `show` route.

---

### `Admin\SurveyController`

| Method | Description |
|--------|-------------|
| `index()` | List surveys with response counts |
| `create()` | Survey creation form |
| `store(Request)` | Create survey with questions JSON |
| `edit(Survey)` | Edit form |
| `update(Request, Survey)` | Update survey |
| `destroy(Survey)` | Delete survey (and its responses) |

---

### `Admin\UserController`

super_admin only.

| Method | Description |
|--------|-------------|
| `index(Request)` | List all users with filters |
| `edit(User)` | Edit user role + assigned events |
| `update(Request, User)` | Update user role and event assignments |
| `destroy(User)` | Delete user account |
