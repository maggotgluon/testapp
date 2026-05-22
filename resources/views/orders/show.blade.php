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
            <div class="mt-5 rounded-md border border-emerald-400/30 bg-emerald-400/10 p-4 text-sm text-emerald-950 dark:text-emerald-50">
                <div class="font-semibold">Order received / ได้รับคำสั่งซื้อแล้ว</div>
                <p class="mt-1">Please keep this order number for future lookup: <span class="font-mono font-semibold">{{ $order->order_number }}</span>. Admin approval will activate the tickets after payment review. / กรุณาเก็บเลขออเดอร์นี้ไว้สำหรับค้นหาภายหลัง แอดมินจะอนุมัติตั๋วหลังตรวจสอบการชำระเงิน</p>
            </div>
            @guest
                <div class="mt-4 rounded-md border border-amber-400/30 bg-amber-400/10 p-4 text-sm text-amber-900 dark:text-amber-100">
                    <div class="font-semibold">Save this order to your account / บันทึกออเดอร์นี้ไว้ในบัญชี</div>
                    <p class="mt-1">Login now to keep this order and tickets in your profile, or write down the order number and phone number for lookup later. / เข้าสู่ระบบตอนนี้เพื่อเก็บออเดอร์และตั๋วไว้ในโปรไฟล์ หรือจดเลขออเดอร์และเบอร์โทรไว้สำหรับค้นหาภายหลัง</p>
                    <div class="mt-3 flex flex-wrap gap-2">
                        @if(config('services.line.client_id') && config('services.line.client_secret'))
                            <a class="rounded-md bg-[#06c755] px-4 py-2 font-semibold text-zinc-950" href="{{ route('auth.social', ['provider' => 'line', 'redirect' => route('orders.show', ['order' => $order, 'phone' => $order->customer_phone]), 'claim_order' => $order->id, 'phone' => $order->customer_phone]) }}">Login with LINE and save / เข้าสู่ระบบ LINE เพื่อบันทึก</a>
                        @endif
                        <a class="rounded-md border border-amber-500/40 px-4 py-2 font-semibold text-amber-900 dark:text-amber-100" href="{{ route('login', ['redirect' => route('orders.show', ['order' => $order, 'phone' => $order->customer_phone]), 'claim_order' => $order->id, 'phone' => $order->customer_phone]) }}">Login / เข้าสู่ระบบ</a>
                    </div>
                </div>
            @else
                @if(auth()->id() === $order->user_id)
                    <div class="mt-4 rounded-md border border-zinc-200 dark:border-white/10 bg-zinc-50 dark:bg-zinc-900 p-4 text-sm text-zinc-700 dark:text-zinc-300">
                        This order is saved to your profile. / ออเดอร์นี้ถูกบันทึกไว้ในโปรไฟล์ของคุณแล้ว
                    </div>
                @endif
            @endguest
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
                        <div class="text-sm text-zinc-600 dark:text-zinc-400">{{ $ticket->ticketType->name }} · {{ $ticket->holder_name }} · {{ str_replace('_', ' ', $ticket->status) }}</div>
                    </a>
                @endforeach
            </div>
        </section>
    </div>
</x-layouts.app>
