# HTTP Routes

> **OKF Section**: Interface Definitions  
> **Audience**: Developers integrating features, agents understanding access patterns

---

## Route Summary

All routes are defined in [`routes/web.php`](../routes/web.php). There is **no separate API file** — everything is server-rendered Blade or JSON responses from the same routes.

---

## Public Routes (No Authentication Required)

| Method | Path | Name | Handler | Description |
|--------|------|------|---------|-------------|
| GET | `/robots.txt` | `seo.robots` | `SeoController@robots` | Robots.txt |
| GET | `/sitemap.xml` | `seo.sitemap` | `SeoController@sitemap` | XML sitemap |
| GET | `/` | `events.index` | `EventController@index` | Event listing (homepage) |
| GET | `/about` | `about` | View: `about` | About page |
| GET | `/guides/how-to-buy-ticket` | `guides.buy-ticket` | View | How to buy guide |
| GET | `/guides/gate-check-in` | `guides.gate-check-in` | View | Gate check-in guide |
| GET | `/legal/terms` | `legal.terms` | `LegalDocumentController@show` | Terms of service |
| GET | `/legal/privacy` | `legal.privacy` | `LegalDocumentController@show` | Privacy policy |
| GET | `/legal/refund-policy` | `legal.refund` | `LegalDocumentController@show` | Refund policy |
| GET | `/legal/event-admission-policy` | `legal.event-admission` | `LegalDocumentController@show` | Admission policy |
| GET | `/legal/cookie-policy` | `legal.cookies` | `LegalDocumentController@show` | Cookie policy |
| GET | `/events/{event}` | `events.show` | `EventController@show` | Event detail page |
| GET | `/surveys/{survey}` | `surveys.show` | `SurveyController@show` | Public survey page |
| POST | `/surveys/{survey}` | `surveys.store` | `SurveyController@store` | Submit survey |

---

## Authentication Routes

| Method | Path | Name | Handler | Description |
|--------|------|------|---------|-------------|
| GET | `/login` | `login` | `AuthController@show` | Customer login page |
| POST | `/login` | `login.store` | `AuthController@login` | Customer login (phone-based) |
| GET | `/admin/login` | `admin.login` | `AuthController@adminShow` | Admin login page |
| POST | `/admin/login` | `admin.login.store` | `AuthController@adminLogin` | Admin login (username + phone) |
| POST | `/auth/line/liff` | `auth.line.liff` | `AuthController@lineLiff` | LINE LIFF token login (JSON) |
| POST | `/line/webhook` | `line.webhook` | `LineWebhookController` | LINE Messaging API webhook |
| GET | `/auth/{provider}` | `auth.social` | `AuthController@social` | OAuth redirect (line/facebook/instagram) |
| GET | `/auth/{provider}/callback` | `auth.social.callback` | `AuthController@socialCallback` | OAuth callback |
| POST | `/logout` | `logout` | `AuthController@logout` | Logout |

---

## Order & Ticket Routes (Partially Public)

These routes are public but use a **phone number** as an authorization token for guest access.

| Method | Path | Name | Handler | Access |
|--------|------|------|---------|--------|
| POST | `/orders` | `orders.store` | `OrderController@store` | Public (guest allowed) |
| GET | `/orders/lookup` | `orders.lookup` | `EventController@lookup` | Public (phone auth) |
| GET | `/orders/{order}` | `orders.show` | `OrderController@show` | Owner or admin or phone match |
| PATCH | `/orders/{order}/tickets/{ticket}/holder` | `orders.tickets.holder` | `OrderController@updateTicketHolder` | Owner or phone match |
| GET | `/tickets/{uuid}` | `tickets.show` | `OrderController@ticket` | Owner or phone match or admin |
| GET | `/tickets/{uuid}/qr` | `tickets.qr` | `OrderController@ticketQr` | Public (SVG response) |
| GET | `/payments/events/{event}/qr` | `payments.qr` | `OrderController@paymentQr` | Public (SVG response) |

---

## Authenticated User Routes

Requires `auth` middleware (any logged-in user).

| Method | Path | Name | Handler | Description |
|--------|------|------|---------|-------------|
| GET | `/profile` | `profile` | `EventController@profile` | User profile page |
| PATCH | `/profile` | `profile.update` | `EventController@updateProfile` | Update profile |
| POST | `/push-subscriptions` | `push-subscriptions.store` | `PushSubscriptionController@store` | Register Web Push subscription |
| DELETE | `/push-subscriptions` | `push-subscriptions.destroy` | `PushSubscriptionController@destroy` | Unregister Web Push |

---

## CRM Routes (Internal / External Service)

Used for CRM system webhook integration.

| Method | Path | Name | Handler |
|--------|------|------|---------|
| GET | `/crm/customers/lookup` | `crm.customers.lookup` | `CrmController@lookupCustomer` |
| POST | `/crm/customers/upsert` | `crm.customers.upsert` | `CrmController@upsertCustomer` |
| GET | `/crm/orders/{order}` | `crm.orders.show` | `CrmController@showOrder` |

---

## Admin Routes

### Admin — All Roles (`super_admin`, `event_admin`, `gate_scanner`)

Middleware: `auth` + `role:super_admin,event_admin,gate_scanner`

