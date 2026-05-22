<x-layouts.app :title="$event->name.' overview'">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <p class="text-sm font-medium uppercase tracking-[0.18em] text-emerald-600 dark:text-emerald-300">Event operations / ภาพรวมการจัดงาน</p>
            <h1 class="mt-2 text-3xl font-semibold text-zinc-950 dark:text-white">{{ $event->name }}</h1>
            <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">{{ $event->starts_at->format('M j, Y H:i') }} · {{ $event->venue }}</p>
        </div>
        <a class="rounded-md border border-zinc-200 dark:border-white/10 px-3 py-2 text-sm text-zinc-800 dark:text-zinc-100" href="{{ route('admin.events.edit', $event) }}">Edit event / แก้ไขอีเวนต์</a>
    </div>

    <div class="mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="rounded-lg border border-zinc-200 dark:border-white/10 bg-white dark:bg-white/[0.04] p-5">
            <div class="text-sm text-zinc-600 dark:text-zinc-400">Gross event revenue / รายได้รวม</div>
            <div class="mt-2 text-3xl font-semibold text-zinc-950 dark:text-white">THB {{ number_format($grossRevenue) }}</div>
        </div>
        <div class="rounded-lg border border-zinc-200 dark:border-white/10 bg-white dark:bg-white/[0.04] p-5">
            <div class="text-sm text-zinc-600 dark:text-zinc-400">Orders / ออเดอร์</div>
            <div class="mt-2 text-3xl font-semibold text-zinc-950 dark:text-white">{{ number_format($orderStatusCounts->sum()) }}</div>
        </div>
        <div class="rounded-lg border border-zinc-200 dark:border-white/10 bg-white dark:bg-white/[0.04] p-5">
            <div class="text-sm text-zinc-600 dark:text-zinc-400">Tickets issued / ตั๋วที่ออกแล้ว</div>
            <div class="mt-2 text-3xl font-semibold text-zinc-950 dark:text-white">{{ number_format($totalTickets) }}</div>
        </div>
        <div class="rounded-lg border border-zinc-200 dark:border-white/10 bg-white dark:bg-white/[0.04] p-5">
            <div class="text-sm text-zinc-600 dark:text-zinc-400">Checked in/out / เช็กอินหรือเช็กเอาต์</div>
            <div class="mt-2 text-3xl font-semibold text-zinc-950 dark:text-white">{{ number_format($checkedInTickets) }}</div>
        </div>
    </div>

    <div class="mt-6 grid gap-6 xl:grid-cols-[.8fr_1.2fr]">
        <section class="rounded-lg border border-zinc-200 dark:border-white/10 bg-white dark:bg-white/[0.04] p-5">
            <h2 class="text-xl font-semibold text-zinc-950 dark:text-white">Ticket type sales / ยอดขายตามประเภทตั๋ว</h2>
            <div class="mt-4 grid gap-3">
                @foreach($ticketTypeStats as $stat)
                    <div class="rounded-md border border-zinc-200 dark:border-white/10 bg-zinc-50 dark:bg-zinc-900 p-4">
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <div class="font-medium text-zinc-950 dark:text-white">{{ $stat['name'] }}</div>
                                <div class="text-sm text-zinc-600 dark:text-zinc-400">{{ number_format($stat['quantity']) }} sold / ขายแล้ว @if($stat['capacity']) / {{ number_format($stat['capacity']) }} capacity / ความจุ @endif</div>
                            </div>
                            <div class="text-right text-sm text-emerald-700 dark:text-emerald-200">THB {{ number_format($stat['revenue']) }}</div>
                        </div>
                    </div>
                @endforeach
            </div>
        </section>

        <section class="grid gap-4 sm:grid-cols-2">
            <div class="rounded-lg border border-zinc-200 dark:border-white/10 bg-white dark:bg-white/[0.04] p-5">
                <h2 class="text-xl font-semibold text-zinc-950 dark:text-white">Order status / สถานะออเดอร์</h2>
                <div class="mt-4 grid gap-2 text-sm">
                    @foreach(['pending', 'approved', 'rejected', 'refunded'] as $status)
                        <div class="flex justify-between rounded bg-zinc-50 dark:bg-zinc-900 px-3 py-2"><span class="text-zinc-700 dark:text-zinc-300">{{ str_replace('_', ' ', $status) }}</span><span class="font-semibold text-zinc-950 dark:text-white">{{ $orderStatusCounts[$status] ?? 0 }}</span></div>
                    @endforeach
                </div>
            </div>
            <div class="rounded-lg border border-zinc-200 dark:border-white/10 bg-white dark:bg-white/[0.04] p-5">
                <h2 class="text-xl font-semibold text-zinc-950 dark:text-white">Ticket/check-in status / สถานะตั๋วและเช็กอิน</h2>
                <div class="mt-4 grid gap-2 text-sm">
                    @foreach(['pending', 'approved', 'checked_in', 'checked_out', 'expired', 'rejected', 'refunded'] as $status)
                        <div class="flex justify-between rounded bg-zinc-50 dark:bg-zinc-900 px-3 py-2"><span class="text-zinc-700 dark:text-zinc-300">{{ str_replace('_', ' ', $status) }}</span><span class="font-semibold text-zinc-950 dark:text-white">{{ $ticketStatusCounts[$status] ?? 0 }}</span></div>
                    @endforeach
                </div>
            </div>
        </section>
    </div>

    <section class="mt-6 rounded-lg border border-zinc-200 dark:border-white/10 bg-white dark:bg-white/[0.04]">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-zinc-200 dark:border-white/10 p-4">
            <h2 class="text-xl font-semibold text-zinc-950 dark:text-white">Orders for this event / ออเดอร์ของอีเวนต์นี้</h2>
            <form class="flex gap-2">
                <select class="rounded-md border border-zinc-200 dark:border-white/10 bg-white dark:bg-zinc-950 px-3 py-2 text-sm text-zinc-950 dark:text-white" name="order_status">
                    <option value="">All orders / ทุกออเดอร์</option>
                    @foreach(['pending', 'approved', 'rejected', 'refunded'] as $status)
                        <option value="{{ $status }}" @selected(request('order_status') === $status)>{{ str_replace('_', ' ', $status) }}</option>
                    @endforeach
                </select>
                <button class="rounded-md border border-zinc-200 dark:border-white/10 px-3 py-2 text-sm text-zinc-800 dark:text-zinc-100">Filter / กรอง</button>
            </form>
        </div>
        <div class="divide-y divide-white/10">
            @forelse($orders as $order)
                <div class="grid gap-4 p-4 lg:grid-cols-[1fr_auto]">
                    <div>
                        <a class="font-semibold text-zinc-950 dark:text-white hover:text-emerald-600 dark:text-emerald-300" href="{{ route('admin.orders.show', $order) }}">{{ $order->order_number }}</a>
                        <div class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">{{ $order->customer_name }} · {{ $order->customer_phone }} · {{ str_replace('_', ' ', $order->status) }}</div>
                        <div class="mt-2 flex flex-wrap gap-2 text-xs text-zinc-700 dark:text-zinc-300">
                            @foreach($order->items as $item)
                                <span class="rounded bg-zinc-100 dark:bg-white/10 px-2 py-1">{{ $item->ticketType->name }} x {{ $item->quantity }}</span>
                            @endforeach
                        </div>
                    </div>
                    <div class="flex flex-wrap items-start gap-2">
                        @if($order->status !== 'approved')
                            <form method="POST" action="{{ route('admin.orders.approve', $order) }}">@csrf<button class="rounded-md bg-emerald-400 px-3 py-2 text-sm font-semibold text-zinc-950">Approve / อนุมัติ</button></form>
                        @endif
                        <form method="POST" action="{{ route('admin.orders.reject', $order) }}">@csrf<button class="rounded-md bg-rose-400 px-3 py-2 text-sm font-semibold text-zinc-950">Reject / ปฏิเสธ</button></form>
                        <form method="POST" action="{{ route('admin.orders.refund', $order) }}">@csrf<button class="rounded-md border border-zinc-200 dark:border-white/10 px-3 py-2 text-sm text-zinc-800 dark:text-zinc-100">Refund / คืนเงิน</button></form>
                    </div>
                </div>
            @empty
                <div class="p-6 text-zinc-600 dark:text-zinc-400">No orders found for this event. / ไม่พบออเดอร์ของอีเวนต์นี้</div>
            @endforelse
        </div>
        <div class="p-4">{{ $orders->withQueryString()->links() }}</div>
    </section>

    <section class="mt-6 rounded-lg border border-zinc-200 dark:border-white/10 bg-white dark:bg-white/[0.04]">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-zinc-200 dark:border-white/10 p-4">
            <h2 class="text-xl font-semibold text-zinc-950 dark:text-white">Tickets and check-in / ตั๋วและการเช็กอิน</h2>
            <form class="flex gap-2">
                <select class="rounded-md border border-zinc-200 dark:border-white/10 bg-white dark:bg-zinc-950 px-3 py-2 text-sm text-zinc-950 dark:text-white" name="ticket_status">
                    <option value="">All tickets / ทุกตั๋ว</option>
                    @foreach(['pending', 'approved', 'checked_in', 'checked_out', 'expired', 'rejected', 'refunded'] as $status)
                        <option value="{{ $status }}" @selected(request('ticket_status') === $status)>{{ str_replace('_', ' ', $status) }}</option>
                    @endforeach
                </select>
                <button class="rounded-md border border-zinc-200 dark:border-white/10 px-3 py-2 text-sm text-zinc-800 dark:text-zinc-100">Filter / กรอง</button>
            </form>
        </div>
        <div class="divide-y divide-white/10">
            @forelse($tickets as $ticket)
                <div class="grid gap-4 p-4 lg:grid-cols-[1fr_auto]">
                    <div>
                        <a class="font-semibold text-zinc-950 dark:text-white hover:text-emerald-600 dark:text-emerald-300" href="{{ route('tickets.show', $ticket->uuid) }}">{{ $ticket->holder_name }}</a>
                        <div class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">{{ $ticket->ticketType->name }} · {{ $ticket->order->order_number }} · {{ str_replace('_', ' ', $ticket->status) }}</div>
                        <div class="mt-1 text-xs text-zinc-500">In / เข้า: {{ $ticket->checked_in_at?->format('M j H:i') ?? '-' }} · Out / ออก: {{ $ticket->checked_out_at?->format('M j H:i') ?? '-' }}</div>
                    </div>
                    <form method="POST" action="{{ route('admin.events.tickets.status', [$event, $ticket]) }}" class="flex flex-wrap items-start gap-2">
                        @csrf
                        @method('PATCH')
                        <select class="rounded-md border border-zinc-200 dark:border-white/10 bg-white dark:bg-zinc-950 px-3 py-2 text-sm text-zinc-950 dark:text-white" name="status">
                            @foreach(['pending', 'approved', 'checked_in', 'checked_out', 'expired', 'rejected', 'refunded'] as $status)
                                <option value="{{ $status }}" @selected($ticket->status === $status)>{{ str_replace('_', ' ', $status) }}</option>
                            @endforeach
                        </select>
                        <button class="rounded-md bg-emerald-400 px-3 py-2 text-sm font-semibold text-zinc-950">Update / อัปเดต</button>
                    </form>
                </div>
            @empty
                <div class="p-6 text-zinc-600 dark:text-zinc-400">No tickets found for this event. / ไม่พบตั๋วของอีเวนต์นี้</div>
            @endforelse
        </div>
        <div class="p-4">{{ $tickets->withQueryString()->links() }}</div>
    </section>
</x-layouts.app>
