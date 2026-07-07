# TicketFlow — Event Booking Application

> **Open Knowledge Format (OKF) Compliant Documentation**  
> Version: 1.0 | Last Updated: 2026-06-29 | Status: Active

---

## Table of Contents

### Core Documentation (New)

| # | Document | Description |
|---|----------|-------------|
| 1 | [overview.md](./overview.md) | Project overview, architecture, and business context |
| 2 | [data-model.md](./data-model.md) | Database schema, entity relationships, and field definitions |
| 3 | [api-routes.md](./api-routes.md) | All HTTP routes, endpoints, and access control |
| 4 | [controllers.md](./controllers.md) | Controller responsibilities and key business logic |
| 5 | [services.md](./services.md) | Service layer — how each service works |
| 6 | [models.md](./models.md) | Eloquent models, relationships, and business methods |
| 7 | [auth-roles.md](./auth-roles.md) | Authentication flows and role-based access control |
| 8 | [payments.md](./payments.md) | Payment methods, slip QR decoding, and approval flow |
| 9 | [notifications.md](./notifications.md) | LINE messaging, Web Push, and email notifications |
| 10 | [surveys.md](./surveys.md) | Survey system — placements, gates, and response tracking |
| 11 | [setup.md](./setup.md) | Local development setup and environment variables |

### Pre-existing Documentation

| Document | Description |
|----------|-------------|
| [CRM_INTEGRATION.md](./CRM_INTEGRATION.md) | CRM webhook integration guide |
| [FITNESS_EVENT_ORGANIZER_BENEFITS.md](./FITNESS_EVENT_ORGANIZER_BENEFITS.md) | Benefits guide for fitness event organizers |
| [LOCALIZATION.md](./LOCALIZATION.md) | Localization / i18n documentation |
| [PASSENGER_DEPLOYMENT.md](./PASSENGER_DEPLOYMENT.md) | Passenger/Nginx deployment guide |

---

## Quick Summary

**TicketFlow** is a Laravel-based event booking platform focused on the Thai market. It supports:

- Public event browsing and ticket purchasing
- QR / bank transfer / cash payment with slip upload & AI-assisted QR decoding
- LINE OAuth, Facebook, Instagram social login + LIFF (LINE in-app browser)
- Admin panel for event CRUD, order approval, and attendee management
- Gate scanner (QR check-in / check-out)
- Coupon and promotion discount engine
- Survey gate system (intercepts user flow at configurable placements)
- CRM webhook integration and customer sync
- Web Push notifications + LINE messaging

---

## OKF Metadata

```yaml
name: TicketFlow Documentation
version: "1.0"
created: "2026-06-29"
language: en
license: proprietary
format: Markdown
topics:
  - event-booking
  - laravel
  - thailand-payments
  - qr-code
  - line-api
maintainer: Mag
```
