# TicketFlow

Laravel 13 event ticket booking and event-day check-in MVP.

## Scope

- Client event browsing, checkout, coupon discount, payment slip upload, order lookup, profile, purchase history, and ticket pages.
- Admin roles: `super_admin`, `event_admin`, and `gate_scanner`.
- Admin event and ticket type management, event operations overview, coupon management, user role management, timed ticket sale windows, order approval/rejection/refund, payment slip review, and scanner check-in/check-out.
- MySQL-ready schema in `.env.example`; local development can use SQLite.
- Blade, Alpine.js, Tailwind CSS, Vite hot reload.
- AI ops assistant service on the admin dashboard with a clean replacement point for a real model integration.
- PromptPay payment QR payload generation with amount using EMV Merchant Presented Mode.

## Demo Accounts

Use the quick login screen with these seeded phone numbers:

- Super admin: username `admin`, phone `0900000000`
- Event admin: username `eventadmin`, phone `0922222222`
- Gate scanner: username `scanner`, phone `0911111111`
- Coupon: `EARLYBIRD`

Admin accounts use `/admin/login`. In `APP_ENV=local`, that page also shows a role dropdown for fast testing. In production, the dropdown is hidden and admins use username + phone.

## Local Setup

```bash
composer install
npm install
php artisan migrate:fresh --seed
php artisan storage:link
composer run dev
```

Open `http://127.0.0.1:8001`.

## Social Login

Facebook, LINE, and Instagram OAuth routes are wired through Laravel Socialite:

- `/auth/facebook`
- `/auth/line`
- `/auth/instagram`

Add provider credentials to `.env` using the variables shown in `.env.example`, then set each provider callback URL to:

- `APP_URL/auth/facebook/callback`
- `APP_URL/auth/line/callback`
- `APP_URL/auth/instagram/callback`

## Production Next Steps

1. Add admin OTP or password verification; username + phone is convenient but not strong enough by itself.
2. Connect LINE LIFF profile data to the existing LINE Socialite login flow.
3. Replace generated QR payment information with a real Thai QR payment provider callback.
4. Swap the local AI ops assistant heuristics with an OpenAI-backed service for event copy, fraud/risk review, and demand forecasting.
5. Add notification delivery for approved tickets through LINE Messaging API, email, or SMS.
6. Add stronger scanner offline mode.
