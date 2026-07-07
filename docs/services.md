# Services

> **OKF Section**: Processing Logic  
> **Audience**: Developers extending features, AI agents understanding business rules

All services live in `app/Services/` and are injected via Laravel's service container.

---

## `CrmSyncService`

**File:** `app/Services/CrmSyncService.php`  
**Purpose:** Syncs customer data and activity to an external CRM system via HTTP webhooks.

### Configuration

```env
CRM_BASE_URL=https://crm.example.com/api
CRM_TOKEN=your-bearer-token
```

Set in `config/services.php` under `crm.base_url` and `crm.token`. **If not set, all CRM calls are silently skipped.**

### Key Methods

| Method | Description |
|--------|-------------|
| `enabled()` | Returns `true` if CRM is configured |
| `pullCustomer(array $identifiers)` | GET `/customers/lookup` with phone/email/line_user_id. Returns customer array or null |
| `applyCustomerToUser(User, array)` | Merges CRM data into local User model (name, phone, email, LINE info) |
| `pushCustomer(User, string $event)` | POST to CRM with user profile and trigger event |
| `pushOrderActivity(TicketOrder, string $event)` | POST order data to CRM (e.g., `ticket_order_created`) |
| `pushTicketActivity(Ticket, string $event)` | POST ticket data to CRM (e.g., `ticket_checked_in`) |

### When It's Called

- Login (all methods): pushes customer profile
- Checkout: pulls customer data for merge, pushes updated profile
- Order approved: pushes activity
- Ticket check-in/check-out: pushes activity

---

## `CustomerNotificationService`

**File:** `app/Services/CustomerNotificationService.php`  
**Purpose:** Sends notifications via LINE Messaging API and Web Push.

### Dependencies
- `LineMessagingService` — for LINE push
- `NotificationChannels\WebPush` — for Web Push

### Key Methods

| Method | Description |
|--------|-------------|
| `orderApproved(TicketOrder)` | Notifies customer their order was approved (LINE + Web Push) |
| `orderCreatedForAdmins(TicketOrder)` | Notifies admins of a new order waiting review (Web Push only) |
| `eventMessage(Event, Collection $users, subject, message, channels)` | Bulk message to attendees |
| `availableChannels()` | Returns which channels are configured (`['line', 'web_push']`) |
| `webPushConfigured()` | Checks VAPID keys are in config |

### Channel Availability

- **LINE**: requires `LINE_CHANNEL_ACCESS_TOKEN` (different from OAuth keys)
- **Web Push**: requires VAPID public + private keys in `config/webpush.php`

---

## `LineMessagingService`

**File:** `app/Services/LineMessagingService.php`  
**Purpose:** Sends LINE push messages to individual users.

```env
LINE_CHANNEL_ACCESS_TOKEN=your-channel-access-token
```

### Key Methods

| Method | Description |
|--------|-------------|
| `isConfigured()` | Returns true if `LINE_CHANNEL_ACCESS_TOKEN` is set |
| `pushText(User, string $text)` | Sends a text message to a user's LINE ID. Returns bool |

The user must have `provider = 'line'` and a valid `provider_id` (LINE User ID). Silently fails if the user has no LINE ID.

---

## `QrCodeService`

**File:** `app/Services/QrCodeService.php`  
**Purpose:** Generates QR code SVG strings — both PromptPay payment QR codes and ticket QR codes.

### Key Methods

| Method | Returns | Description |
|--------|---------|-------------|
| `svg(string $payload)` | string (SVG) | Renders any string as a QR SVG |
| `ticketPayload(Ticket)` | string | Returns the ticket's UUID |
| `paymentPayload(Event, amount, account?)` | string | Builds a PromptPay QR payload |
| `promptPayPayload(string $target, amount?)` | string | Low-level PromptPay EMVCo payload builder |
| `promptPayIdentifier(string $target)` | string | Returns the normalized PromptPay ID |

### PromptPay Target Types

The service auto-detects target type from the length of digits:
- ≥15 digits → e-Wallet ID
- 13 digits → Tax ID
- <13 digits → Phone number (converted to international format `66xxxxxxxxx`)

---

## `SlipQrDecoderService`

**File:** `app/Services/SlipQrDecoderService.php`  
**Purpose:** Decodes QR codes embedded in payment slip images and validates them against the order.

