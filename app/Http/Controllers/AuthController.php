<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\TicketOrder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function show(Request $request): View
    {
        if ($request->filled('redirect')) {
            $request->session()->put('url.intended', $this->safeRedirect((string) $request->query('redirect')));
        }

        if ($request->filled(['claim_order', 'phone'])) {
            $request->session()->put('claim_order', [
                'id' => (int) $request->query('claim_order'),
                'phone' => (string) $request->query('phone'),
            ]);
        }

        return view('auth.login', [
            'socialProviders' => collect(['line' => 'LINE', 'facebook' => 'Facebook', 'instagram' => 'Instagram'])
                ->filter(fn ($label, $provider) => config("services.{$provider}.client_id") && config("services.{$provider}.client_secret")),
        ]);
    }

    public function adminShow(): View
    {
        return view('auth.admin-login', [
            'localRoles' => app()->environment('local')
                ? [
                    'super_admin' => 'Super admin',
                    'event_admin' => 'Event admin',
                    'gate_scanner' => 'Gate scanner',
                ]
                : [],
        ]);
    }

    public function login(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['nullable', 'string', 'max:80'],
            'phone' => ['required', 'string', 'max:40'],
            'email' => ['nullable', 'email'],
            'provider' => ['nullable', 'in:line,facebook,instagram,guest'],
        ]);

        $user = User::where('phone', $data['phone'])->first();

        if ($user?->isAdmin()) {
            throw ValidationException::withMessages(['phone' => 'Admin accounts must use the admin login page.']);
        }

        $user ??= User::create(
            [
                'name' => $data['name'],
                'username' => $data['username'] ?? null,
                'phone' => $data['phone'],
                'email' => $data['email'] ?? null,
                'provider' => $data['provider'] ?? 'guest',
                'provider_id' => ($data['provider'] ?? 'guest').'-'.Str::slug($data['phone']),
            ]
        );

        $user->update([
            'name' => $data['name'],
            'username' => $user->isAdmin() ? $user->username : ($data['username'] ?? $user->username),
            'email' => $data['email'] ?? $user->email,
            'provider' => $data['provider'] ?? $user->provider,
        ]);

        Auth::login($user, true);
        $this->attachGuestOrdersToUser($user);

        return redirect()->intended(route('profile'))->with('status', 'Welcome back.');
    }

    public function adminLogin(Request $request): RedirectResponse
    {
        if (app()->environment('local') && $request->filled('role')) {
            $data = $request->validate([
                'role' => ['required', 'in:super_admin,event_admin,gate_scanner'],
            ]);

            $user = User::where('role', $data['role'])->firstOrFail();
            Auth::login($user, true);

            return redirect()->intended(route('admin.dashboard'))->with('status', 'Logged in as '.$user->name.'.');
        }

        $data = $request->validate([
            'username' => ['required', 'string', 'max:80'],
            'phone' => ['required', 'string', 'max:40'],
        ]);

        $user = User::where('phone', $data['phone'])->where('username', $data['username'])->first();

        if (! $user?->isAdmin()) {
            throw ValidationException::withMessages(['username' => 'Admin account not found for this username and phone.']);
        }

        Auth::login($user, true);

        return redirect()->intended(route('admin.dashboard'))->with('status', 'Welcome back, '.$user->name.'.');
    }

    public function social(Request $request, string $provider): RedirectResponse
    {
        abort_unless(in_array($provider, ['line', 'facebook', 'instagram'], true), 404);

        if (! config("services.{$provider}.client_id") || ! config("services.{$provider}.client_secret")) {
            return redirect()
                ->route('login')
                ->with('status', strtoupper($provider).' credentials are not configured yet. Add them to .env to enable real OAuth login.');
        }

        if ($request->filled('redirect')) {
            $request->session()->put('url.intended', $this->safeRedirect((string) $request->query('redirect')));
        }

        if ($request->filled(['claim_order', 'phone'])) {
            $request->session()->put('claim_order', [
                'id' => (int) $request->query('claim_order'),
                'phone' => (string) $request->query('phone'),
            ]);
        }

        return Socialite::driver($provider)->redirect();
    }

    public function socialCallback(Request $request, string $provider): RedirectResponse
    {
        abort_unless(in_array($provider, ['line', 'facebook', 'instagram'], true), 404);

        if (! config("services.{$provider}.client_id") || ! config("services.{$provider}.client_secret")) {
            return redirect()
                ->route('login')
                ->with('status', strtoupper($provider).' credentials are not configured yet.');
        }

        $socialUser = Socialite::driver($provider)->stateless()->user();
        $email = $socialUser->getEmail();

        $user = User::query()
            ->where(fn ($query) => $query
                ->where([['provider', $provider], ['provider_id', $socialUser->getId()]])
                ->when($email, fn ($query) => $query->orWhere('email', $email)))
            ->first();

        $user ??= User::create([
            'name' => $socialUser->getName() ?: $socialUser->getNickname() ?: ucfirst($provider).' User',
            'username' => $socialUser->getNickname(),
            'email' => $email,
            'provider' => $provider,
            'provider_id' => $socialUser->getId(),
            'avatar' => $socialUser->getAvatar(),
        ]);

        $user->update([
            'provider' => $provider,
            'provider_id' => $socialUser->getId(),
            'avatar' => $socialUser->getAvatar(),
            'email' => $email ?: $user->email,
        ]);

        Auth::login($user, true);
        $this->attachGuestOrdersToUser($user, $request);

        return redirect()->intended(route('profile'))->with('status', 'Logged in with '.strtoupper($provider).'.');
    }

    public function lineLiff(Request $request): JsonResponse
    {
        $data = $request->validate([
            'id_token' => ['required', 'string'],
            'profile' => ['nullable', 'array'],
            'profile.userId' => ['nullable', 'string'],
            'profile.displayName' => ['nullable', 'string', 'max:255'],
            'profile.pictureUrl' => ['nullable', 'url', 'max:2048'],
            'redirect' => ['nullable', 'string', 'max:2048'],
        ]);

        $clientId = config('services.line.client_id')
            ?: Str::before((string) config('services.line.liff_id'), '-');

        if (! $clientId) {
            throw ValidationException::withMessages([
                'line' => 'LINE LIFF needs LINE_CLIENT_ID or a valid LINE_LIFF_ID in .env.',
            ]);
        }

        $response = Http::asForm()->post('https://api.line.me/oauth2/v2.1/verify', [
            'id_token' => $data['id_token'],
            'client_id' => $clientId,
        ]);

        if ($response->failed()) {
            throw ValidationException::withMessages([
                'line' => 'LINE login could not be verified. Please try again.',
            ]);
        }

        $verified = $response->json();
        $profile = $data['profile'] ?? [];
        $lineUserId = $verified['sub'] ?? $profile['userId'] ?? null;

        if (! $lineUserId) {
            throw ValidationException::withMessages([
                'line' => 'LINE did not return a user id. Please try again.',
            ]);
        }

        $email = $verified['email'] ?? null;

        $user = User::query()
            ->where(fn ($query) => $query
                ->where([['provider', 'line'], ['provider_id', $lineUserId]])
                ->when($email, fn ($query) => $query->orWhere('email', $email)))
            ->first();

        $user ??= User::create([
            'name' => $verified['name'] ?? $profile['displayName'] ?? 'LINE User',
            'email' => $email,
            'provider' => 'line',
            'provider_id' => $lineUserId,
            'avatar' => $verified['picture'] ?? $profile['pictureUrl'] ?? null,
        ]);

        if ($user->isAdmin()) {
            throw ValidationException::withMessages([
                'line' => 'Admin accounts must use the admin login page.',
            ]);
        }

        $user->update([
            'name' => $verified['name'] ?? $profile['displayName'] ?? $user->name,
            'email' => $email ?: $user->email,
            'provider' => 'line',
            'provider_id' => $lineUserId,
            'avatar' => $verified['picture'] ?? $profile['pictureUrl'] ?? $user->avatar,
        ]);

        Auth::login($user, true);
        $request->session()->regenerate();
        $this->attachGuestOrdersToUser($user, $request);

        return response()->json([
            'redirect' => $this->safeRedirect($data['redirect'] ?? null),
        ]);
    }

    private function attachGuestOrdersToUser(User $user, ?Request $request = null): void
    {
        if ($user->isAdmin()) {
            return;
        }

        $claim = $request?->session()->pull('claim_order');

        if ($claim && ! empty($claim['id']) && ! empty($claim['phone'])) {
            $order = TicketOrder::query()
                ->whereKey($claim['id'])
                ->where('customer_phone', $claim['phone'])
                ->whereNull('user_id')
                ->first();

            if ($order) {
                $order->update(['user_id' => $user->id]);
                $order->tickets()->update(['user_id' => $user->id]);
            }
        }

        if ($user->phone) {
            $orders = TicketOrder::query()
                ->whereNull('user_id')
                ->where('customer_phone', $user->phone)
                ->get();

            foreach ($orders as $order) {
                $order->update(['user_id' => $user->id]);
                $order->tickets()->update(['user_id' => $user->id]);
            }
        }

        if ($user->email) {
            $orders = TicketOrder::query()
                ->whereNull('user_id')
                ->where('customer_email', $user->email)
                ->get();

            foreach ($orders as $order) {
                $order->update(['user_id' => $user->id]);
                $order->tickets()->update(['user_id' => $user->id]);
            }
        }
    }

    private function safeRedirect(?string $redirect): string
    {
        if (! $redirect) {
            return route('profile');
        }

        if (str_starts_with($redirect, '/') && ! str_starts_with($redirect, '//')) {
            return $redirect;
        }

        $appHost = parse_url(config('app.url'), PHP_URL_HOST);
        $redirectHost = parse_url($redirect, PHP_URL_HOST);

        if ($appHost && $redirectHost && hash_equals($appHost, $redirectHost)) {
            return $redirect;
        }

        return route('profile');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('events.index');
    }
}
