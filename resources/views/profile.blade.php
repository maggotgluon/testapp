<x-layouts.app title="Profile">
    <h1 class="text-3xl font-semibold text-zinc-950 dark:text-white">My orders and tickets</h1>
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
            <div class="rounded-lg border border-zinc-200 dark:border-white/10 bg-white dark:bg-white/[0.04] p-8 text-zinc-700 dark:text-zinc-300">No orders yet.</div>
        @endforelse
    </div>
</x-layouts.app>