| Method | Path | Name | Handler |
|--------|------|------|---------|
| GET | `/admin/` | `admin.dashboard` | `DashboardController` |
| GET | `/admin/scanner` | `admin.scanner` | `ScannerController@index` |
| POST | `/admin/scanner` | `admin.scanner.scan` | `ScannerController@scan` |

### Admin — Event & Order Management (`super_admin`, `event_admin`)

Middleware: `auth` + `role:super_admin,event_admin`

| Method | Path | Name | Handler |
|--------|------|------|---------|
| GET | `/admin/events` | `admin.events.index` | `AdminEventController@index` |
| GET | `/admin/events/create` | `admin.events.create` | `AdminEventController@create` |
| POST | `/admin/events` | `admin.events.store` | `AdminEventController@store` |
| GET | `/admin/events/{event}/edit` | `admin.events.edit` | `AdminEventController@edit` |
| PUT/PATCH | `/admin/events/{event}` | `admin.events.update` | `AdminEventController@update` |
| DELETE | `/admin/events/{event}` | `admin.events.destroy` | `AdminEventController@destroy` (`super_admin` only in logic) |
| GET | `/admin/events/{event}/overview` | `admin.events.overview` | `AdminEventController@overview` |
| POST | `/admin/events/{event}/email-attendees` | `admin.events.email-attendees` | `AdminEventController@emailAttendees` |
| POST | `/admin/events/{event}/message-attendees` | `admin.events.message-attendees` | `AdminEventController@messageAttendees` |
| POST | `/admin/events/{event}/archive-payment-slips` | `admin.events.archive-payment-slips` | `AdminEventController@archivePaymentSlips` |
| PATCH | `/admin/events/{event}/tickets/{ticket}/status` | `admin.events.tickets.status` | `AdminEventController@updateTicketStatus` |
| DELETE | `/admin/events/{event}/tickets/{ticket}` | `admin.events.tickets.destroy` | `AdminEventController@destroyTicket` |
| PATCH | `/admin/events/{event}/tickets/{ticket}/holder` | `admin.events.tickets.holder` | `AdminEventController@updateTicketHolder` |
| GET | `/admin/orders` | `admin.orders.index` | `AdminOrderController@index` |
| GET | `/admin/orders/{order}` | `admin.orders.show` | `AdminOrderController@show` |
| POST | `/admin/orders/{order}/approve` | `admin.orders.approve` | `AdminOrderController@approve` |
| POST | `/admin/orders/{order}/reject` | `admin.orders.reject` | `AdminOrderController@reject` |
| POST | `/admin/orders/{order}/cancel` | `admin.orders.cancel` | `AdminOrderController@cancel` |
| POST | `/admin/orders/{order}/refund` | `admin.orders.refund` | `AdminOrderController@refund` |
| POST | `/admin/orders/{order}/check-slip-qr` | `admin.orders.check-slip-qr` | `AdminOrderController@checkSlipQr` |
| POST | `/admin/orders/{order}/payment-slip` | `admin.orders.payment-slip` | `AdminOrderController@updatePaymentSlip` |
| DELETE | `/admin/orders/{order}` | `admin.orders.destroy` | `AdminOrderController@destroy` |
| Resource | `/admin/coupons` | `admin.coupons.*` | `CouponController` (except show) |
| Resource | `/admin/promotions` | `admin.promotions.*` | `PromotionController` (except show) |
| Resource | `/admin/surveys` | `admin.surveys.*` | `AdminSurveyController` (except show) |

### Admin — Super Admin Only

Middleware: `auth` + `role:super_admin`

| Method | Path | Name | Handler |
|--------|------|------|---------|
| GET | `/admin/users/{user}/profile` | `admin.users.profile` | `EventController@profile` |
| GET | `/admin/users` | `admin.users.index` | `AdminUserController@index` |
| GET | `/admin/users/{user}/edit` | `admin.users.edit` | `AdminUserController@edit` |
| PUT/PATCH | `/admin/users/{user}` | `admin.users.update` | `AdminUserController@update` |
| DELETE | `/admin/users/{user}` | `admin.users.destroy` | `AdminUserController@destroy` |

---

## Middleware

### `EnsureRole` (`app/Http/Middleware/EnsureRole.php`)

```php
// Usage: role:super_admin,event_admin,gate_scanner
// Checks: auth()->user()->role is in the given list
// If not → abort(403)
```

Used on all admin routes as a second layer after `auth`.

---

## Access Control Matrix

| Action | Guest | Customer | gate_scanner | event_admin | super_admin |
|--------|-------|----------|-------------|-------------|-------------|
| Browse events | ✅ | ✅ | ✅ | ✅ | ✅ |
| Purchase tickets | ✅ | ✅ | ❌ | ❌ | ❌ |
| View own order | ✅ (phone) | ✅ | ✅ | ✅ | ✅ |
| View profile | ❌ | ✅ | ✅ | ✅ | ✅ |
| Scan QR tickets | ❌ | ❌ | ✅ (assigned) | ✅ (assigned) | ✅ |
| Manage events | ❌ | ❌ | ❌ | ✅ (assigned) | ✅ |
| Approve orders | ❌ | ❌ | ❌ | ✅ (assigned) | ✅ |
| Manage users | ❌ | ❌ | ❌ | ❌ | ✅ |
| Delete events | ❌ | ❌ | ❌ | ❌ | ✅ |