### How It Works

1. **Image preprocessing** — Converts image to greyscale, increases contrast, resizes if needed
2. **QR decoding** — Uses `chillerlan/php-qrcode` to read the QR from the preprocessed image
3. **PromptPay parsing** — Parses the EMVCo payload from Thai bank transfer slips
4. **Validation** — Compares decoded data against the expected payment amount and receiver

### Review Statuses

| Status | Meaning |
|--------|---------|
| `ok` | QR decoded, amount matches, receiver matches |
| `risky` | QR decoded but has suspicious flags (wrong amount, wrong receiver, etc.) |
| `needs_manual_review` | Could not decode or missing critical fields |

### Review Flags (in `slip_review_flags` JSON)

| Flag | Description |
|------|-------------|
| `amount_mismatch` | Decoded amount ≠ expected amount |
| `receiver_mismatch` | PromptPay ID doesn't match event's account |
| `duplicate_slip` | Same QR payload or image hash seen before |
| `invalid_crc` | PromptPay CRC check failed |
| `decode_error` | Image file not found or could not be read |

### Thai Banks Dictionary

The service includes a dictionary of Thai bank codes (`002` → `Bangkok Bank`, etc.) used when parsing QR data and displaying human-readable bank names.

---

## `PaymentSlipStorageService`

**File:** `app/Services/PaymentSlipStorageService.php`  
**Purpose:** Manages the lifecycle of payment slip files.

### Key Methods

| Method | Description |
|--------|-------------|
| `store(UploadedFile)` | Stores a new slip file to `payment-slips/` disk. Returns path. |
| `archiveApprovedSlipsForEndedEvent(Event)` | Moves slips from `payment-slips/` to `payment-slips-archive/` for ended events. Returns stats array. |
| `deleteActiveSlipForOrder(TicketOrder)` | Soft-deletes the slip file (marks `slip_deleted_at`) when order is deleted |

### Storage Lifecycle

```
Upload → payment-slips/{filename}          (active, visible to admin)
       ↓ (after event ends, admin triggers)
       → payment-slips-archive/{filename}  (archived, less accessible)
       ↓ (if order deleted)
       → slip_deleted_at set, file removed
```

---

## `SurveyGate`

**File:** `app/Services/SurveyGate.php`  
**Purpose:** Determines if a survey should be shown to the current user at a given placement, and manages session state for the interstitial redirect flow.

### Key Methods

| Method | Description |
|--------|-------------|
| `due(string $placement, Request, ?Event)` | Returns the first uncompleted survey for this placement/event, or null |
| `hasCompleted(Survey, Request)` | Checks if user (or guest session) has already completed this survey |
| `responseFor(Survey, Request)` | Gets or creates a draft `SurveyResponse` for the current user/session |
| `rememberReturn(Survey, Request, ?string $returnTo)` | Saves the redirect-back URL in session |
| `returnTo(Survey, Request)` | Retrieves and removes the return URL from session |
| `claimGuestResponses(User, Request)` | On login, assigns guest session responses to the newly-authenticated user |

### Survey Completion Check

A survey is considered "completed" if:
1. `session('survey_completed.{id}')` is set (set by `SurveyController` after submit), OR
2. A `SurveyResponse` with `status = 'completed'` exists for this user ID or session ID

---

## `EventDescriptionService`

**File:** `app/Services/EventDescriptionService.php`  
**Purpose:** Sanitizes HTML event descriptions and converts Markdown to safe HTML.

### Key Methods

| Method | Description |
|--------|-------------|
| `safeHtml(?string $html)` | Strips dangerous HTML tags, allows safe subset |
| `markdown(?string $md)` | Converts Markdown to HTML (uses `League\CommonMark` or similar) |

---

## `LegalDocumentService`

**File:** `app/Services/LegalDocumentService.php`  
**Purpose:** Reads and returns legal document content from `resources/legal-docs/`.

Documents supported: `terms`, `privacy`, `refund`, `event-admission`, `cookies`

---

## `TicketingAiAssistant`

**File:** `app/Services/TicketingAiAssistant.php`  
**Purpose:** Optional AI helper for generating event descriptions or answering FAQs.  
**Status:** Stub/placeholder. Not wired into production flows.
