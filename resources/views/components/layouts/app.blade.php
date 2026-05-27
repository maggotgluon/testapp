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
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
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
                <!-- <a class="rounded-md px-3 py-2 hover:bg-zinc-100 dark:hover:bg-white/10" href="{{ route('events.index') }}">Events / อีเวนต์</a> -->
                <a class="inline-flex items-center gap-2 rounded-md px-3 py-2 hover:bg-zinc-100 dark:hover:bg-white/10" href="{{ route('guides.buy-ticket') }}"><x-icon name="sparkles" /></a>
                <a class="inline-flex items-center gap-2 rounded-md px-3 py-2 hover:bg-zinc-100 dark:hover:bg-white/10" href="{{ route('orders.lookup') }}"><x-icon name="search" /></a>
                @auth
                    <a class="inline-flex items-center gap-2 rounded-md px-3 py-2 hover:bg-zinc-100 dark:hover:bg-white/10" href="{{ route('profile') }}">
                        @if(auth()->user()->avatar)
                            <img class="h-8 w-8 rounded-full object-cover ring-2 ring-emerald-400/40" src="{{ auth()->user()->avatar }}" alt="{{ auth()->user()->name }}">
                        @else
                            <x-icon name="user" />
                        @endif
                        {{ auth()->user()->name }}
                    </a>
                    @if(auth()->user()->isAdmin())
                        <a class="inline-flex items-center gap-2 rounded-md bg-emerald-400 px-3 py-2 font-semibold text-zinc-950" href="{{ route('admin.dashboard') }}"><x-icon name="shield" />Admin</a>
                    @endif
                @else
                    <a class="inline-flex items-center gap-2 rounded-md bg-emerald-400 px-3 py-2 font-semibold text-zinc-950" href="{{ route('login') }}"><x-icon name="log-in" />Login / เข้าสู่ระบบ</a>
                    <!-- <a class="rounded-md px-3 py-2 hover:bg-zinc-100 dark:hover:bg-white/10" href="{{ route('admin.login') }}">Admin / ผู้ดูแล</a> -->
                @endauth
            </nav>
        </div>
        @auth
            @if(auth()->user()->isAdmin() && request()->is('admin*'))
                <div class="border-t border-zinc-200 dark:border-white/10">
                    <div class="mx-auto flex max-w-7xl gap-2 overflow-x-auto px-4 py-3 text-sm sm:px-6 lg:px-8">
                        <a class="inline-flex items-center gap-2 rounded-md px-3 py-2 {{ request()->routeIs('admin.dashboard') ? 'bg-emerald-400 text-zinc-950 font-semibold' : 'text-zinc-700 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-white/10' }}" href="{{ route('admin.dashboard') }}"><x-icon name="layout-dashboard" />Dashboard</a>
                        @if(in_array(auth()->user()->role, ['super_admin', 'event_admin'], true))
                            <a class="inline-flex items-center gap-2 rounded-md px-3 py-2 {{ request()->routeIs('admin.events.*') ? 'bg-emerald-400 text-zinc-950 font-semibold' : 'text-zinc-700 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-white/10' }}" href="{{ route('admin.events.index') }}"><x-icon name="calendar-days" />Events</a>
                            <a class="inline-flex items-center gap-2 rounded-md px-3 py-2 {{ request()->routeIs('admin.coupons.*') ? 'bg-emerald-400 text-zinc-950 font-semibold' : 'text-zinc-700 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-white/10' }}" href="{{ route('admin.coupons.index') }}"><x-icon name="tag" />Coupons</a>
                            <a class="inline-flex items-center gap-2 rounded-md px-3 py-2 {{ request()->routeIs('admin.promotions.*') ? 'bg-emerald-400 text-zinc-950 font-semibold' : 'text-zinc-700 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-white/10' }}" href="{{ route('admin.promotions.index') }}"><x-icon name="sparkles" />Promotions</a>
                            <a class="inline-flex items-center gap-2 rounded-md px-3 py-2 {{ request()->routeIs('admin.orders.*') ? 'bg-emerald-400 text-zinc-950 font-semibold' : 'text-zinc-700 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-white/10' }}" href="{{ route('admin.orders.index') }}"><x-icon name="shopping-bag" />Orders</a>
                        @endif
                        <a class="inline-flex items-center gap-2 rounded-md px-3 py-2 {{ request()->routeIs('admin.scanner') ? 'bg-emerald-400 text-zinc-950 font-semibold' : 'text-zinc-700 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-white/10' }}" href="{{ route('admin.scanner') }}"><x-icon name="scan-line" />Scanner</a>
                        <a class="inline-flex items-center gap-2 rounded-md px-3 py-2 {{ request()->routeIs('guides.gate-check-in') ? 'bg-emerald-400 text-zinc-950 font-semibold' : 'text-zinc-700 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-white/10' }}" href="{{ route('guides.gate-check-in') }}"><x-icon name="sparkles" />Check-in guide</a>
                        @if(auth()->user()->role === 'super_admin')
                            <a class="inline-flex items-center gap-2 rounded-md px-3 py-2 {{ request()->routeIs('admin.users.*') ? 'bg-emerald-400 text-zinc-950 font-semibold' : 'text-zinc-700 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-white/10' }}" href="{{ route('admin.users.index') }}"><x-icon name="users" />Users</a>
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
                <div class="inline-flex items-center gap-2 font-semibold text-zinc-950 dark:text-white">
                    <span class="grid h-8 w-8 place-items-center rounded-md bg-emerald-400 text-zinc-950"><x-icon name="ticket" class="h-4 w-4" /></span>
                    TicketFlow
                </div>
                <nav class="flex flex-wrap gap-x-4 gap-y-2">
                    <a class="hover:text-emerald-700 dark:hover:text-emerald-200" href="{{ route('legal.terms') }}">Terms and Conditions / ข้อกำหนดและเงื่อนไข</a>
                </nav>
            </div>
            <!-- <p>This page provides general service information and is not legal advice. / หน้านี้เป็นข้อมูลทั่วไปของบริการ ไม่ใช่คำแนะนำทางกฎหมาย</p> -->
        </div>
    </footer>
</body>
</html>
