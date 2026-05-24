@props([
    'title' => config('app.name', 'Event Ticket'),
    'metaDescription' => null,
    'metaImage' => null,
    'canonicalUrl' => null,
])
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title }}</title>
    @if($metaDescription)
        <meta name="description" content="{{ $metaDescription }}">
        <meta property="og:description" content="{{ $metaDescription }}">
        <meta name="twitter:description" content="{{ $metaDescription }}">
    @endif
    <meta property="og:title" content="{{ $title }}">
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
            <a href="{{ route('events.index') }}" class="text-lg font-semibold tracking-tight">TicketFlow</a>
            <nav class="flex items-center gap-2 text-sm text-zinc-700 dark:text-zinc-300">
                <!-- <a class="rounded-md px-3 py-2 hover:bg-zinc-100 dark:hover:bg-white/10" href="{{ route('events.index') }}">Events / อีเวนต์</a> -->
                <a class="rounded-md px-3 py-2 hover:bg-zinc-100 dark:hover:bg-white/10" href="{{ route('orders.lookup') }}">Find order / ค้นหาออเดอร์</a>
                @auth
                    <a class="rounded-md px-3 py-2 hover:bg-zinc-100 dark:hover:bg-white/10" href="{{ route('profile') }}">Profile / โปรไฟล์</a>
                    @if(auth()->user()->isAdmin())
                        <a class="rounded-md bg-emerald-400 px-3 py-2 font-semibold text-zinc-950" href="{{ route('admin.dashboard') }}">Admin / ผู้ดูแล</a>
                    @endif
                @else
                    <a class="rounded-md bg-emerald-400 px-3 py-2 font-semibold text-zinc-950" href="{{ route('login') }}">Login / เข้าสู่ระบบ</a>
                    <!-- <a class="rounded-md px-3 py-2 hover:bg-zinc-100 dark:hover:bg-white/10" href="{{ route('admin.login') }}">Admin / ผู้ดูแล</a> -->
                @endauth
            </nav>
        </div>
        @auth
            @if(auth()->user()->isAdmin() && request()->is('admin*'))
                <div class="border-t border-zinc-200 dark:border-white/10">
                    <div class="mx-auto flex max-w-7xl gap-2 overflow-x-auto px-4 py-3 text-sm sm:px-6 lg:px-8">
                        <a class="rounded-md px-3 py-2 {{ request()->routeIs('admin.dashboard') ? 'bg-emerald-400 text-zinc-950 font-semibold' : 'text-zinc-700 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-white/10' }}" href="{{ route('admin.dashboard') }}">Dashboard / ภาพรวม</a>
                        @if(in_array(auth()->user()->role, ['super_admin', 'event_admin'], true))
                            <a class="rounded-md px-3 py-2 {{ request()->routeIs('admin.events.*') ? 'bg-emerald-400 text-zinc-950 font-semibold' : 'text-zinc-700 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-white/10' }}" href="{{ route('admin.events.index') }}">Events / อีเวนต์</a>
                            <a class="rounded-md px-3 py-2 {{ request()->routeIs('admin.coupons.*') ? 'bg-emerald-400 text-zinc-950 font-semibold' : 'text-zinc-700 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-white/10' }}" href="{{ route('admin.coupons.index') }}">Coupons / คูปอง</a>
                            <a class="rounded-md px-3 py-2 {{ request()->routeIs('admin.orders.*') ? 'bg-emerald-400 text-zinc-950 font-semibold' : 'text-zinc-700 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-white/10' }}" href="{{ route('admin.orders.index') }}">Orders / ออเดอร์</a>
                        @endif
                        <a class="rounded-md px-3 py-2 {{ request()->routeIs('admin.scanner') ? 'bg-emerald-400 text-zinc-950 font-semibold' : 'text-zinc-700 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-white/10' }}" href="{{ route('admin.scanner') }}">Scanner / สแกนตั๋ว</a>
                        @if(auth()->user()->role === 'super_admin')
                            <a class="rounded-md px-3 py-2 {{ request()->routeIs('admin.users.*') ? 'bg-emerald-400 text-zinc-950 font-semibold' : 'text-zinc-700 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-white/10' }}" href="{{ route('admin.users.index') }}">Users / ผู้ใช้</a>
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
</body>
</html>
