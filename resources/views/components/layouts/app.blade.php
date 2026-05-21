<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? config('app.name', 'Event Ticket') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-zinc-950 text-zinc-100 antialiased">
    <div class="border-b border-white/10 bg-zinc-950/85 backdrop-blur">
        <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-4 sm:px-6 lg:px-8">
            <a href="{{ route('events.index') }}" class="text-lg font-semibold tracking-tight">TicketFlow</a>
            <nav class="flex items-center gap-2 text-sm text-zinc-300">
                <a class="rounded-md px-3 py-2 hover:bg-white/10" href="{{ route('events.index') }}">Events</a>
                <a class="rounded-md px-3 py-2 hover:bg-white/10" href="{{ route('orders.lookup') }}">Find order</a>
                @auth
                    <a class="rounded-md px-3 py-2 hover:bg-white/10" href="{{ route('profile') }}">Profile</a>
                    @if(auth()->user()->isAdmin())
                        <a class="rounded-md bg-emerald-400 px-3 py-2 font-semibold text-zinc-950" href="{{ route('admin.dashboard') }}">Admin</a>
                    @endif
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button class="rounded-md px-3 py-2 hover:bg-white/10">Logout</button>
                    </form>
                @else
                    <a class="rounded-md bg-emerald-400 px-3 py-2 font-semibold text-zinc-950" href="{{ route('login') }}">Login</a>
                    <a class="rounded-md px-3 py-2 hover:bg-white/10" href="{{ route('admin.login') }}">Admin</a>
                @endauth
            </nav>
        </div>
        @auth
            @if(auth()->user()->isAdmin() && request()->is('admin*'))
                <div class="border-t border-white/10">
                    <div class="mx-auto flex max-w-7xl gap-2 overflow-x-auto px-4 py-3 text-sm sm:px-6 lg:px-8">
                        <a class="rounded-md px-3 py-2 {{ request()->routeIs('admin.dashboard') ? 'bg-emerald-400 text-zinc-950 font-semibold' : 'text-zinc-300 hover:bg-white/10' }}" href="{{ route('admin.dashboard') }}">Dashboard</a>
                        @if(in_array(auth()->user()->role, ['super_admin', 'event_admin'], true))
                            <a class="rounded-md px-3 py-2 {{ request()->routeIs('admin.events.*') ? 'bg-emerald-400 text-zinc-950 font-semibold' : 'text-zinc-300 hover:bg-white/10' }}" href="{{ route('admin.events.index') }}">Events</a>
                            <a class="rounded-md px-3 py-2 {{ request()->routeIs('admin.coupons.*') ? 'bg-emerald-400 text-zinc-950 font-semibold' : 'text-zinc-300 hover:bg-white/10' }}" href="{{ route('admin.coupons.index') }}">Coupons</a>
                            <a class="rounded-md px-3 py-2 {{ request()->routeIs('admin.orders.*') ? 'bg-emerald-400 text-zinc-950 font-semibold' : 'text-zinc-300 hover:bg-white/10' }}" href="{{ route('admin.orders.index') }}">Orders</a>
                        @endif
                        <a class="rounded-md px-3 py-2 {{ request()->routeIs('admin.scanner') ? 'bg-emerald-400 text-zinc-950 font-semibold' : 'text-zinc-300 hover:bg-white/10' }}" href="{{ route('admin.scanner') }}">Scanner</a>
                        @if(auth()->user()->role === 'super_admin')
                            <a class="rounded-md px-3 py-2 {{ request()->routeIs('admin.users.*') ? 'bg-emerald-400 text-zinc-950 font-semibold' : 'text-zinc-300 hover:bg-white/10' }}" href="{{ route('admin.users.index') }}">Users</a>
                        @endif
                    </div>
                </div>
            @endif
        @endauth
    </div>

    <main class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        @if(session('status'))
            <div class="mb-6 rounded-md border border-emerald-400/30 bg-emerald-400/10 px-4 py-3 text-sm text-emerald-100">{{ session('status') }}</div>
        @endif
        @if($errors->any())
            <div class="mb-6 rounded-md border border-rose-400/30 bg-rose-400/10 px-4 py-3 text-sm text-rose-100">{{ $errors->first() }}</div>
        @endif
        {{ $slot }}
    </main>
</body>
</html>
