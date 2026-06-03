@props([
    'title' => config('app.name', 'Event Ticket'),
    'metaDescription' => null,
    'metaImage' => null,
    'canonicalUrl' => null,
    'robots' => null,
    'structuredData' => null,
])
@php
    $computedRobots = $robots
        ?? (request()->is('admin*', 'crm*', 'login', 'admin/login', 'orders*', 'tickets*', 'payments*', 'profile', 'auth*', 'line*', 'push-subscriptions*')
            ? 'noindex, nofollow'
            : 'index, follow');
    $siteName = config('app.name', 'TicketFlow');
@endphp
<!doctype html>
<html lang="en" data-ui-lang="both">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#34d399">
    <link rel="manifest" href="/site.webmanifest">
    @auth
        @if(config('webpush.vapid.public_key'))
            <meta name="webpush-public-key" content="{{ config('webpush.vapid.public_key') }}">
            <meta name="push-subscribe-url" content="{{ route('push-subscriptions.store') }}">
            <meta name="push-unsubscribe-url" content="{{ route('push-subscriptions.destroy') }}">
        @endif
    @endauth
    <title>{{ $title }}</title>
    <meta name="robots" content="{{ $computedRobots }}">
    <meta property="og:site_name" content="{{ $siteName }}">
    <meta property="og:locale" content="en_US">
    @if($metaDescription)
        <meta name="description" content="{{ $metaDescription }}">
        <meta property="og:description" content="{{ $metaDescription }}">
        <meta name="twitter:description" content="{{ $metaDescription }}">
    @endif
    <meta property="og:title" content="{{ $title }}">
    <meta name="twitter:title" content="{{ $title }}">
    <meta property="og:type" content="website">
    @if($canonicalUrl)
        <link rel="canonical" href="{{ $canonicalUrl }}">
        <meta property="og:url" content="{{ $canonicalUrl }}">
    @endif
    @if($metaImage)
        <meta property="og:image" content="{{ $metaImage }}">
        <meta name="twitter:card" content="summary_large_image">
        <meta name="twitter:image" content="{{ $metaImage }}">
    @endif
    @if($structuredData)
        <script type="application/ld+json">{!! json_encode($structuredData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
    @endif
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-white dark:bg-zinc-950 text-zinc-800 dark:text-zinc-100 antialiased">
    @guest
        @if(config('services.line.liff_id') && ! request()->is('admin*'))
            <div class="hidden" x-data="lineLiffLogin({
                auto: true,
                liffId: @js(config('services.line.liff_id')),
                loginUrl: @js(route('auth.line.liff')),
                profileUrl: @js(route('profile')),
                redirectUrl: @js(request()->fullUrl()),
            })" x-init="init()"></div>
        @endif
    @endguest

    <div class="border-b border-zinc-200 dark:border-white/10 bg-white dark:bg-zinc-950/85 backdrop-blur">
        <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-4 sm:px-6 lg:px-8">
            <a href="{{ route('events.index') }}" class="inline-flex shrink-0 items-center gap-2 text-lg font-semibold tracking-tight">
                <span class="grid h-9 w-9 place-items-center rounded-md bg-emerald-400 text-zinc-950"><x-icon name="ticket" class="h-5 w-5" /></span>
                <span>TicketFlow</span>
            </a>
            <nav class="flex min-w-0 items-center gap-1 overflow-x-auto whitespace-nowrap text-sm text-zinc-700 dark:text-zinc-300 sm:gap-2">
                <!-- Events link intentionally hidden while home redirects to the active event. -->
                <a class="interactive-action inline-flex items-center gap-2 rounded-md px-3 py-2 hover:bg-zinc-100 dark:hover:bg-white/10" href="{{ route('guides.buy-ticket') }}" aria-label="Buy ticket guide"><x-icon name="sparkles" /></a>
                <a class="interactive-action inline-flex items-center gap-2 rounded-md px-3 py-2 hover:bg-zinc-100 dark:hover:bg-white/10" href="{{ route('orders.lookup') }}" aria-label="Find order"><x-icon name="search" /></a>
                @auth
                    <a class="interactive-action inline-flex items-center gap-2 rounded-md px-3 py-2 hover:bg-zinc-100 dark:hover:bg-white/10" href="{{ route('profile') }}">
                        @if(auth()->user()->avatar)
                            <img class="h-8 w-8 rounded-full object-cover ring-2 ring-emerald-400/40" src="{{ auth()->user()->avatar }}" alt="{{ auth()->user()->name }}">
                        @else
                            <x-icon name="user" />
                        @endif
                        {{ auth()->user()->name }}
                    </a>
                    @if(auth()->user()->isAdmin())
                        <a class="interactive-action inline-flex items-center gap-2 rounded-md bg-emerald-400 px-3 py-2 font-semibold text-zinc-950" href="{{ route('admin.dashboard') }}"><x-icon name="shield" /><x-t en="Admin" th="ผู้ดูแล" /></a>
                    @endif
                @else
                    <a class="interactive-action inline-flex items-center gap-2 rounded-md bg-emerald-400 px-3 py-2 font-semibold text-zinc-950" href="{{ route('login') }}"><x-icon name="log-in" /><x-t en="Login" th="เข้าสู่ระบบ" /></a>
                    <!-- Admin login uses the visible Login flow and role-specific admin route. -->
                @endauth
            </nav>
        </div>
        @auth
            @if(auth()->user()->isAdmin() && request()->is('admin*'))
                <div class="border-t border-zinc-200 dark:border-white/10">
                    <div class="mx-auto flex max-w-7xl gap-2 overflow-x-auto px-4 py-3 text-sm sm:px-6 lg:px-8">
                        <a class="interactive-action inline-flex items-center gap-2 rounded-md px-3 py-2 {{ request()->routeIs('admin.dashboard') ? 'bg-emerald-400 text-zinc-950 font-semibold' : 'text-zinc-700 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-white/10' }}" href="{{ route('admin.dashboard') }}"><x-icon name="layout-dashboard" /><x-t en="Dashboard" th="แดชบอร์ด" /></a>
                        @if(in_array(auth()->user()->role, ['super_admin', 'event_admin'], true))
                            <a class="interactive-action inline-flex items-center gap-2 rounded-md px-3 py-2 {{ request()->routeIs('admin.events.*') ? 'bg-emerald-400 text-zinc-950 font-semibold' : 'text-zinc-700 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-white/10' }}" href="{{ route('admin.events.index') }}"><x-icon name="calendar-days" /><x-t en="Events" th="อีเวนต์" /></a>
                            <a class="interactive-action inline-flex items-center gap-2 rounded-md px-3 py-2 {{ request()->routeIs('admin.coupons.*', 'admin.promotions.*') ? 'bg-emerald-400 text-zinc-950 font-semibold' : 'text-zinc-700 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-white/10' }}" href="{{ route('admin.coupons.index') }}"><x-icon name="tag" /><x-t en="Coupons & promotions" th="คูปองและโปรโมชัน" /></a>
                            <a class="interactive-action inline-flex items-center gap-2 rounded-md px-3 py-2 {{ request()->routeIs('admin.orders.*') ? 'bg-emerald-400 text-zinc-950 font-semibold' : 'text-zinc-700 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-white/10' }}" href="{{ route('admin.orders.index') }}"><x-icon name="shopping-bag" /><x-t en="Orders" th="ออเดอร์" /></a>
                        @endif
                        <a class="interactive-action inline-flex items-center gap-2 rounded-md px-3 py-2 {{ request()->routeIs('admin.scanner') ? 'bg-emerald-400 text-zinc-950 font-semibold' : 'text-zinc-700 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-white/10' }}" href="{{ route('admin.scanner') }}"><x-icon name="scan-line" /><x-t en="Scanner" th="สแกนเนอร์" /></a>
                        @if(auth()->user()->role === 'super_admin')
                            <a class="interactive-action inline-flex items-center gap-2 rounded-md px-3 py-2 {{ request()->routeIs('admin.users.*') ? 'bg-emerald-400 text-zinc-950 font-semibold' : 'text-zinc-700 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-white/10' }}" href="{{ route('admin.users.index') }}"><x-icon name="users" /><x-t en="Users" th="ผู้ใช้" /></a>
                        @endif
                    </div>
                </div>
            @endif
        @endauth
    </div>

    <main class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        @if(session('status'))
            <div class="mb-6 rounded-md border border-emerald-400/30 bg-emerald-400/10 px-4 py-3 text-sm text-emerald-800 dark:text-emerald-100">{{ session('status') }}</div>
        @endif
        @if($errors->any())
            <div class="mb-6 rounded-md border border-rose-400/30 bg-rose-400/10 px-4 py-3 text-sm text-rose-800 dark:text-rose-100">{{ $errors->first() }}</div>
        @endif
        {{ $slot }}
    </main>
    <footer class="border-t border-zinc-200 bg-zinc-50 dark:border-white/10 dark:bg-zinc-950">
        <div class="mx-auto grid max-w-7xl gap-4 px-4 py-6 text-sm text-zinc-600 dark:text-zinc-400 sm:px-6 lg:px-8">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <a class="interactive-action inline-flex items-center gap-2 font-semibold text-zinc-950 hover:text-emerald-700 dark:text-white dark:hover:text-emerald-200" href="{{ route('about') }}">
                    <span class="grid h-8 w-8 place-items-center rounded-md bg-emerald-400 text-zinc-950"><x-icon name="ticket" class="h-4 w-4" /></span>
                    TicketFlow
                </a>
                <nav class="flex flex-wrap items-center gap-x-4 gap-y-2">
                    <!-- About link is available through the TicketFlow logo. -->
                    <a class="interactive-action rounded-md px-2 py-1 hover:text-emerald-700 dark:hover:text-emerald-200" href="{{ route('legal.terms') }}"><x-t en="Terms and Conditions" th="ข้อกำหนดและเงื่อนไข" /></a>
                    <!-- Developer link is available on the About page. -->
                </nav>
                <label class="inline-flex w-fit items-center gap-2 rounded-md border border-zinc-200 bg-white px-3 py-2 text-sm font-semibold text-zinc-700 shadow-sm dark:border-white/10 dark:bg-white/[0.04] dark:text-zinc-200" data-i18n-skip>
                    <!-- <span class="text-xs uppercase tracking-wide text-zinc-500 dark:text-zinc-500">Language</span> -->
                    <select class="bg-transparent text-sm font-semibold focus:outline-none" data-language-switcher aria-label="UI language">
                        <option value="en">🇬🇧 English</option>
                        <option value="th">🇹🇭 ไทย</option>
                        <option value="both">🇬🇧 🇹🇭 EN/TH</option>
                    </select>
                </label>
            </div>
        </div>
    </footer>
</body>
</html>
