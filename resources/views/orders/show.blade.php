<x-layouts.app :title="$order->order_number">
    <div class="grid gap-6 lg:grid-cols-[1fr_.75fr]">
        <section class="rounded-lg border border-zinc-200 dark:border-white/10 bg-white dark:bg-white/[0.04] p-6">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <p class="text-sm text-zinc-600 dark:text-zinc-400"><x-t en="Order" th="ออเดอร์" /></p>
                    <h1 class="text-3xl font-semibold text-zinc-950 dark:text-white">{{ $order->order_number }}</h1>
                </div>
                <span class="rounded bg-zinc-100 dark:bg-white/10 px-3 py-1 text-sm text-emerald-700 dark:text-emerald-200">{{ str_replace('_', ' ', $order->status) }}</span>
            </div>
            <div class="mt-5 rounded-md border border-emerald-400/30 bg-emerald-400/10 p-4 text-sm text-emerald-950 dark:text-emerald-50">
                <div class="font-semibold"><x-t en="Order received" th="ได้รับคำสั่งซื้อแล้ว" /></div>
                <p class="mt-1">
                    <x-t en="Please keep this order number for future lookup:" th="กรุณาเก็บเลขออเดอร์นี้ไว้สำหรับค้นหาภายหลัง:" />
                    <span class="font-mono font-semibold border p-1 rounded-sm">{{ $order->order_number }}</span>.
                    <x-t en="Admin approval will activate the tickets after payment review." th="แอดมินจะอนุมัติตั๋วหลังตรวจสอบการชำระเงิน" />
                </p>
            </div>
            @guest
                <div class="mt-4 rounded-md border border-amber-400/30 bg-amber-400/10 p-4 text-sm text-amber-900 dark:text-amber-100">
                    <div class="font-semibold"><x-t en="Save this order to your account" th="บันทึกออเดอร์นี้ไว้ในบัญชี" /></div>
                    <p class="mt-1"><x-t en="Login now to keep this order and tickets in your profile, or write down the order number and phone number for lookup later." th="เข้าสู่ระบบตอนนี้เพื่อเก็บออเดอร์และตั๋วไว้ในโปรไฟล์ หรือจดเลขออเดอร์และเบอร์โทรไว้สำหรับค้นหาภายหลัง" /></p>
                    <div class="mt-3 flex flex-wrap gap-2">
                        @if(config('services.line.client_id') && config('services.line.client_secret'))
                            <a class="inline-flex items-center gap-2 rounded-md bg-[#06c755] px-4 py-2 font-semibold text-zinc-950" href="{{ route('auth.social', ['provider' => 'line', 'redirect' => route('orders.show', ['order' => $order, 'phone' => $order->customer_phone]), 'claim_order' => $order->id, 'phone' => $order->customer_phone]) }}"><x-icon name="log-in" /><x-t en="Login with LINE and save" th="เข้าสู่ระบบ LINE เพื่อบันทึก" /></a>
                        @endif
                        <a class="inline-flex items-center gap-2 rounded-md border border-amber-500/40 px-4 py-2 font-semibold text-amber-900 dark:text-amber-100" href="{{ route('login', ['redirect' => route('orders.show', ['order' => $order, 'phone' => $order->customer_phone]), 'claim_order' => $order->id, 'phone' => $order->customer_phone]) }}"><x-icon name="log-in" /><x-t en="Login" th="เข้าสู่ระบบ" /></a>
                    </div>
                </div>
            @else
                @if(auth()->id() === $order->user_id)
                    <div class="mt-4 rounded-md border border-zinc-200 dark:border-white/10 bg-zinc-50 dark:bg-zinc-900 p-4 text-sm text-zinc-700 dark:text-zinc-300">
                        <x-t en="This order is saved to your profile." th="ออเดอร์นี้ถูกบันทึกไว้ในโปรไฟล์ของคุณแล้ว" />
                    </div>
                @endif
            @endguest
            <div class="mt-5 flex flex-wrap items-center gap-4 rounded-md border border-zinc-200 bg-zinc-50 p-4 text-sm dark:border-white/10 dark:bg-zinc-900">
                @if($order->user?->avatar)
                    <img class="h-14 w-14 rounded-full object-cover" src="{{ $order->user->avatar }}" alt="{{ $order->user->name }}">
                @else
                    <div class="grid h-14 w-14 place-items-center rounded-full bg-zinc-200 font-semibold text-zinc-700 dark:bg-white/10 dark:text-zinc-200">{{ strtoupper(substr($order->customer_name, 0, 1)) }}</div>
                @endif
                <div>
                    <div class="font-semibold text-zinc-950 dark:text-white">{{ $order->customer_name }}</div>
                    <div class="mt-1 flex flex-wrap gap-x-3 gap-y-1 text-zinc-600 dark:text-zinc-400">
                        <span>{{ $order->customer_phone }}</span>
                        <span>
                            @if($order->customer_email)
                                {{ $order->customer_email }}
                            @else
                                <x-t en="No email" th="ไม่มีอีเมล" />
                            @endif
                        </span>
                        @if($order->user?->provider)
                            <span>{{ strtoupper($order->user->provider) }}</span>
                        @endif
                    </div>
                </div>
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
                <div class="flex justify-between"><dt class="text-zinc-600 dark:text-zinc-400"><x-t en="Subtotal" th="ยอดรวม" /></dt><dd>THB {{ number_format($order->subtotal_thb) }}</dd></div>
                <div class="flex justify-between"><dt class="text-zinc-600 dark:text-zinc-400"><x-t en="Discount" th="ส่วนลด" /></dt><dd>THB {{ number_format($order->discount_thb) }}</dd></div>
                <div class="flex justify-between text-lg font-semibold text-zinc-950 dark:text-white"><dt><x-t en="Total" th="ยอดสุทธิ" /></dt><dd>THB {{ number_format($order->total_thb) }}</dd></div>
            </dl>
        </section>
        <section class="rounded-lg border border-zinc-200 dark:border-white/10 bg-white dark:bg-white/[0.04] p-6">
            <h2 class="inline-flex items-center gap-2 text-xl font-semibold text-zinc-950 dark:text-white"><x-icon name="ticket" class="h-5 w-5 text-emerald-500" /><x-t en="Tickets" th="ตั๋ว" /></h2>
            <div class="mt-4 grid gap-3">
                @foreach($order->tickets as $ticket)
                    <div class="interactive-card group rounded-md border border-zinc-200 p-4 dark:border-white/10" x-data="{ editHolder: false }">
                        <a class="click-area-link" href="{{ route('tickets.show', ['uuid' => $ticket->uuid, 'phone' => $ticket->holder_phone]) }}" aria-label="Open ticket for {{ $ticket->holder_name }}"></a>
                        <div class="click-area-content">
                            <div class="font-medium text-zinc-950 group-hover:text-emerald-700 dark:text-white">{{ $ticket->event->name }}</div>
                            <div class="mt-1 inline-flex items-center gap-2 text-sm text-zinc-600 dark:text-zinc-400"><x-icon name="ticket" class="h-3.5 w-3.5" />{{ $ticket->ticketType->name }} · {{ $ticket->holder_name }} · {{ str_replace('_', ' ', $ticket->status) }}</div>
                        </div>
                        @if($order->status === 'approved')
                            <div class="click-area-content mt-3 border-t border-zinc-200 pt-3 dark:border-white/10">
                                <button class="interactive-action inline-flex items-center gap-2 rounded-md border border-zinc-200 px-3 py-2 text-sm font-semibold text-zinc-800 dark:border-white/10 dark:text-zinc-100" type="button" @click="editHolder = !editHolder">
                                    <x-icon name="edit" />
                                    <span x-text="TicketFlowLanguage.format(editHolder ? { en: 'Cancel', th: 'ยกเลิก' } : { en: 'Edit holder name', th: 'แก้ไขชื่อผู้ถือบัตร' })"></span>
                                </button>
                                <form class="mt-3 grid gap-3 sm:grid-cols-[1fr_auto]" method="POST" action="{{ route('orders.tickets.holder', ['order' => $order, 'ticket' => $ticket, 'phone' => request('phone')]) }}" x-cloak x-show="editHolder" x-transition>
                                    @csrf
                                    @method('PATCH')
                                    <label class="text-sm text-zinc-700 dark:text-zinc-300"><x-t en="Holder name" th="ชื่อผู้ถือบัตร" />
                                        <input class="mt-1 w-full rounded-md border border-zinc-200 bg-white px-3 py-2 text-zinc-950 dark:border-white/10 dark:bg-zinc-950 dark:text-white" name="holder_name" value="{{ old('holder_name', $ticket->holder_name) }}" required>
                                    </label>
                                    <button class="self-end inline-flex items-center justify-center gap-2 rounded-md bg-emerald-400 px-3 py-2 text-sm font-semibold text-zinc-950"><x-icon name="save" /><x-t en="Save" th="บันทึก" /></button>
                                </form>
                            </div>
                        @else
                            <div class="click-area-content mt-3 rounded-md border border-amber-300 bg-amber-50 px-3 py-2 text-sm text-amber-900 dark:border-amber-400/40 dark:bg-amber-400/10 dark:text-amber-100">
                                <x-t en="Holder name can be edited after payment approval." th="แก้ไขชื่อผู้ถือบัตรได้หลังอนุมัติการชำระเงิน" />
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        </section>
    </div>
</x-layouts.app>
