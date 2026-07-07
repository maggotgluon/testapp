# Project Overview

> **OKF Section**: Context & Purpose  
> **Audience**: New developers, AI agents, technical stakeholders

---

## 1. What Is This?

**TicketFlow** is a full-stack web application for selling and managing event tickets, built with **Laravel 13** (PHP 8.3). It is designed for the **Thai market** — supporting PromptPay QR payments, Thai banks, LINE social login, and bilingual (Thai/English) UI.

---

## 2. Tech Stack

| Layer | Technology |
|-------|-----------|
| Backend Framework | Laravel 13.x (PHP 8.3) |
| Database | MySQL (production) / SQLite (dev) |
| Frontend Assets | Vite + Vanilla JS + Vanilla CSS |
| Queue | Database queue driver |
| File Storage | Local `uploads` disk (configurable to S3) |
| Session | Database-backed sessions |
| Auth | Laravel native Auth + Laravel Socialite |
| Social Login | LINE, Facebook, Instagram |
| LINE Integration | LIFF (LINE Front-end Framework), LINE Messaging API, LINE Webhook |
| QR Code | `chillerlan/php-qrcode` — generates SVG PromptPay QR codes |
| Notifications | Web Push (VAPID), LINE Messaging API, Email (SMTP/log) |
| CRM | External CRM via HTTP webhook (optional) |

---

## 3. Application Architecture

```
┌──────────────────────────────────────────────────────────┐
│                     PUBLIC USERS                          │
│  Browse Events → Select Tickets → Checkout → View Order   │
└────────────────────────┬─────────────────────────────────┘
                         │ HTTP
┌────────────────────────▼─────────────────────────────────┐
│                    LARAVEL APP                            │
│                                                           │
│  Routes (web.php)                                         │
│     ├── Public Routes (no auth)                           │
│     ├── Auth Routes (login/logout/social/liff)            │
│     ├── Order Routes (checkout, ticket view)              │
│     └── Admin Routes (role-gated)                         │
│                                                           │
│  Controllers                                              │
│     ├── EventController       (public event pages)        │
│     ├── OrderController       (checkout, ticket views)    │
│     ├── AuthController        (login flows)               │
│     ├── SurveyController      (public survey submit)      │
│     └── Admin/                                            │
│          ├── DashboardController                          │
│          ├── EventController  (CRUD + attendee tools)     │
│          ├── OrderController  (approve/reject/refund)     │
│          ├── ScannerController (QR gate scanner)          │
│          ├── CouponController                             │
│          ├── PromotionController                          │
│          ├── SurveyController                             │
│          └── UserController                               │
│                                                           │
│  Services (App\Services\)                                 │
│     ├── CrmSyncService        (external CRM HTTP calls)   │
│     ├── CustomerNotificationService (LINE + Web Push)     │
│     ├── EventDescriptionService (HTML sanitize/markdown)  │
│     ├── LegalDocumentService  (render legal docs)         │
│     ├── LineMessagingService  (LINE push message)         │
│     ├── PaymentSlipStorageService (store/archive slips)   │
│     ├── QrCodeService         (PromptPay QR generation)   │
│     ├── SlipQrDecoderService  (QR decode + slip review)   │
│     ├── SurveyGate            (survey placement logic)    │
│     └── TicketingAiAssistant  (optional AI helper)        │
│                                                           │
│  Models (App\Models\)                                     │
│     User, Event, TicketType, TicketOrder, OrderItem,      │
│     Ticket, Payment, Coupon, Promotion, Survey,           │
│     SurveyResponse, CheckInLog                            │
└──────────────────────────────────────────────────────────┘
```

---

## 4. Key Business Flows

### 4.1 Ticket Purchase Flow
```
1. User browses /  → sees published, non-expired events
2. User clicks event → /events/{event}
3. [Optional] Survey gate intercepts (before_event_view)
4. User selects ticket types + quantities
5. [Optional] Survey gate intercepts (before_ticket_selection)
6. User fills checkout form (name, phone, payment method, slip upload)
7. [Optional] Survey gate intercepts (before_payment)
8. POST /orders → OrderController::store()
   - Validates input & inventory (lockForUpdate)
   - Applies coupon + promotion discounts
   - Decodes slip QR (SlipQrDecoderService)
   - Creates TicketOrder + OrderItems + Tickets + Payment
   - Auto-approves if total = 0 (free tickets)
9. Admin reviews → approves/rejects order
10. Tickets become active (status = approved)
```

### 4.2 Admin Approval Flow
```
Admin → /admin/orders/{order} → reviews slip + QR data
     → POST /admin/orders/{order}/approve
     → Tickets marked approved, sold_count incremented
     → Customer notified (LINE + Web Push)
     → CRM activity pushed
```

### 4.3 Gate Check-in Flow
```
Scanner → /admin/scanner
       → Scans QR (ticket UUID)
       → POST /admin/scanner → ScannerController::scan()
       → Validates ticket status (must be 'approved')
       → Updates to 'checked_in' + logs CheckInLog
       → Optional check-out scan → 'checked_out'
```

---

## 5. Directory Structure

```
testapp/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Admin/         ← Admin controllers
│   │   │   └── *.php          ← Public controllers
│   │   └── Middleware/
│   │       └── EnsureRole.php ← Role-based gate
│   ├── Mail/                  ← Mailable classes
│   ├── Models/                ← Eloquent models
│   ├── Notifications/         ← Web Push notification class
│   ├── Providers/             ← AppServiceProvider
│   └── Services/              ← Business logic services
├── database/
│   ├── migrations/            ← 26 migrations
│   ├── factories/             ← Test factories
│   └── seeders/               ← Database seeders
├── resources/
│   ├── views/
│   │   ├── admin/             ← Admin panel Blade views
│   │   ├── auth/              ← Login pages
│   │   ├── events/            ← Public event views
│   │   ├── orders/            ← Order confirmation views
│   │   ├── tickets/           ← Individual ticket views
│   │   ├── surveys/           ← Survey views
│   │   ├── layouts/           ← Base layouts
│   │   └── components/        ← Blade components
│   ├── css/                   ← Vanilla CSS
│   └── js/                    ← Vanilla JS + Vite
├── routes/
│   └── web.php                ← All routes (no API file)
├── docs/                      ← This documentation
└── public/                    ← Web root (Vite build output)
```

---

## 6. Important Design Decisions

| Decision | Rationale |
|----------|-----------|
| No API routes file | All routes are in `web.php` — the app is server-rendered (Blade) with inline JS for interactive parts (scanner, LIFF) |
| SQLite for dev, MySQL for prod | Configured in `.env`. The `database.sqlite` file is committed for convenience |
| All prices in THB integer (Satang ignored) | `price_thb`, `subtotal_thb`, `total_thb` are integers. No decimal currency math. |
| Slip QR decoded server-side | Thai PromptPay QR codes embedded in payment slip photos are read using `chillerlan/php-qrcode` after image pre-processing |
| Surveys are interstitial gates | Survey placement intercepts redirect the user mid-flow; the return URL is saved in session |
| Guest checkout allowed | `user_id` is nullable on orders. Users who log in later can claim their orders via phone/email matching |
| Role hierarchy | `super_admin > event_admin > gate_scanner`. `event_admin` and `gate_scanner` are scoped to assigned events |
