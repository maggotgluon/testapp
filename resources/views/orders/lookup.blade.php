<x-layouts.app title="Find order">
    <div class="mx-auto max-w-4xl">
        <form class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-white/10 dark:bg-white/[0.04]">
            <h1 class="text-2xl font-semibold text-zinc-950 dark:text-white">
                {{ $isAdmin ? 'Admin order search / ค้นหาออเดอร์สำหรับแอดมิน' : 'Find order without account / ค้นหาออเดอร์โดยไม่ต้องล็อกอิน' }}
            </h1>
            <div class="mt-5 grid gap-4 sm:grid-cols-2">
                <label class="text-sm text-zinc-700 dark:text-zinc-300">Phone / เบอร์โทร<input class="mt-1 w-full rounded-md border border-zinc-200 bg-white px-3 py-2 text-zinc-950 dark:border-white/10 dark:bg-zinc-950 dark:text-white" name="phone" value="{{ request('phone') }}" @required(! $isAdmin)></label>
                <label class="text-sm text-zinc-700 dark:text-zinc-300">Order number / เลขออเดอร์<input class="mt-1 w-full rounded-md border border-zinc-200 bg-white px-3 py-2 uppercase text-zinc-950 dark:border-white/10 dark:bg-zinc-950 dark:text-white" name="order_number" value="{{ request('order_number') }}" placeholder="BNML-0521-001" @required(! $isAdmin)></label>
            </div>
            <button class="mt-5 rounded-md bg-emerald-400 px-4 py-2 font-semibold text-zinc-950">Search / ค้นหา</button>
        </form>

        <div class="mt-6 grid gap-4">
            @foreach($orders as $order)
                <div class="rounded-lg border border-zinc-200 bg-white p-5 dark:border-white/10 dark:bg-white/[0.04]">
                    <div class="flex flex-wrap items-start justify-between gap-4">
                        <div>
                            <a class="font-semibold text-zinc-950 hover:text-emerald-700 dark:text-white dark:hover:text-emerald-200" href="{{ $isAdmin ? route('admin.orders.show', $order) : route('orders.show', ['order' => $order, 'phone' => $order->customer_phone]) }}">{{ $order->order_number }}</a>
                            <div class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">{{ $order->customer_name }} · {{ $order->customer_phone }} · {{ $order->customer_email ?: 'No email / ไม่มีอีเมล' }}</div>
                            <div class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">{{ str_replace('_', ' ', $order->status) }} · THB {{ number_format($order->total_thb) }}</div>
                        </div>
                        @if($isAdmin)
                            <div class="flex flex-wrap gap-2">
                                <form method="POST" action="{{ route('admin.orders.approve', $order) }}" onsubmit="return confirm('Approve this order? / ยืนยันอนุมัติออเดอร์นี้?')">@csrf<button class="rounded-md bg-emerald-400 px-3 py-2 text-sm font-semibold text-zinc-950">Approve / อนุมัติ</button></form>
                                <form method="POST" action="{{ route('admin.orders.reject', $order) }}" onsubmit="return confirm('Reject this order? / ยืนยันปฏิเสธออเดอร์นี้?')">@csrf<button class="rounded-md bg-rose-400 px-3 py-2 text-sm font-semibold text-zinc-950">Reject / ปฏิเสธ</button></form>
                                <form method="POST" action="{{ route('admin.orders.refund', $order) }}" onsubmit="return confirm('Refund this order? / ยืนยันคืนเงินออเดอร์นี้?')">@csrf<button class="rounded-md border border-zinc-200 px-3 py-2 text-sm font-semibold text-zinc-800 dark:border-white/10 dark:text-zinc-100">Refund / คืนเงิน</button></form>
                                <form method="POST" action="{{ route('admin.orders.destroy', $order) }}" onsubmit="return confirm('Delete this order and its tickets? / ลบออเดอร์และตั๋วทั้งหมด?')">@csrf @method('DELETE')<button class="rounded-md border border-rose-300 px-3 py-2 text-sm font-semibold text-rose-700 dark:border-rose-400/40 dark:text-rose-200">Delete / ลบ</button></form>
                            </div>
                        @endif
                    </div>
                    <div class="mt-4 grid gap-2">
                        @foreach($order->tickets as $ticket)
                            <div class="flex flex-wrap items-center justify-between gap-3 rounded-md bg-zinc-50 px-3 py-2 text-sm dark:bg-zinc-900">
                                <a class="text-zinc-800 hover:text-emerald-700 dark:text-zinc-100 dark:hover:text-emerald-200" href="{{ route('tickets.show', ['uuid' => $ticket->uuid, 'phone' => $ticket->holder_phone]) }}">{{ $ticket->event->name }} · {{ $ticket->ticketType->name }} · {{ $ticket->holder_name }}</a>
                                <span class="text-zinc-600 dark:text-zinc-400">{{ str_replace('_', ' ', $ticket->status) }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</x-layouts.app>
