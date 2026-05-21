<x-layouts.app :title="$order->order_number">
    <div class="grid gap-6 lg:grid-cols-[1fr_.8fr]">
        <section class="rounded-lg border border-white/10 bg-white/[0.04] p-6">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <p class="text-sm text-zinc-400">Order</p>
                    <h1 class="text-3xl font-semibold text-white">{{ $order->order_number }}</h1>
                </div>
                <span class="rounded bg-white/10 px-3 py-1 text-sm text-emerald-200">{{ $order->status }}</span>
            </div>
            <dl class="mt-5 grid gap-2 text-sm text-zinc-300">
                <div><dt class="text-zinc-500">Customer</dt><dd>{{ $order->customer_name }} · {{ $order->customer_phone }}</dd></div>
                <div><dt class="text-zinc-500">Payment</dt><dd>{{ str_replace('_', ' ', $order->payment_method) }} · THB {{ number_format($order->total_thb) }}</dd></div>
                <div><dt class="text-zinc-500">Note</dt><dd>{{ $order->payment_note ?: 'No note' }}</dd></div>
            </dl>
            <div class="mt-6 flex flex-wrap gap-2">
                <form method="POST" action="{{ route('admin.orders.approve', $order) }}">@csrf<button class="rounded-md bg-emerald-400 px-4 py-2 font-semibold text-zinc-950">Approve</button></form>
                <form method="POST" action="{{ route('admin.orders.reject', $order) }}">@csrf<button class="rounded-md bg-rose-400 px-4 py-2 font-semibold text-zinc-950">Reject</button></form>
                <form method="POST" action="{{ route('admin.orders.refund', $order) }}">@csrf<button class="rounded-md border border-white/10 px-4 py-2 font-semibold text-zinc-100">Refund</button></form>
            </div>
            @if($order->payment_slip_path)
                <img class="mt-6 max-h-96 rounded-lg border border-white/10 object-contain" src="{{ asset('storage/'.$order->payment_slip_path) }}" alt="Payment slip">
            @endif
        </section>
        <section class="rounded-lg border border-white/10 bg-white/[0.04] p-6">
            <h2 class="text-xl font-semibold text-white">Tickets</h2>
            <div class="mt-4 grid gap-3">
                @foreach($order->tickets as $ticket)
                    <a class="rounded-md border border-white/10 p-4 hover:border-emerald-300" href="{{ route('tickets.show', $ticket->uuid) }}">
                        <div class="font-medium text-white">{{ $ticket->event->name }}</div>
                        <div class="text-sm text-zinc-400">{{ $ticket->ticketType->name }} · {{ $ticket->status }}</div>
                    </a>
                @endforeach
            </div>
        </section>
    </div>
</x-layouts.app>
