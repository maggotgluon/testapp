@php
    $webPushEnabled = filled(config('webpush.vapid.public_key')) && filled(config('webpush.vapid.private_key'));
    $lineNotificationsEnabled = filled(config('services.line.messaging_channel_access_token')) && filled(config('services.line.messaging_channel_secret')) && filled(config('services.line.official_account_url'));
@endphp
<x-layouts.app title="Profile">
    <section class="flex flex-wrap items-center justify-between gap-4 rounded-lg border border-zinc-200 bg-white p-5 dark:border-white/10 dark:bg-white/[0.04]">
        <div class="flex flex-wrap items-center gap-4">
            @if(auth()->user()->avatar)
                <img class="h-16 w-16 rounded-full object-cover ring-2 ring-emerald-400/40" src="{{ auth()->user()->avatar }}" alt="{{ auth()->user()->name }}">
            @else
                <div class="grid h-16 w-16 place-items-center rounded-full bg-emerald-400 text-xl font-semibold text-zinc-950">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</div>
            @endif
            <div>
                <h1 class="text-3xl font-semibold text-zinc-950 dark:text-white">{{ auth()->user()->name }}</h1>
                <div class="mt-1 flex flex-wrap gap-x-3 gap-y-1 text-sm text-zinc-600 dark:text-zinc-400">
                    <span>{{ auth()->user()->phone ?: 'No phone yet / ยังไม่มีเบอร์โทร' }}</span>
                    <span>{{ auth()->user()->email ?: 'No email yet / ยังไม่มีอีเมล' }}</span>
                    <span>{{ strtoupper(auth()->user()->provider ?: 'guest') }}</span>
                </div>
            </div>
        </div>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button class="inline-flex items-center gap-2 rounded-md border border-zinc-200 px-4 py-2 text-sm font-semibold text-zinc-800 hover:bg-zinc-50 dark:border-white/10 dark:text-zinc-100 dark:hover:bg-white/10"><x-icon name="log-out" />Logout / ออกจากระบบ</button>
        </form>
    </section>

    @if($webPushEnabled || $lineNotificationsEnabled)
    <section class="mt-6 grid gap-4 rounded-lg border border-zinc-200 bg-white p-5 dark:border-white/10 dark:bg-white/[0.04] {{ $webPushEnabled && $lineNotificationsEnabled ? 'md:grid-cols-2' : '' }}">
        @if($webPushEnabled)
        <div>
            <h2 class="inline-flex items-center gap-2 text-lg font-semibold text-zinc-950 dark:text-white"><x-icon name="bell" class="h-5 w-5 text-emerald-500" />Notifications / การแจ้งเตือน</h2>
            <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">Enable reminders and ticket updates on this device. / เปิดรับการแจ้งเตือนและอัปเดตตั๋วบนอุปกรณ์นี้</p>
            <div class="mt-4" x-data="webPushSettings()" x-init="init()">
                <button class="inline-flex items-center gap-2 rounded-md bg-emerald-400 px-4 py-2 text-sm font-semibold text-zinc-950 disabled:cursor-not-allowed disabled:opacity-60" type="button" @click="subscribe()" :disabled="!supported || subscribed">
                    <x-icon name="bell" />Enable Web Push / เปิด Web Push
                </button>
                <button class="ml-2 inline-flex items-center gap-2 rounded-md border border-zinc-200 px-4 py-2 text-sm font-semibold text-zinc-800 disabled:cursor-not-allowed disabled:opacity-60 dark:border-white/10 dark:text-zinc-100" type="button" @click="unsubscribe()" :disabled="!subscribed">
                    <x-icon name="x" />Turn off / ปิด
                </button>
                <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400" x-text="message"></p>
            </div>
        </div>
        @endif
        @if($lineNotificationsEnabled)
        <div>
            <h2 class="inline-flex items-center gap-2 text-lg font-semibold text-zinc-950 dark:text-white"><x-icon name="heart-handshake" class="h-5 w-5 text-emerald-500" />LINE updates / แจ้งเตือนผ่าน LINE</h2>
            <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">
                @if(auth()->user()->provider === 'line')
                    LINE account connected. / เชื่อมต่อบัญชี LINE แล้ว
                @else
                    Login with LINE to receive LINE ticket updates. / เข้าสู่ระบบด้วย LINE เพื่อรับอัปเดตตั๋วผ่าน LINE
                @endif
            </p>
            <a class="mt-4 inline-flex items-center gap-2 rounded-md border border-zinc-200 px-4 py-2 text-sm font-semibold text-zinc-800 hover:border-emerald-300 dark:border-white/10 dark:text-zinc-100" href="{{ config('services.line.official_account_url') }}" target="_blank" rel="noopener"><x-icon name="heart-handshake" />Add LINE Official Account / เพิ่มเพื่อน LINE OA</a>
        </div>
        @endif
    </section>
    @endif

    <div class="mt-8 flex flex-wrap items-center gap-2">
        <a class="inline-flex items-center gap-2 rounded-md px-4 py-2 text-sm font-semibold {{ $activeView === 'orders' ? 'bg-emerald-400 text-zinc-950' : 'border border-zinc-200 text-zinc-700 hover:bg-zinc-50 dark:border-white/10 dark:text-zinc-200 dark:hover:bg-white/10' }}" href="{{ route('profile', ['view' => 'orders']) }}"><x-icon name="receipt" />Orders / ออเดอร์</a>
        <a class="inline-flex items-center gap-2 rounded-md px-4 py-2 text-sm font-semibold {{ $activeView === 'tickets' ? 'bg-emerald-400 text-zinc-950' : 'border border-zinc-200 text-zinc-700 hover:bg-zinc-50 dark:border-white/10 dark:text-zinc-200 dark:hover:bg-white/10' }}" href="{{ route('profile', ['view' => 'tickets']) }}"><x-icon name="ticket" />Tickets / ตั๋ว</a>
    </div>

    @if($activeView === 'orders')
        <div class="mt-6 grid gap-6">
            @forelse($orderEvents as $event)
                <section class="overflow-hidden rounded-lg border border-zinc-200 bg-white dark:border-white/10 dark:bg-white/[0.04]">
                    <div class="grid gap-4 border-b border-zinc-200 p-4 dark:border-white/10 sm:grid-cols-[96px_1fr]">
                        <div class="aspect-[4/5] overflow-hidden rounded-md bg-zinc-100 dark:bg-zinc-900">
                            @if($event->poster_path)
                                <img class="h-full w-full object-cover" src="{{ asset('uploads/'.$event->poster_path) }}" alt="{{ $event->name }}">
                            @endif
                        </div>
                        <div class="self-center">
                            <h2 class="text-xl font-semibold text-zinc-950 dark:text-white">{{ $event->name }}</h2>
                            <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">{{ $event->starts_at->format('M j, Y H:i') }} · {{ $event->venue }}</p>
                        </div>
                    </div>
                    <div class="grid gap-4 p-4">
                        @foreach($orders->filter(fn ($order) => $order->items->contains('event_id', $event->id)) as $order)
                            <div class="rounded-lg border border-zinc-200 bg-zinc-50 p-5 dark:border-white/10 dark:bg-zinc-900">
                                <div class="flex flex-wrap items-center justify-between gap-3">
                                    <div>
                                        <h3 class="font-semibold text-zinc-950 dark:text-white">{{ $order->order_number }}</h3>
                                        <p class="text-sm text-zinc-600 dark:text-zinc-400">{{ $order->created_at->format('M j, Y H:i') }} · THB {{ number_format($order->total_thb) }}</p>
                                    </div>
                                    <span class="rounded bg-zinc-100 px-3 py-1 text-sm text-emerald-700 dark:bg-white/10 dark:text-emerald-200">{{ str_replace('_', ' ', $order->status) }}</span>
                                </div>
                                <div class="mt-4 flex flex-wrap gap-2">
                                    <a class="inline-flex items-center gap-2 rounded-md border border-zinc-200 px-3 py-2 text-sm text-zinc-700 hover:border-emerald-300 dark:border-white/10 dark:text-zinc-200" href="{{ route('orders.show', $order) }}"><x-icon name="receipt" />View order / ดูออเดอร์</a>
                                    @foreach($order->tickets->where('event_id', $event->id) as $ticket)
                                        <a class="inline-flex items-center gap-2 rounded-md border border-zinc-200 px-3 py-2 text-sm text-zinc-700 hover:border-emerald-300 dark:border-white/10 dark:text-zinc-200" href="{{ route('tickets.show', $ticket->uuid) }}"><x-icon name="ticket" />{{ $ticket->ticketType->name }} · {{ $ticket->holder_name }}</a>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                </section>
            @empty
                <div class="rounded-lg border border-zinc-200 bg-white p-8 text-zinc-700 dark:border-white/10 dark:bg-white/[0.04] dark:text-zinc-300">No orders yet. / ยังไม่มีออเดอร์</div>
            @endforelse
        </div>
    @else
        <div class="mt-6 grid gap-6">
            @forelse($ticketEvents as $event)
                <section class="overflow-hidden rounded-lg border border-zinc-200 bg-white dark:border-white/10 dark:bg-white/[0.04]">
                    <div class="grid gap-4 border-b border-zinc-200 p-4 dark:border-white/10 sm:grid-cols-[96px_1fr]">
                        <div class="aspect-[4/5] overflow-hidden rounded-md bg-zinc-100 dark:bg-zinc-900">
                            @if($event->poster_path)
                                <img class="h-full w-full object-cover" src="{{ asset('uploads/'.$event->poster_path) }}" alt="{{ $event->name }}">
                            @endif
                        </div>
                        <div class="self-center">
                            <h2 class="text-xl font-semibold text-zinc-950 dark:text-white">{{ $event->name }}</h2>
                            <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">{{ $event->starts_at->format('M j, Y H:i') }} · {{ $event->venue }}</p>
                        </div>
                    </div>
                    <div class="grid gap-3 p-4 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach($tickets->where('event_id', $event->id) as $ticket)
                            <div class="rounded-lg border border-zinc-200 bg-zinc-50 p-4 dark:border-white/10 dark:bg-zinc-900">
                                <a class="block hover:text-emerald-700 dark:hover:text-emerald-200" href="{{ route('tickets.show', $ticket->uuid) }}">
                                    <div class="font-semibold text-zinc-950 dark:text-white">{{ $ticket->ticketType->name }}</div>
                                    <div class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">{{ $ticket->holder_name }} · {{ str_replace('_', ' ', $ticket->status) }}</div>
                                </a>
                                @if($ticket->order)
                                    <a class="mt-3 inline-flex items-center gap-2 rounded-md border border-zinc-200 px-3 py-2 text-xs font-semibold text-zinc-700 hover:border-emerald-300 dark:border-white/10 dark:text-zinc-200" href="{{ route('orders.show', $ticket->order) }}"><x-icon name="receipt" class="h-3.5 w-3.5" />{{ $ticket->order->order_number }} · View order / ดูออเดอร์</a>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </section>
            @empty
                <div class="rounded-lg border border-zinc-200 bg-white p-8 text-zinc-700 dark:border-white/10 dark:bg-white/[0.04] dark:text-zinc-300">No tickets yet. / ยังไม่มีตั๋ว</div>
            @endforelse
        </div>
    @endif
</x-layouts.app>
