# Implementation Plan: Mailtrap, Brevo & Email/Password Auth

> **Status:** Ready for implementation  
> **Target:** TicketFlow (Laravel 13, PHP 8.3)

---

## Table of Contents

- [Phase 1: Mail Integration (Mailtrap + Brevo)](#phase-1-mail-integration-mailtrap--brevo)
- [Phase 2: Email/Password Authentication](#phase-2-emailpassword-authentication)
- [Phase 3: Queue Email Sending](#phase-3-queue-email-sending)
- [Implementation Order](#implementation-order)

---

## Phase 1: Mail Integration (Mailtrap + Brevo)

### Goal

Replace the current `log` mail driver with real email delivery using **Mailtrap** (local/dev) and **Brevo** (production). Both services support SMTP — no extra packages required.

### 1.1 Add Environment Variables

**File:** `.env.example` (lines 50–57)

Replace the existing `MAIL_*` block with two sets of credentials:

```env
# ── Default Mailer ──────────────────────────
# Choices: mailtrap (dev), brevo (prod), log (no delivery)
MAIL_MAILER=mailtrap
MAIL_FROM_ADDRESS="hello@example.com"
MAIL_FROM_NAME="${APP_NAME}"

# ── Mailtrap (Local / Dev) ──────────────────
# https://mailtrap.io → Email Testing → SMTP Settings
MAILTRAP_HOST=sandbox.smtp.mailtrap.io
MAILTRAP_PORT=587
MAILTRAP_USERNAME=null
MAILTRAP_PASSWORD=null
MAILTRAP_ENCRYPTION=tls

# ── Brevo SMTP (Production) ─────────────────
# https://app.brevo.com → SMTP & API → SMTP Settings
BREVO_HOST=smtp-relay.brevo.com
BREVO_PORT=587
BREVO_USERNAME=null
BREVO_PASSWORD=null
BREVO_ENCRYPTION=tls
```

> **Note:** Keep the old `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD` lines if they exist — they won't conflict because `config/mail.php` will no longer reference them for SMTP. They can be removed once confirmed unused.

### 1.2 Configure Mailers

**File:** `config/mail.php` (lines 38–100)

Add two new named mailers inside the `'mailers'` array:

```php
'mailtrap' => [
    'transport' => 'smtp',
    'host' => env('MAILTRAP_HOST', 'sandbox.smtp.mailtrap.io'),
    'port' => env('MAILTRAP_PORT', 587),
    'username' => env('MAILTRAP_USERNAME'),
    'password' => env('MAILTRAP_PASSWORD'),
    'encryption' => env('MAILTRAP_ENCRYPTION', 'tls'),
    'timeout' => null,
    'local_domain' => env('MAIL_EHLO_DOMAIN', parse_url((string) env('APP_URL', 'http://localhost'), PHP_URL_HOST)),
],

'brevo' => [
    'transport' => 'smtp',
    'host' => env('BREVO_HOST', 'smtp-relay.brevo.com'),
    'port' => env('BREVO_PORT', 587),
    'username' => env('BREVO_USERNAME'),
    'password' => env('BREVO_PASSWORD'),
    'encryption' => env('BREVO_ENCRYPTION', 'tls'),
    'timeout' => null,
    'local_domain' => env('MAIL_EHLO_DOMAIN', parse_url((string) env('APP_URL', 'http://localhost'), PHP_URL_HOST)),
],
```

Also update the default mailer to default to `'log'` (safe fallback):

```php
'default' => env('MAIL_MAILER', 'log'),
```

Line 17 stays the same — just keep the `log` fallback in case neither Mailtrap nor Brevo is configured.

### 1.3 Usage in Code

When sending mail, specify the mailer explicitly or rely on the default:

```php
// Uses default mailer (MAIL_MAILER env var)
Mail::to($email)->send(new EventAttendeeAnnouncement(...));

// Or be explicit:
Mail::mailer('brevo')->to($email)->send(...);
Mail::mailer('mailtrap')->to($email)->send(...);
```

The `EventAttendeeAnnouncement` mailable (`app/Mail/EventAttendeeAnnouncement.php`) already implements `Queueable` — no changes needed to the mailable class itself.

---

## Phase 2: Email/Password Authentication

### Goal

Add email + password as an optional login/registration method alongside the existing phone-based login. The `password` column already exists on the `users` table but is unused. No schema migration needed.

### 2.1 Add Registration Route

**File:** `routes/web.php` (after line 46, near existing auth routes)

Add:

```php
Route::get('/register', [AuthController::class, 'registerShow'])->name('register');
Route::post('/register', [AuthController::class, 'register'])->name('register.store');
```

### 2.2 Add Password Reset Routes

**File:** `routes/web.php`

Add below the registration routes:

```php
Route::get('/forgot-password', [AuthController::class, 'forgotPasswordShow'])->name('password.request');
Route::post('/forgot-password', [AuthController::class, 'forgotPassword'])->name('password.email');
Route::get('/reset-password/{token}', [AuthController::class, 'resetPasswordShow'])->name('password.reset');
Route::post('/reset-password', [AuthController::class, 'resetPassword'])->name('password.update');
```

These routes are deliberately **not** using Laravel's built-in `Auth::routes()` because the rest of the auth system is custom. Each handler will be implemented manually in `AuthController`.

### 2.3 Modify `AuthController`

**File:** `app/Http/Controllers/AuthController.php`

#### 2.3.1 Add Imports

Add at the top of the class:

```php
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Auth\Events\PasswordReset;
```

#### 2.3.2 Add `registerShow()` method

```php
public function registerShow(): View
{
    return view('auth.register');
}
```

#### 2.3.3 Add `register()` method

```php
public function register(Request $request, CrmSyncService $crm, SurveyGate $surveys): RedirectResponse
{
    $data = $request->validate([
        'name' => ['required', 'string', 'max:255'],
        'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
        'password' => ['required', 'string', 'min:8', 'confirmed'],
    ]);

    $user = User::create([
        'name' => $data['name'],
        'email' => $data['email'],
        'password' => Hash::make($data['password']),
        'provider' => null,   // explicitly null = email/password user
        'provider_id' => null,
    ]);

    $surveys->claimGuestResponses($user, $request);
    Auth::login($user, true);
    $this->attachGuestOrdersToUser($user);
    $crm->pushCustomer($user->fresh(), 'email_registration');

    if ($survey = $surveys->due('on_login', $request)) {
        $surveys->rememberReturn($survey, $request, redirect()->intended(route('profile'))->getTargetUrl());
        return redirect()->route('surveys.show', $survey);
    }

    return redirect()->intended(route('profile'))->with('status', 'Account created. Welcome!');
}
```

#### 2.3.4 Modify `show()` — Pass email login flag to view

Change the return at line 34–37 to include a new flag:

```php
return view('auth.login', [
    'socialProviders' => collect(['line' => 'LINE', 'facebook' => 'Facebook', 'instagram' => 'Instagram'])
        ->filter(fn ($label, $provider) => config("services.{$provider}.client_id") && config("services.{$provider}.client_secret")),
    'emailLoginEnabled' => true,
]);
```

#### 2.3.5 Modify `login()` — Support email+password

Rename the existing `login()` to better reflect its purpose, or add a branching condition. Cleanest approach: keep `login()` as the phone-based path, and add a new `emailLogin()` method that handles the email+password case.

**Add `emailLogin()` method:**

```php
public function emailLogin(Request $request, CrmSyncService $crm, SurveyGate $surveys): RedirectResponse
{
    $data = $request->validate([
        'email' => ['required', 'string', 'email'],
        'password' => ['required', 'string'],
    ]);

    $user = User::where('email', $data['email'])->first();

    if (! $user || ! Hash::check($data['password'], $user->password)) {
        throw ValidationException::withMessages([
            'email' => __('auth.failed'),
        ]);
    }

    if ($user->isAdmin()) {
        throw ValidationException::withMessages([
            'email' => 'Admin accounts must use the admin login page.',
        ]);
    }

    $surveys->claimGuestResponses($user, $request);
    Auth::login($user, true);
    $this->attachGuestOrdersToUser($user);
    $crm->pushCustomer($user->fresh(), 'email_login');

    if ($survey = $surveys->due('on_login', $request)) {
        $surveys->rememberReturn($survey, $request, redirect()->intended(route('profile'))->getTargetUrl());
        return redirect()->route('surveys.show', $survey);
    }

    return redirect()->intended(route('profile'))->with('status', 'Welcome back.');
}
```

#### 2.3.6 Add Password Reset Methods

**`forgotPasswordShow()`:**

```php
public function forgotPasswordShow(): View
{
    return view('auth.forgot-password');
}
```

**`forgotPassword()`:**

```php
public function forgotPassword(Request $request): RedirectResponse
{
    $data = $request->validate([
        'email' => ['required', 'string', 'email'],
    ]);

    $status = Password::sendResetLink(
        $data->only('email')
    );

    return $status === Password::RESET_LINK_SENT
        ? back()->with('status', __($status))
        : back()->withErrors(['email' => __($status)]);
}
```

**`resetPasswordShow()`:**

```php
public function resetPasswordShow(Request $request, string $token): View
{
    return view('auth.reset-password', [
        'token' => $token,
        'email' => $request->query('email'),
    ]);
}
```

**`resetPassword()`:**

```php
public function resetPassword(Request $request): RedirectResponse
{
    $data = $request->validate([
        'token' => ['required'],
        'email' => ['required', 'email'],
        'password' => ['required', 'confirmed', 'min:8'],
    ]);

    $status = Password::reset(
        $data,
        function (User $user, string $password) {
            $user->forceFill([
                'password' => Hash::make($password),
            ])->save();

            event(new PasswordReset($user));
        }
    );

    return $status === Password::PASSWORD_RESET
        ? redirect()->route('login')->with('status', __($status))
        : back()->withErrors(['email' => [__($status)]]);
}
```

### 2.4 Add New Routes for emailLogin

**File:** `routes/web.php`

Add the email login route (it uses a different path to avoid conflicting with the phone login):

```php
Route::post('/login/email', [AuthController::class, 'emailLogin'])->name('login.email');
```

### 2.5 Add Blade Views

#### 2.5.1 `resources/views/auth/register.blade.php` (New)

```blade
<x-layouts.app title="Register">
    <div class="mx-auto max-w-xl rounded-lg border border-zinc-200 dark:border-white/10 bg-white dark:bg-white/[0.04] p-6">
        <h1 class="inline-flex items-center gap-2 text-2xl font-semibold text-zinc-950 dark:text-white">
            <x-icon name="user-plus" class="h-6 w-6 text-emerald-500" />
            <x-t en="Create Account" th="สมัครสมาชิก" />
        </h1>
        <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">
            <x-t en="Register with your email and password." th="ลงทะเบียนด้วยอีเมลและรหัสผ่าน" />
        </p>
        <form method="POST" action="{{ route('register.store') }}" class="mt-6 grid gap-4">
            @csrf
            <label class="text-sm text-zinc-700 dark:text-zinc-300">
                <x-t en="Name" th="ชื่อ" />
                <input class="mt-1 w-full rounded-md border border-zinc-200 dark:border-white/10 bg-white dark:bg-zinc-950 px-3 py-2 text-zinc-950 dark:text-white" name="name" value="{{ old('name') }}" required autofocus>
            </label>
            <label class="text-sm text-zinc-700 dark:text-zinc-300">
                <x-t en="Email" th="อีเมล" />
                <input class="mt-1 w-full rounded-md border border-zinc-200 dark:border-white/10 bg-white dark:bg-zinc-950 px-3 py-2 text-zinc-950 dark:text-white" type="email" name="email" value="{{ old('email') }}" required>
            </label>
            <label class="text-sm text-zinc-700 dark:text-zinc-300">
                <x-t en="Password" th="รหัสผ่าน" />
                <input class="mt-1 w-full rounded-md border border-zinc-200 dark:border-white/10 bg-white dark:bg-zinc-950 px-3 py-2 text-zinc-950 dark:text-white" type="password" name="password" required minlength="8">
            </label>
            <label class="text-sm text-zinc-700 dark:text-zinc-300">
                <x-t en="Confirm Password" th="ยืนยันรหัสผ่าน" />
                <input class="mt-1 w-full rounded-md border border-zinc-200 dark:border-white/10 bg-white dark:bg-zinc-950 px-3 py-2 text-zinc-950 dark:text-white" type="password" name="password_confirmation" required minlength="8">
            </label>
            @if($errors->any())
                <div class="rounded-md bg-red-50 dark:bg-red-900/20 p-3 text-sm text-red-600 dark:text-red-400">
                    @foreach($errors->all() as $error)
                        <p>{{ $error }}</p>
                    @endforeach
                </div>
            @endif
            <button class="inline-flex items-center justify-center gap-2 rounded-md bg-emerald-400 px-4 py-3 font-semibold text-zinc-950">
                <x-icon name="check" />
                <x-t en="Register" th="สมัครสมาชิก" />
            </button>
            <p class="text-center text-sm text-zinc-500">
                <x-t en="Already have an account?" th="มีบัญชีอยู่แล้ว?" />
                <a href="{{ route('login') }}" class="text-emerald-600 hover:underline"><x-t en="Log in" th="เข้าสู่ระบบ" /></a>
            </p>
        </form>
    </div>
</x-layouts.app>
```

#### 2.5.2 Modify `resources/views/auth/login.blade.php`

Split the login page into two tabs: **Phone Login** (existing) and **Email Login** (new). Add a JavaScript toggle at the top:

```blade
<x-layouts.app title="Login">
    <div class="mx-auto max-w-xl rounded-lg border border-zinc-200 dark:border-white/10 bg-white dark:bg-white/[0.04] p-6">
        {{-- Tab switcher --}}
        <div class="mb-6 flex rounded-md bg-zinc-100 dark:bg-zinc-800 p-1 text-sm">
            <button id="tab-phone" class="flex-1 rounded-md px-3 py-2 font-medium transition-colors" onclick="switchTab('phone')">
                <x-t en="Phone" th="เบอร์โทร" />
            </button>
            <button id="tab-email" class="flex-1 rounded-md px-3 py-2 font-medium text-zinc-500 transition-colors" onclick="switchTab('email')">
                <x-t en="Email" th="อีเมล" />
            </button>
        </div>

        {{-- Phone login form (existing, unchanged) --}}
        <div id="form-phone">
            <h1 class="inline-flex items-center gap-2 text-2xl font-semibold text-zinc-950 dark:text-white">
                <x-icon name="log-in" class="h-6 w-6 text-emerald-500" />
                <x-t en="Client login" th="เข้าสู่ระบบลูกค้า" />
            </h1>
            <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">
                <x-t en="Use social login or phone quick login for customers." th="เข้าสู่ระบบด้วยโซเชียลหรือชื่อและเบอร์โทร" />
            </p>
            @if($socialProviders->isNotEmpty())
                <div class="mt-5 grid gap-3 sm:grid-cols-3" style="grid-template-columns: repeat({{ $socialProviders->count() }}, minmax(0, 1fr));">
                    @foreach($socialProviders as $provider => $label)
                        <a class="inline-flex items-center justify-center gap-2 rounded-md px-3 py-2 text-center text-sm font-semibold {{ $provider === 'line' ? 'bg-[#06c755] text-zinc-950' : ($provider === 'facebook' ? 'bg-[#1877f2] text-white' : 'bg-pink-500 text-white') }}" href="{{ route('auth.social', $provider) }}"><x-icon name="log-in" />{{ $label }}</a>
                    @endforeach
                </div>
            @endif
            <form method="POST" action="{{ route('login.store') }}" class="mt-6 grid gap-4">
                @csrf
                <input type="hidden" name="provider" value="guest">
                <label class="text-sm text-zinc-700 dark:text-zinc-300">
                    <x-t en="Name" th="ชื่อ" />
                    <input class="mt-1 w-full rounded-md border border-zinc-200 dark:border-white/10 bg-white dark:bg-zinc-950 px-3 py-2 text-zinc-950 dark:text-white" name="name" required>
                </label>
                <label class="text-sm text-zinc-700 dark:text-zinc-300">
                    <x-t en="Phone" th="เบอร์โทร" />
                    <input class="mt-1 w-full rounded-md border border-zinc-200 dark:border-white/10 bg-white dark:bg-zinc-950 px-3 py-2 text-zinc-950 dark:text-white" name="phone" required>
                </label>
                <label class="text-sm text-zinc-700 dark:text-zinc-300">
                    <x-t en="Email" th="อีเมล" />
                    <span class="text-zinc-500"><x-t en="(optional)" th="(ไม่บังคับ)" /></span>
                    <input class="mt-1 w-full rounded-md border border-zinc-200 dark:border-white/10 bg-white dark:bg-zinc-950 px-3 py-2 text-zinc-950 dark:text-white" name="email">
                </label>
                <button class="inline-flex items-center justify-center gap-2 rounded-md bg-emerald-400 px-4 py-3 font-semibold text-zinc-950">
                    <x-icon name="check" />
                    <x-t en="Continue" th="ดำเนินการต่อ" />
                </button>
            </form>
            @if($emailLoginEnabled ?? false)
                <p class="mt-4 text-center text-sm text-zinc-500">
                    <x-t en="No account?" th="ยังไม่มีบัญชี?" />
                    <a href="{{ route('register') }}" class="text-emerald-600 hover:underline"><x-t en="Register" th="สมัครสมาชิก" /></a>
                </p>
            @endif
        </div>

        {{-- Email login form (new) --}}
        <div id="form-email" class="hidden">
            <h1 class="inline-flex items-center gap-2 text-2xl font-semibold text-zinc-950 dark:text-white">
                <x-icon name="log-in" class="h-6 w-6 text-emerald-500" />
                <x-t en="Email Login" th="เข้าสู่ระบบด้วยอีเมล" />
            </h1>
            <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">
                <x-t en="Log in with your email and password." th="เข้าสู่ระบบด้วยอีเมลและรหัสผ่าน" />
            </p>
            <form method="POST" action="{{ route('login.email') }}" class="mt-6 grid gap-4">
                @csrf
                <label class="text-sm text-zinc-700 dark:text-zinc-300">
                    <x-t en="Email" th="อีเมล" />
                    <input class="mt-1 w-full rounded-md border border-zinc-200 dark:border-white/10 bg-white dark:bg-zinc-950 px-3 py-2 text-zinc-950 dark:text-white" type="email" name="email" required autofocus>
                </label>
                <label class="text-sm text-zinc-700 dark:text-zinc-300">
                    <x-t en="Password" th="รหัสผ่าน" />
                    <input class="mt-1 w-full rounded-md border border-zinc-200 dark:border-white/10 bg-white dark:bg-zinc-950 px-3 py-2 text-zinc-950 dark:text-white" type="password" name="password" required>
                </label>
                @if($errors->any())
                    <div class="rounded-md bg-red-50 dark:bg-red-900/20 p-3 text-sm text-red-600 dark:text-red-400">
                        @foreach($errors->all() as $error)
                            <p>{{ $error }}</p>
                        @endforeach
                    </div>
                @endif
                <button class="inline-flex items-center justify-center gap-2 rounded-md bg-emerald-400 px-4 py-3 font-semibold text-zinc-950">
                    <x-icon name="check" />
                    <x-t en="Log in" th="เข้าสู่ระบบ" />
                </button>
                <div class="flex items-center justify-between text-sm">
                    <a href="{{ route('password.request') }}" class="text-emerald-600 hover:underline">
                        <x-t en="Forgot password?" th="ลืมรหัสผ่าน?" />
                    </a>
                    <a href="{{ route('register') }}" class="text-emerald-600 hover:underline">
                        <x-t en="Register" th="สมัครสมาชิก" />
                    </a>
                </div>
            </form>
        </div>
    </div>

    <script>
        function switchTab(tab) {
            document.getElementById('form-phone').classList.toggle('hidden', tab !== 'phone');
            document.getElementById('form-email').classList.toggle('hidden', tab !== 'email');
            document.getElementById('tab-phone').classList.toggle('text-zinc-500', tab !== 'phone');
            document.getElementById('tab-phone').classList.toggle('bg-white', tab === 'phone');
            document.getElementById('tab-phone').classList.toggle('dark:bg-zinc-950', tab === 'phone');
            document.getElementById('tab-email').classList.toggle('text-zinc-500', tab !== 'email');
            document.getElementById('tab-email').classList.toggle('bg-white', tab === 'email');
            document.getElementById('tab-email').classList.toggle('dark:bg-zinc-950', tab === 'email');
        }
        // Check for email query param to pre-select email tab
        if (new URLSearchParams(window.location.search).has('email')) {
            switchTab('email');
        }
    </script>
</x-layouts.app>
```

#### 2.5.3 `resources/views/auth/forgot-password.blade.php` (New)

```blade
<x-layouts.app title="Forgot Password">
    <div class="mx-auto max-w-xl rounded-lg border border-zinc-200 dark:border-white/10 bg-white dark:bg-white/[0.04] p-6">
        <h1 class="inline-flex items-center gap-2 text-2xl font-semibold text-zinc-950 dark:text-white">
            <x-icon name="help-circle" class="h-6 w-6 text-emerald-500" />
            <x-t en="Forgot Password" th="ลืมรหัสผ่าน" />
        </h1>
        <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">
            <x-t en="Enter your email and we'll send you a reset link." th="กรอกอีเมลของคุณเราจะส่งลิงก์รีเซ็ตรหัสผ่านให้" />
        </p>
        <form method="POST" action="{{ route('password.email') }}" class="mt-6 grid gap-4">
            @csrf
            <label class="text-sm text-zinc-700 dark:text-zinc-300">
                <x-t en="Email" th="อีเมล" />
                <input class="mt-1 w-full rounded-md border border-zinc-200 dark:border-white/10 bg-white dark:bg-zinc-950 px-3 py-2 text-zinc-950 dark:text-white" type="email" name="email" value="{{ old('email') }}" required autofocus>
            </label>
            @if($errors->any())
                <div class="rounded-md bg-red-50 dark:bg-red-900/20 p-3 text-sm text-red-600 dark:text-red-400">
                    @foreach($errors->all() as $error)
                        <p>{{ $error }}</p>
                    @endforeach
                </div>
            @endif
            @if(session('status'))
                <div class="rounded-md bg-emerald-50 dark:bg-emerald-900/20 p-3 text-sm text-emerald-600 dark:text-emerald-400">
                    {{ session('status') }}
                </div>
            @endif
            <button class="inline-flex items-center justify-center gap-2 rounded-md bg-emerald-400 px-4 py-3 font-semibold text-zinc-950">
                <x-icon name="send" />
                <x-t en="Send Reset Link" th="ส่งลิงก์รีเซ็ต" />
            </button>
            <p class="text-center text-sm text-zinc-500">
                <a href="{{ route('login') }}" class="text-emerald-600 hover:underline">
                    <x-t en="Back to login" th="กลับไปหน้าเข้าสู่ระบบ" />
                </a>
            </p>
        </form>
    </div>
</x-layouts.app>
```

#### 2.5.4 `resources/views/auth/reset-password.blade.php` (New)

```blade
<x-layouts.app title="Reset Password">
    <div class="mx-auto max-w-xl rounded-lg border border-zinc-200 dark:border-white/10 bg-white dark:bg-white/[0.04] p-6">
        <h1 class="inline-flex items-center gap-2 text-2xl font-semibold text-zinc-950 dark:text-white">
            <x-icon name="lock" class="h-6 w-6 text-emerald-500" />
            <x-t en="Reset Password" th="รีเซ็ตรหัสผ่าน" />
        </h1>
        <form method="POST" action="{{ route('password.update') }}" class="mt-6 grid gap-4">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">
            <label class="text-sm text-zinc-700 dark:text-zinc-300">
                <x-t en="Email" th="อีเมล" />
                <input class="mt-1 w-full rounded-md border border-zinc-200 dark:border-white/10 bg-white dark:bg-zinc-950 px-3 py-2 text-zinc-950 dark:text-white" type="email" name="email" value="{{ old('email', $email ?? '') }}" required readonly>
            </label>
            <label class="text-sm text-zinc-700 dark:text-zinc-300">
                <x-t en="New Password" th="รหัสผ่านใหม่" />
                <input class="mt-1 w-full rounded-md border border-zinc-200 dark:border-white/10 bg-white dark:bg-zinc-950 px-3 py-2 text-zinc-950 dark:text-white" type="password" name="password" required minlength="8" autofocus>
            </label>
            <label class="text-sm text-zinc-700 dark:text-zinc-300">
                <x-t en="Confirm New Password" th="ยืนยันรหัสผ่านใหม่" />
                <input class="mt-1 w-full rounded-md border border-zinc-200 dark:border-white/10 bg-white dark:bg-zinc-950 px-3 py-2 text-zinc-950 dark:text-white" type="password" name="password_confirmation" required minlength="8">
            </label>
            @if($errors->any())
                <div class="rounded-md bg-red-50 dark:bg-red-900/20 p-3 text-sm text-red-600 dark:text-red-400">
                    @foreach($errors->all() as $error)
                        <p>{{ $error }}</p>
                    @endforeach
                </div>
            @endif
            <button class="inline-flex items-center justify-center gap-2 rounded-md bg-emerald-400 px-4 py-3 font-semibold text-zinc-950">
                <x-icon name="check" />
                <x-t en="Reset Password" th="รีเซ็ตรหัสผ่าน" />
            </button>
        </form>
    </div>
</x-layouts.app>
```

### 2.6 Update Password Reset Broker Configuration

**File:** `config/auth.php`

Verify the `passwords` section already points to the `users` table and uses the `users` provider. Laravel 13 default should be:

```php
'passwords' => [
    'users' => [
        'provider' => 'users',
        'table' => env('AUTH_PASSWORD_RESET_TOKEN_TABLE', 'password_reset_tokens'),
        'expire' => 60,
        'throttle' => 60,
    ],
],
```

The `password_reset_tokens` table already exists (default Laravel migration). No migration needed.

### 2.7 Configure Laravel Password Reset Notification

Laravel sends a `Illuminate\Auth\Notifications\ResetPassword` notification to users when requesting a password reset link. This notification uses the `app.name` config and the `password.reset` named route.

Ensure `config/app.php` has:

```php
'name' => env('APP_NAME', 'TicketFlow'),
```

The notification will generate reset URLs like: `http://localhost/reset-password/{token}?email=user@example.com` — matching our route exactly.

No custom notification class needed unless we want to customize the email template.

---

## Phase 3: Queue Email Sending

### Goal

Prevent the `emailAttendees()` method from blocking the HTTP request. The `EventAttendeeAnnouncement` mailable already has the `Queueable` trait — we just need to dispatch it as a queued job instead of sending it synchronously.

### 3.1 Modify `emailAttendees()` in `AdminEventController`

**File:** `app/Http/Controllers/Admin/EventController.php` (lines 140–142)

Change:

```php
foreach ($emails as $email) {
    Mail::to($email)->send(new EventAttendeeAnnouncement($event, $data['subject'], $data['message']));
}
```

To:

```php
foreach ($emails as $email) {
    Mail::to($email)->queue(new EventAttendeeAnnouncement($event, $data['subject'], $data['message']));
}
```

That's it — the `->queue()` method pushes each email onto the default queue connection (database, as configured in `.env`). The queue worker (`php artisan queue:listen`) processes them asynchronously.

### 3.2 Ensure Queue Worker is Running

The `.env.example` already defaults to `QUEUE_CONNECTION=database`. The development setup docs (`docs/setup.md`) already include `php artisan queue:listen` in the `composer dev` command — so no configuration change needed.

---

## Implementation Order

| Step | File(s) | Description | Effort |
|------|---------|-------------|--------|
| 1 | `.env.example`, `config/mail.php` | Add Mailtrap + Brevo mailer configs | ~10 min |
| 2 | `routes/web.php` | Add registration + password reset routes | ~5 min |
| 3 | `app/Http/Controllers/AuthController.php` | Add `registerShow()`, `register()`, `emailLogin()`, forgot/reset password methods | ~30 min |
| 4 | `resources/views/auth/register.blade.php` (new) | Registration form view | ~15 min |
| 5 | `resources/views/auth/login.blade.php` | Add tab toggle + email login form | ~20 min |
| 6 | `resources/views/auth/forgot-password.blade.php` (new) | Forgot password form view | ~10 min |
| 7 | `resources/views/auth/reset-password.blade.php` (new) | Reset password form view | ~10 min |
| 8 | `app/Http/Controllers/Admin/EventController.php` | Switch `send()` to `queue()` for attendee emails | ~2 min |
| 9 | `.env` | Add real Mailtrap/Brevo credentials (user to provide) | ~2 min |
| **Total** | | | **~1.5 hours** |

---

## Testing Checklist

After implementation, verify:

### Mail
- [ ] `php artisan config:clear` and `php artisan config:cache` work
- [ ] In local env, set `MAIL_MAILER=log` — emails appear in `storage/logs/laravel.log`
- [ ] Set `MAIL_MAILER=mailtrap` with test credentials — email appears in Mailtrap inbox
- [ ] `POST /admin/events/{event}/email-attendees` with valid data sends mail to correct recipients

### Auth — Registration
- [ ] `GET /register` shows registration form
- [ ] `POST /register` with valid data creates user, logs in, redirects to profile
- [ ] `POST /register` with existing email returns validation error
- [ ] `POST /register` with mismatched passwords returns validation error
- [ ] Password is hashed in the database (not plain text)
- [ ] New email/password user has `provider = null` and `provider_id = null`

### Auth — Email Login
- [ ] Login page shows both "Phone" and "Email" tabs
- [ ] Email tab form accepts email + password
- [ ] Correct credentials log the user in and redirect to profile
- [ ] Wrong password returns `auth.failed` error message
- [ ] Admin account cannot log in via email login (shows admin-specific error)

### Auth — Password Reset
- [ ] "Forgot password?" link visible on email login tab
- [ ] `GET /forgot-password` shows email input form
- [ ] `POST /forgot-password` with valid email sends reset link email
- [ ] Email contains correct reset URL with token and email param
- [ ] `GET /reset-password/{token}?email=...` shows reset form
- [ ] `POST /reset-password` with valid token + new password updates password
- [ ] New password works on next login attempt

### Auth — Phone Login (Regression)
- [ ] Existing phone login still works unchanged
- [ ] Guest order claiming still works on login
- [ ] `on_login` survey gate still fires
- [ ] CRM sync still fires
