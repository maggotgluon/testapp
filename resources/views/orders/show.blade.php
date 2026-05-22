<x-layouts.app :title="$order->order_number">
    <div class="grid gap-6 lg:grid-cols-[1fr_.75fr]">
        <section class="rounded-lg border border-zinc-200 dark:border-white/10 bg-white dark:bg-white/[0.04] p-6">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <p class="text-sm text-zinc-600 dark:text-zinc-400">Order / ออเดอร์</p>
                    <h1 class="text-3xl font-semibold text-zinc-950 dark:text-white">{{ $order->order_number }}</h1>
                </div>
                <span class="rounded bg-zinc-100 dark:bg-white/10 px-3 py-1 text-sm text-emerald-700 dark:text-emerald-200">{{ str_replace('_', ' ', $order->status) }}</span>
            </div>
            <div class="mt-6 divide-y divide-white/10">
                @foreach($order->items as $item)
                    <div class="flex justify-between gap-4 py-4">
                        <div>
                            <div class="font-medium text-zinc-950 dark:text-white">{{ $item->event->name }}</div>
                            <div class="text-sm text-zinc-600 dark:text-zinc-400">{{ $item->ticketType->name }} x {{ $item->quantity }}</div>
                        </div>
                        <div class="text-zinc-700 dark:text-zinc-200">THB {{ number_format($item->line_total_thb) }}</div>
                    </div>
                @endforeach
            </div>
            <dl class="mt-5 grid gap-2 text-sm">
                <div class="flex justify-between"><dt class="text-zinc-600 dark:text-zinc-400">Subtotal / ยอดรวม</dt><dd>THB {{ number_format($order->subtotal_thb) }}</dd></div>
                <div class="flex justify-between"><dt class="text-zinc-600 dark:text-zinc-400">Discount / ส่วนลด</dt><dd>THB {{ number_format($order->discount_thb) }}</dd></div>
                <div class="flex justify-between text-lg font-semibold text-zinc-950 dark:text-white"><dt>Total / ยอดสุทธิ</dt><dd>THB {{ number_format($order->total_thb) }}</dd></div>
            </dl>
        </section>
        <section class="rounded-lg border border-zinc-200 dark:border-white/10 bg-white dark:bg-white/[0.04] p-6">
            <h2 class="text-xl font-semibold text-zinc-950 dark:text-white">Tickets / ตั๋ว</h2>
            <div class="mt-4 grid gap-3">
                @foreach($order->tickets as $ticket)
                    <a class="rounded-md border border-zinc-200 dark:border-white/10 p-4 hover:border-emerald-300" href="{{ route('tickets.show', ['uuid' => $ticket->uuid, 'phone' => $ticket->holder_phone]) }}">
                        <div class="font-medium text-zinc-950 dark:text-white">{{ $ticket->event->name }}</div>
                        <div class="text-sm text-zinc-600 dark:text-zinc-400">{{ $ticket->ticketType->name }} · {{ str_replace('_', ' ', $ticket->status) }}</div>
                    </a>
                @endforeach
            </div>
        </section>
    </div>
</x-layouts.app>
