<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function show(): View
    {
        return view('auth.login');
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

    public function social(string $provider): RedirectResponse
    {
        abort_unless(in_array($provider, ['line', 'facebook', 'instagram'], true), 404);

        if (! config("services.{$provider}.client_id") || ! config("services.{$provider}.client_secret")) {
            return redirect()
                ->route('login')
                ->with('status', strtoupper($provider).' credentials are not configured yet. Add them to .env to enable real OAuth login.');
        }

        return Socialite::driver($provider)->redirect();
    }

    public function socialCallback(string $provider): RedirectResponse
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

        return redirect()->intended(route('profile'))->with('status', 'Logged in with '.strtoupper($provider).'.');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('events.index');
    }
}
