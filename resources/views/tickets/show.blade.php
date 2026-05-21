<x-layouts.app :title="$ticket->event->name">
    <div class="mx-auto grid max-w-4xl gap-6 lg:grid-cols-[.8fr_1fr]">
        <div class="overflow-hidden rounded-lg border border-white/10 bg-white/[0.04]">
            <div class="aspect-[4/5] bg-gradient-to-br from-emerald-500 via-sky-500 to-zinc-800">
                @if($ticket->event->ticket_image_path || $ticket->event->poster_path)
                    <img class="h-full w-full object-cover" src="{{ asset('storage/'.($ticket->event->ticket_image_path ?: $ticket->event->poster_path)) }}" alt="{{ $ticket->event->name }}">
                @endif
            </div>
        </div>
        <div class="rounded-lg border border-white/10 bg-white/[0.04] p-6">
        <div class="flex items-start justify-between gap-4">
            <div>
                <p class="text-sm text-emerald-300">{{ $ticket->ticketType->name }}</p>
                <h1 class="text-3xl font-semibold text-white">{{ $ticket->event->name }}</h1>
                <p class="mt-2 text-zinc-400">{{ $ticket->event->venue }} · {{ $ticket->event->starts_at->format('M j, Y H:i') }}</p>
            </div>
            <span class="rounded bg-white/10 px-3 py-1 text-sm text-emerald-200">{{ str_replace('_', ' ', $ticket->status) }}</span>
        </div>
        <div class="mt-6 grid place-items-center rounded-lg bg-white p-5 text-zinc-950">
            <img class="h-56 w-56" src="{{ route('tickets.qr', $ticket->uuid) }}" alt="Ticket QR code">
            <div class="mt-4 font-mono text-xs">{{ $ticket->uuid }}</div>
        </div>
        <dl class="mt-6 grid gap-3 text-sm">
            <div><dt class="text-zinc-500">Holder</dt><dd class="text-white">{{ $ticket->holder_name }}</dd></div>
            <div><dt class="text-zinc-500">Order</dt><dd class="text-white">{{ $ticket->order->order_number }}</dd></div>
            <div><dt class="text-zinc-500">Check in</dt><dd class="text-white">{{ $ticket->checked_in_at?->format('M j, Y H:i') ?? 'Not yet' }}</dd></div>
            <div><dt class="text-zinc-500">Check out</dt><dd class="text-white">{{ $ticket->checked_out_at?->format('M j, Y H:i') ?? 'Not yet' }}</dd></div>
        </dl>
        </div>
    </div>
</x-layouts.app>
