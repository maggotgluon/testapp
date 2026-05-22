<x-layouts.app title="Profile">
    <section class="flex flex-wrap items-center gap-4 rounded-lg border border-zinc-200 bg-white p-5 dark:border-white/10 dark:bg-white/[0.04]">
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
    </section>

    <h2 class="mt-8 text-2xl font-semibold text-zinc-950 dark:text-white">My orders and tickets / ออเดอร์และตั๋วของฉัน</h2>
    <div class="mt-6 grid gap-4">
        @forelse($orders as $order)
            <div class="rounded-lg border border-zinc-200 dark:border-white/10 bg-white dark:bg-white/[0.04] p-5">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h2 class="font-semibold text-zinc-950 dark:text-white">{{ $order->order_number }}</h2>
                        <p class="text-sm text-zinc-600 dark:text-zinc-400">{{ $order->created_at->format('M j, Y H:i') }} · THB {{ number_format($order->total_thb) }}</p>
                    </div>
                    <span class="rounded bg-zinc-100 dark:bg-white/10 px-3 py-1 text-sm text-emerald-700 dark:text-emerald-200">{{ str_replace('_', ' ', $order->status) }}</span>
                </div>
                <div class="mt-4 flex flex-wrap gap-2">
                    @foreach($order->tickets as $ticket)
                        <a class="rounded-md border border-zinc-200 dark:border-white/10 px-3 py-2 text-sm text-zinc-700 dark:text-zinc-200 hover:border-emerald-300" href="{{ route('tickets.show', $ticket->uuid) }}">{{ $ticket->event->name }} · {{ $ticket->ticketType->name }}</a>
                    @endforeach
                </div>
            </div>
        @empty
            <div class="rounded-lg border border-zinc-200 dark:border-white/10 bg-white dark:bg-white/[0.04] p-8 text-zinc-700 dark:text-zinc-300">No orders yet. / ยังไม่มีออเดอร์</div>
        @endforelse
    </div>
</x-layouts.app>
