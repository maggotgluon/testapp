# Authentication & Role-Based Access Control

> **OKF Section**: Access Patterns & Security  
> **Audience**: Developers implementing features, security reviewers

---

## Authentication Methods

TicketFlow supports **four distinct login flows**:

### 1. Customer Phone Login (Manual)

**Endpoint:** `POST /login`

```
Name + Phone (required)
Email (optional)
  ↓
Find user by phone OR create new user
  ↓
Log in (remember = true)
  ↓
Claim guest orders by phone/email
  ↓
Push to CRM
  ↓
Check for pending surveys (on_login placement)
  ↓
Redirect to intended URL or /profile
```

**Key behavior:** No password — phone number is the identity key. If the phone doesn't exist, a new user account is created automatically.

---

### 2. Admin Login (Username + Phone)

**Endpoint:** `POST /admin/login`

```
Username + Phone
  ↓
Look up user by BOTH username AND phone
  ↓
Check user.role is admin (super_admin / event_admin / gate_scanner)
  ↓
Log in
  ↓
Redirect to /admin
```

**Local dev shortcut:** In `APP_ENV=local`, passing just a `role` parameter bypasses credentials and logs in as the first user with that role.

---

### 3. Social OAuth (LINE / Facebook / Instagram)

**Endpoints:** `GET /auth/{provider}` → `GET /auth/{provider}/callback`

```
Redirect to OAuth provider
  ↓
Receive provider user (socialite)
  ↓
Find existing user by provider+ID or email
  ↓
Create if not found
  ↓
Update avatar, email, LINE friend status
  ↓
Log in + claim guest orders
  ↓
Push to CRM
  ↓
Survey gate check (on_login)
  ↓
Redirect
```

**OAuth apps required:**
```env
LINE_CLIENT_ID=
LINE_CLIENT_SECRET=
LINE_REDIRECT_URI="${APP_URL}/auth/line/callback"

FACEBOOK_CLIENT_ID=
FACEBOOK_CLIENT_SECRET=
FACEBOOK_REDIRECT_URI="${APP_URL}/auth/facebook/callback"

INSTAGRAM_CLIENT_ID=
INSTAGRAM_CLIENT_SECRET=
INSTAGRAM_REDIRECT_URI="${APP_URL}/auth/instagram/callback"
```

---

### 4. LINE LIFF Login

**Endpoint:** `POST /auth/line/liff` (JSON)

Used when the app is opened **inside LINE** (LIFF browser). The client sends a LINE ID token which is verified against the LINE API.

```
POST /auth/line/liff
  { id_token, profile: { userId, displayName }, redirect }
  ↓
Verify id_token against LINE API (POST https://api.line.me/oauth2/v2.1/verify)
  ↓
Find or create user
  ↓
Log in
  ↓
Survey gate check (on_login)
  ↓
Returns JSON: { redirect: "/some-url" }
```

```env
LINE_LIFF_ID=1234567890-xxxxxxxx    # Used if LINE_CLIENT_ID is not set
```

---

## Guest Checkout

Users **do not need to log in** to purchase tickets. Guest orders have `user_id = null` and are identified by `customer_phone`.

**Claiming guest orders:** When a user logs in, the system automatically:
1. Checks `session('claim_order')` — a specific order passed via URL params
2. Looks up all orders with `user_id = null` matching `customer_phone`
3. Looks up all orders with `user_id = null` matching `customer_email`
4. Assigns them to the logged-in user

---

## Roles

| Role | Value | Description |
|------|-------|-------------|
| Customer | `null` (no role) | Regular user — can buy tickets and view their own orders |
| Gate Scanner | `gate_scanner` | Can access scanner and admin dashboard for assigned events |
| Event Admin | `event_admin` | Can manage events, orders, coupons, promotions, surveys for assigned events |
| Super Admin | `super_admin` | Full access — all events, all orders, user management |

### `isAdmin()` method

Returns `true` if role is `super_admin`, `event_admin`, or `gate_scanner`.  
**Admin users cannot purchase tickets** (enforced in OrderController via `syncCustomerProfile`).

### `canManageEvent(Event|int $event)` method

- `super_admin` → always returns `true`
- `event_admin` / `gate_scanner` → checks `event_user` pivot table

---

## Middleware Stack

### `auth`

Standard Laravel auth middleware. Redirects to `/login` if not authenticated.

### `EnsureRole`

```php
// app/Http/Middleware/EnsureRole.php
// Usage in routes: ->middleware('role:super_admin,event_admin')
// Checks: in_array(auth()->user()->role, $roles)
// Failure: abort(403)
```

---

## Session Security

- Sessions are **database-backed** (`SESSION_DRIVER=database`)
- Session lifetime: 120 minutes (configurable via `SESSION_LIFETIME`)
- CSRF protection: standard Laravel CSRF tokens on all POST/PATCH/DELETE forms
- Safe redirect validation: `AuthController::safeRedirect()` prevents open redirect attacks — only allows same-host redirects or relative paths

---

## Password Handling

- Customer accounts have **no passwords** — phone-based identity only
- Admin accounts **may** have passwords (not currently enforced by UI — admin login uses username + phone)
- The `password` column is hashed with `bcrypt` (12 rounds)

---

## LINE Webhook

**Endpoint:** `POST /line/webhook`  
**Handler:** `LineWebhookController`

Receives follow/unfollow events from LINE Messaging API.

- `follow` event → sets `line_friend_status = 'connected'`, `line_followed_at = now()`
- `unfollow` event → sets `line_friend_status = 'unfollowed'`, `line_blocked_at = now()`

The webhook **does not require auth middleware** (LINE signs requests with a channel secret, validated inside the controller).
