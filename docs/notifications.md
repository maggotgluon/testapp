# Notifications

> **OKF Section**: Communication Channels  
> **Audience**: Developers setting up notifications, admins configuring channels

---

## Notification Channels

TicketFlow supports **three notification channels**:

| Channel | Library | Config Key |
|---------|---------|-----------|
| LINE Messaging API | Custom `LineMessagingService` | `LINE_CHANNEL_ACCESS_TOKEN` |
| Web Push (VAPID) | `laravel-notification-channels/webpush` | `VAPID_PUBLIC_KEY`, `VAPID_PRIVATE_KEY` |
| Email (SMTP) | Laravel Mail | `MAIL_*` variables |

---

## LINE Messaging API

### Configuration

```env
LINE_CHANNEL_ACCESS_TOKEN=your-channel-access-token
```

> **Note:** This is a **Messaging API** channel access token — different from the OAuth `LINE_CLIENT_ID`/`LINE_CLIENT_SECRET` used for login.

### How It Works

1. Admin creates a LINE Official Account
2. LINE Messaging API sends push messages to users who have added the account as a friend
3. Users must have `provider = 'line'` and a valid `provider_id` (LINE User ID)

### When Notifications Are Sent

| Trigger | Recipient | Channel |
|---------|-----------|---------|
| Order approved | Customer | LINE + Web Push |
| New order created | Admin (event_admin + super_admin for this event) | Web Push only |
| Admin broadcast | All attendees (approved or all) | LINE and/or Web Push |

### LINE Webhook (Follow/Unfollow)

**Endpoint:** `POST /line/webhook`

The LINE platform sends events when users follow or unfollow the official account:
- `follow` → `line_friend_status = 'connected'`, `line_followed_at = now()`
- `unfollow` → `line_friend_status = 'unfollowed'`, `line_blocked_at = now()`

The `LineMessagingService::pushText()` method checks that:
1. The user has `provider = 'line'`
2. The user has a `provider_id` (LINE User ID)

If the user has blocked the account, LINE API will return a 400 error which is silently caught.

---

## Web Push (VAPID)

### Configuration

```env
VAPID_PUBLIC_KEY=your-vapid-public-key
VAPID_PRIVATE_KEY=your-vapid-private-key
```

Generate VAPID keys with:
```bash
php artisan webpush:vapid
```

### How It Works

1. User visits the site and accepts browser push permission
2. Browser sends subscription info to `POST /push-subscriptions`
3. Subscription is stored in the `push_subscriptions` table (managed by the `webpush` package)
4. On events (order approved, etc.), `CustomerWebPushNotification` is dispatched

### Subscribing / Unsubscribing

| Method | Path | Description |
|--------|------|-------------|
| POST | `/push-subscriptions` | Register a browser push subscription |
| DELETE | `/push-subscriptions` | Remove all push subscriptions for the current user |

These endpoints require the `auth` middleware.

### `CustomerWebPushNotification`

**File:** `app/Notifications/CustomerWebPushNotification.php`

```php
class CustomerWebPushNotification extends Notification
{
    public function __construct(
        private string $title,
        private string $body,
        private string $url
    ) {}
    
    public function via($notifiable): array
    {
        return [WebPushChannel::class];
    }
    
    public function toWebPush($notifiable, $notification): WebPushMessage
    {
        return (new WebPushMessage)
            ->title($this->title)
            ->body($this->body)
            ->action('View', $this->url);
    }
}
```

---

## Email Notifications

### Configuration

```env
MAIL_MAILER=log        # Use 'smtp' in production
MAIL_HOST=127.0.0.1
MAIL_PORT=2525
MAIL_USERNAME=
MAIL_PASSWORD=
MAIL_FROM_ADDRESS="hello@example.com"
MAIL_FROM_NAME="TicketFlow"
```

### Available Mailable

**`EventAttendeeAnnouncement`** (`app/Mail/EventAttendeeAnnouncement.php`)

Sent when an admin uses the "Email Attendees" feature from the event overview page.

```
POST /admin/events/{event}/email-attendees
  { subject, message, audience: 'approved'|'all' }
  ↓
Collects all customer_email from orders (filtered by audience)
  ↓
Mail::to($email)->send(new EventAttendeeAnnouncement($event, $subject, $message))
```

> **Important:** Emails are sent **synchronously** in the request (no queue). For large events, this may be slow. Consider queuing for production.

---

## Admin Broadcast Messages

Admins can send messages to attendees from the event overview:

**Endpoint:** `POST /admin/events/{event}/message-attendees`

```
{ subject, message, audience: 'approved'|'all', channels: ['line', 'web_push'] }
  ↓
CustomerNotificationService::eventMessage(event, users, subject, message, channels)
  ↓
Sends to each user via selected channels
  ↓
Returns: { line: N, web_push: N }
```

Only channels that are configured in `.env` will be used. Unconfigured channels are silently dropped.

---

## Notification Trigger Map

```
OrderController::store()
  └── CustomerNotificationService::orderCreatedForAdmins(order)
       └── Web Push to super_admin + assigned event_admin users

AdminOrderController::approve()
  └── CustomerNotificationService::orderApproved(order)
       └── LINE + Web Push to order.user (if logged in)

AdminEventController::emailAttendees()
  └── Mail::send(EventAttendeeAnnouncement) to each email

AdminEventController::messageAttendees()
  └── CustomerNotificationService::eventMessage(...)
       └── LINE + Web Push to attendee users
```
