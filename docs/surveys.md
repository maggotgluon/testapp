# Survey System

> **OKF Section**: User Research / Data Collection  
> **Audience**: Developers adding features, admins managing surveys

---

## Overview

Surveys are **interstitial forms** that can intercept users at specific points in the flow. When a survey is "due" for a user, they are redirected to the survey page, and after completing it, redirected back to where they were.

Surveys can be:
- **Global** (no `event_id`) — shown across all events
- **Event-specific** (with `event_id`) — shown only for a specific event

Event-specific surveys take priority over global ones (ordered by `event_id IS NULL ASC`).

---

## Survey Placements

Surveys are triggered at **placement** points in the user journey:

| Placement | Where it fires | Handler |
|-----------|---------------|---------|
| `before_event_view` | Before the event detail page is rendered | `EventController::show()` |
| `before_ticket_selection` | Before the ticket picker shows | `EventController::show()` |
| `before_payment` | Before the checkout form is submitted | `OrderController::store()` |
| `before_free_order_approval` | Before auto-approving a free order | (Admin flow) |
| `after_payment` | After the order is created | `OrderController::store()` |
| `on_login` | Immediately after login (any method) | `AuthController::login/socialCallback/lineLiff` |

### Example: `before_payment` Placement

```php
// In OrderController::store()
if ($checkoutEvent && ($survey = $surveys->due('before_payment', $request, $checkoutEvent))) {
    $surveys->rememberReturn($survey, $request, url()->previous().'#checkout');
    return redirect()->route('surveys.show', $survey)
        ->with('status', 'Please complete this survey before checkout.');
}
// ... proceed with order creation
```

---

## Survey Lifecycle

```
1. Admin creates survey with:
   - placement
   - questions (JSON array)
   - event_id (optional)
   - is_active = true
   - optional date range (starts_at, ends_at)

2. User hits a placement trigger
   ↓
3. SurveyGate::due() checks:
   - Survey is_active = true
   - Within date range (or no dates set)
   - Matches placement
   - Matches event (or is global)
   - User has NOT already completed it (session or DB)

4. If survey is due:
   ↓
   a. SurveyGate::rememberReturn() saves return URL to session
   b. User redirected to GET /surveys/{survey}
   c. User fills out form
   d. POST /surveys/{survey} → SurveyController::store()
   e. SurveyResponse.status = 'completed'
   f. session('survey_completed.{id}') = true
   g. User redirected back via SurveyGate::returnTo()

5. If survey not due (already completed):
   ↓
   Flow continues uninterrupted
```

---

## Question Types

Questions are stored as a JSON array in `surveys.questions`.

### Text Question
```json
{
  "id": "q_text_1",
  "type": "text",
  "label": "How did you hear about this event?",
  "required": true
}
```

### Choice Question (Single/Multiple Select)
```json
{
  "id": "q_choice_1",
  "type": "choice",
  "label": "Rate your experience (1-5)",
  "options": ["1", "2", "3", "4", "5"],
  "multiple": false,
  "required": true
}
```

### Rating Scale
```json
{
  "id": "q_rating_1",
  "type": "rating",
  "label": "How likely are you to recommend us?",
  "min": 1,
  "max": 10,
  "required": false
}
```

---

## Completion Tracking

A survey is considered completed for a user if:

1. **Session flag**: `session('survey_completed.{surveyId}')` is truthy  
   (set immediately after submission, valid for the session lifetime)

2. **Database record**: A `SurveyResponse` with `status = 'completed'` exists where:
   - `user_id` matches (if logged in), OR
   - `session_id` matches (if guest)

### Guest → Login Survey Claim

When a guest user submits a survey and then logs in:

```php
// In SurveyGate::claimGuestResponses(User, Request)
// Called from AuthController on every login flow

SurveyResponse::whereNull('user_id')
    ->where(function ($query) use ($request, $responseIds) {
        $query->where('session_id', $request->session()->getId());
        if ($responseIds !== []) {
            $query->orWhereIn('id', $responseIds);
        }
    })
    ->update(['user_id' => $user->id]);
```

Guest response IDs are tracked in `session('guest_survey_response_ids')`.

---

## Admin Survey Management

### CRUD

| Route | Action |
|-------|--------|
| `GET /admin/surveys` | List all surveys |
| `GET /admin/surveys/create` | Create form |
| `POST /admin/surveys` | Store new survey |
| `GET /admin/surveys/{survey}/edit` | Edit form |
| `PUT /admin/surveys/{survey}` | Update |
| `DELETE /admin/surveys/{survey}` | Delete |

### Viewing Responses

Available from the survey detail/edit page. Responses are paginated and show:
- User name (if logged in) or "Guest"
- Session ID (for guests)
- Completion status and timestamp
- All answers keyed by question ID

---

## Survey Controller (Public)

**File:** `app/Http/Controllers/SurveyController.php`

### `GET /surveys/{survey}` — Show

- Checks if survey is available (active, within date range)
- Gets or creates a `SurveyResponse` draft for this session/user
- Renders the survey form

### `POST /surveys/{survey}` — Store

- Validates answers (required fields, types)
- Updates `SurveyResponse`:
  - `answers` = submitted key-value map
  - `status = 'completed'`
  - `completed_at = now()`
- Sets `session('survey_completed.{id}') = true`
- Redirects to `SurveyGate::returnTo()` (saved session URL)
