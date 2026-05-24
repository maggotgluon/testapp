# CRM Integration

This ticket app can work with a central CRM without making ticket sales depend on the CRM being online.

## Environment

Set these values in the ticket app:

```env
CRM_API_URL=https://crm.example.com/api
CRM_API_TOKEN=crm-outbound-secret
CRM_WEBHOOK_TOKEN=crm-inbound-secret
```

- `CRM_API_URL` is the CRM base API URL used by the ticket app for outbound sync.
- `CRM_API_TOKEN` is sent as `Authorization: Bearer ...` when the ticket app calls the CRM.
- `CRM_WEBHOOK_TOKEN` is required when the CRM calls the ticket app.

If `CRM_API_URL` or `CRM_API_TOKEN` is missing, ticket checkout and check-in still work. The app simply skips outbound CRM sync.

## CRM Endpoints To Receive Data From Ticket App

The CRM should implement these endpoints.

### Upsert Customer

`POST /customers/upsert`

Headers:

```http
Authorization: Bearer crm-outbound-secret
Accept: application/json
Content-Type: application/json
```

Payload:

```json
{
  "source": "checkout",
  "customer": {
    "ticket_app_user_id": 12,
    "name": "Jane Buyer",
    "username": null,
    "phone": "0812345678",
    "email": "jane@example.com",
    "provider": "line",
    "line_user_id": "Uxxxxxxxx",
    "avatar": "https://...",
    "line_friend_status": "followed",
    "line_followed_at": "2026-05-24T10:00:00+07:00",
    "line_blocked_at": null,
    "web_push_enabled": true,
    "updated_at": "2026-05-24T10:05:00+07:00"
  }
}
```

Recommended CRM matching order:

1. `line_user_id`
2. `phone`
3. `email`
4. `ticket_app_user_id`

### Create Customer Activity

`POST /customer-activities`

Payload for an order activity:

```json
{
  "type": "ticket_order_approved",
  "occurred_at": "2026-05-24T10:00:00+07:00",
  "customer": {
    "ticket_app_user_id": 12,
    "name": "Jane Buyer",
    "phone": "0812345678",
    "email": "jane@example.com",
    "line_user_id": "Uxxxxxxxx"
  },
  "order": {
    "ticket_app_order_id": 44,
    "order_number": "SSXX-0524-001",
    "status": "approved",
    "subtotal_thb": 798,
    "discount_thb": 0,
    "total_thb": 798,
    "payment_method": "qr_payment",
    "coupon_code": null,
    "events": [
      {
        "ticket_app_event_id": 1,
        "name": "SHIMMER & SHINE",
        "venue": "Meeting Room",
        "starts_at": "2026-06-14T18:00:00+07:00",
        "ends_at": "2026-06-14T23:00:00+07:00"
      }
    ],
    "tickets": [
      {
        "ticket_app_ticket_id": 100,
        "uuid": "ticket-uuid",
        "status": "approved",
        "holder_name": "Jane Buyer",
        "holder_phone": "0812345678"
      }
    ]
  }
}
```

Activity types currently sent:

- `manual_login`
- `line_login`
- `line_liff_login`
- `checkout`
- `ticket_order_created`
- `ticket_order_approved`
- `ticket_checked_in`
- `ticket_checked_out`
- `line_followed`
- `line_blocked`
- `web_push_enabled`
- `web_push_disabled`

The CRM should store these as a customer timeline. Duplicate protection can use `type` plus `ticket_app_order_id` or `ticket_app_ticket_id` plus `occurred_at`.

## Ticket App Endpoints For CRM To Call

All CRM-to-ticket-app calls require:

```http
Authorization: Bearer crm-inbound-secret
```

or:

```http
X-CRM-Token: crm-inbound-secret
```

### Lookup Customer

`GET /crm/customers/lookup?phone=0812345678`

Supported query keys:

- `ticket_app_user_id`
- `phone`
- `email`
- `line_user_id`

Response:

```json
{
  "customer": {
    "ticket_app_user_id": 12,
    "name": "Jane Buyer",
    "phone": "0812345678",
    "email": "jane@example.com",
    "line_user_id": "Uxxxxxxxx",
    "web_push_enabled": true
  },
  "summary": {
    "orders_count": 3,
    "tickets_count": 5,
    "events_attended": ["SHIMMER & SHINE"]
  }
}
```

### Push CRM Customer Updates Into Ticket App

`POST /crm/customers/upsert`

Payload:

```json
{
  "customer": {
    "name": "Jane Buyer",
    "phone": "0812345678",
    "email": "jane@example.com",
    "line_user_id": "Uxxxxxxxx",
    "avatar": "https://...",
    "line_friend_status": "followed"
  }
}
```

The ticket app will match an existing user by `ticket_app_user_id`, `line_user_id`, `phone`, or `email`. If none exists, it creates a customer user.

### Get Order Detail

`GET /crm/orders/{ticket_app_order_id}`

Returns:

```json
{
  "customer": {},
  "order": {}
}
```

## Recommended Operating Model

- The ticket app owns ticket sale operations and should continue selling even if CRM is down.
- The CRM owns the centralized customer profile and team timeline.
- LINE user ID is the strongest identity key once LINE login is enabled.
- Phone and email are fallback matching keys.
- Keep consent fields in CRM, but send updates back to the ticket app when notification permissions change.
