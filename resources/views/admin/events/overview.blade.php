@php
    $lineNotificationsEnabled = filled(config('services.line.messaging_channel_access_token')) && filled(config('services.line.messaging_channel_secret'));
    $webPushEnabled = filled(config('webpush.vapid.public_key')) && filled(config('webpush.vapid.private_key'));
@endphp
<x-layouts.app :title="$event->name.' overview'">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <p class="text-sm font-medium uppercase tracking-[0.18em] text-emerald-600 dark:text-emerald-300">Event operations / ภาพรวมการจัดงาน</p>
            <h1 class="mt-2 text-3xl font-semibold text-zinc-950 dark:text-white">{{ $event->name }}</h1>
            <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">{{ $event->starts_at->format('M j, Y H:i') }} · {{ $event->venue }}</p>
        </div>
        <a class="inline-flex items-center gap-2 rounded-md border border-zinc-200 dark:border-white/10 px-3 py-2 text-sm text-zinc-800 dark:text-zinc-100" href="{{ route('admin.events.edit', $event) }}"><x-icon name="edit" />Edit event / แก้ไขอีเวนต์</a>
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
                    @foreach(['pending', 'approved', 'rejected', 'cancelled', 'refunded'] as $status)
                        <div class="flex justify-between rounded bg-zinc-50 dark:bg-zinc-900 px-3 py-2"><span class="text-zinc-700 dark:text-zinc-300">{{ str_replace('_', ' ', $status) }}</span><span class="font-semibold text-zinc-950 dark:text-white">{{ $orderStatusCounts[$status] ?? 0 }}</span></div>
                    @endforeach
                </div>
            </div>
            <div class="rounded-lg border border-zinc-200 dark:border-white/10 bg-white dark:bg-white/[0.04] p-5">
                <h2 class="text-xl font-semibold text-zinc-950 dark:text-white">Ticket/check-in status / สถานะตั๋วและเช็กอิน</h2>
                <div class="mt-4 grid gap-2 text-sm">
                    @foreach(['pending', 'approved', 'checked_in', 'checked_out', 'expired', 'rejected', 'cancelled', 'refunded'] as $status)
                        <div class="flex justify-between rounded bg-zinc-50 dark:bg-zinc-900 px-3 py-2"><span class="text-zinc-700 dark:text-zinc-300">{{ str_replace('_', ' ', $status) }}</span><span class="font-semibold text-zinc-950 dark:text-white">{{ $ticketStatusCounts[$status] ?? 0 }}</span></div>
                    @endforeach
                </div>
            </div>
        </section>
    </div>

    <!-- <section class="mt-6 rounded-lg border border-zinc-200 dark:border-white/10 bg-white dark:bg-white/[0.04] p-5">
        <div class="grid gap-4 lg:grid-cols-[.7fr_1.3fr]">
            <div>
                <h2 class="text-xl font-semibold text-zinc-950 dark:text-white">Email attendees / ส่งอีเมลถึงผู้เข้าร่วม</h2>
                <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">Send a message to unique customer emails for this event. Approved audience currently has {{ number_format($emailRecipientCount) }} recipients. / ส่งข้อความถึงอีเมลลูกค้าที่ไม่ซ้ำกันของอีเวนต์นี้ กลุ่มที่อนุมัติแล้วมี {{ number_format($emailRecipientCount) }} คน</p>
            </div>
            <form method="POST" action="{{ route('admin.events.email-attendees', $event) }}" class="grid gap-3">
                @csrf
                <div class="grid gap-3 sm:grid-cols-[1fr_180px]">
                    <label class="text-sm font-medium text-zinc-700 dark:text-zinc-200">Subject / หัวข้อ <span class="rounded bg-rose-400/20 px-1.5 py-0.5 text-xs text-rose-700 dark:text-rose-200">required / จำเป็น</span>
                        <input class="mt-1 w-full rounded-md border border-emerald-400/40 bg-white dark:bg-zinc-950 px-3 py-2 text-zinc-950 dark:text-white focus:border-emerald-300 focus:outline-none" name="subject" value="{{ old('subject') }}" required>
                    </label>
                    <label class="text-sm font-medium text-zinc-700 dark:text-zinc-200">Audience / กลุ่มผู้รับ
                        <select class="mt-1 w-full rounded-md border border-zinc-200 dark:border-white/10 bg-white dark:bg-zinc-950 px-3 py-2 text-zinc-950 dark:text-white" name="audience">
                            <option value="approved" @selected(old('audience', 'approved') === 'approved')>Approved only / อนุมัติแล้ว</option>
                            <option value="all" @selected(old('audience') === 'all')>All with email / ทุกคนที่มีอีเมล</option>
                        </select>
                    </label>
                </div>
                <label class="text-sm font-medium text-zinc-700 dark:text-zinc-200">Message / ข้อความ <span class="rounded bg-rose-400/20 px-1.5 py-0.5 text-xs text-rose-700 dark:text-rose-200">required / จำเป็น</span>
                    <textarea class="mt-1 w-full rounded-md border border-emerald-400/40 bg-white dark:bg-zinc-950 px-3 py-2 text-zinc-950 dark:text-white focus:border-emerald-300 focus:outline-none" name="message" rows="5" required>{{ old('message') }}</textarea>
                </label>
                <button class="inline-flex items-center gap-2 justify-self-start rounded-md bg-emerald-400 px-4 py-2 font-semibold text-zinc-950 hover:bg-emerald-300"><x-icon name="mail" />Send email / ส่งอีเมล</button>
            </form>
        </div>
    </section> -->

    @if(($lineNotificationsEnabled || $webPushEnabled) && $messageRecipientCount > 0)
    <section class="mt-6 rounded-lg border border-zinc-200 dark:border-white/10 bg-white dark:bg-white/[0.04] p-5">
        <div class="grid gap-4 lg:grid-cols-[.7fr_1.3fr]">
            <div>
                <h2 class="text-xl font-semibold text-zinc-950 dark:text-white">Attendee notifications / ส่งการแจ้งเตือนถึงผู้เข้าร่วม</h2>
                <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">Send ticket updates or event reminders to logged-in attendees. Approved audience currently has {{ number_format($messageRecipientCount) }} recipients. / ส่งอัปเดตตั๋วหรือแจ้งเตือนอีเวนต์ถึงผู้เข้าร่วมที่ล็อกอิน กลุ่มที่อนุมัติแล้วมี {{ number_format($messageRecipientCount) }} คน</p>
            </div>
            <form method="POST" action="{{ route('admin.events.message-attendees', $event) }}" class="grid gap-3" onsubmit="return confirm('Send this notification to attendees? / ส่งการแจ้งเตือนนี้ถึงผู้เข้าร่วม?')">
                @csrf
                <div class="grid gap-3 sm:grid-cols-[1fr_180px]">
                    <label class="text-sm font-medium text-zinc-700 dark:text-zinc-200">Subject / หัวข้อ <span class="rounded bg-rose-400/20 px-1.5 py-0.5 text-xs text-rose-700 dark:text-rose-200">required / จำเป็น</span>
                        <input class="mt-1 w-full rounded-md border border-emerald-400/40 bg-white dark:bg-zinc-950 px-3 py-2 text-zinc-950 dark:text-white focus:border-emerald-300 focus:outline-none" name="subject" value="{{ old('subject') }}" required>
                    </label>
                    <label class="text-sm font-medium text-zinc-700 dark:text-zinc-200">Audience / กลุ่มผู้รับ
                        <select class="mt-1 w-full rounded-md border border-zinc-200 dark:border-white/10 bg-white dark:bg-zinc-950 px-3 py-2 text-zinc-950 dark:text-white" name="audience">
                            <option value="approved" @selected(old('audience', 'approved') === 'approved')>Approved only / อนุมัติแล้ว</option>
                            <option value="all" @selected(old('audience') === 'all')>All logged-in buyers / ผู้ซื้อที่ล็อกอินทั้งหมด</option>
                        </select>
                    </label>
                </div>
                <div class="flex flex-wrap gap-3 text-sm text-zinc-700 dark:text-zinc-300">
                    @if($lineNotificationsEnabled)
                        <label class="flex items-center gap-2"><input type="checkbox" name="channels[]" value="line" checked> LINE Messaging API</label>
                    @endif
                    @if($webPushEnabled)
                        <label class="flex items-center gap-2"><input type="checkbox" name="channels[]" value="web_push" checked> Web Push</label>
                    @endif
                </div>
                <label class="text-sm font-medium text-zinc-700 dark:text-zinc-200">Message / ข้อความ <span class="rounded bg-rose-400/20 px-1.5 py-0.5 text-xs text-rose-700 dark:text-rose-200">required / จำเป็น</span>
                    <textarea class="mt-1 w-full rounded-md border border-emerald-400/40 bg-white dark:bg-zinc-950 px-3 py-2 text-zinc-950 dark:text-white focus:border-emerald-300 focus:outline-none" name="message" rows="5" required>{{ old('message') }}</textarea>
                </label>
                <button class="inline-flex items-center gap-2 justify-self-start rounded-md bg-emerald-400 px-4 py-2 font-semibold text-zinc-950 hover:bg-emerald-300"><x-icon name="send" />Send notification / ส่งการแจ้งเตือน</button>
            </form>
        </div>
    </section>
    @endif

    <section class="mt-6 rounded-lg border border-zinc-200 dark:border-white/10 bg-white dark:bg-white/[0.04]">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-zinc-200 dark:border-white/10 p-4">
            <h2 class="text-xl font-semibold text-zinc-950 dark:text-white">Orders for this event / ออเดอร์ของอีเวนต์นี้</h2>
            <form class="flex gap-2">
                <select class="rounded-md border border-zinc-200 dark:border-white/10 bg-white dark:bg-zinc-950 px-3 py-2 text-sm text-zinc-950 dark:text-white" name="order_status">
                    <option value="">All orders / ทุกออเดอร์</option>
                    @foreach(['pending', 'approved', 'rejected', 'cancelled', 'refunded'] as $status)
                        <option value="{{ $status }}" @selected(request('order_status') === $status)>{{ str_replace('_', ' ', $status) }}</option>
                    @endforeach
                </select>
                <button class="inline-flex items-center gap-2 rounded-md border border-zinc-200 dark:border-white/10 px-3 py-2 text-sm text-zinc-800 dark:text-zinc-100"><x-icon name="search" />Filter / กรอง</button>
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
                    <div class="flex flex-wrap items-start gap-2" x-data="{ editStatus: false }">
                        @if($order->status === 'pending')
                            <form method="POST" action="{{ route('admin.orders.approve', $order) }}" onsubmit="return confirm('Approve this order? / ยืนยันอนุมัติออเดอร์นี้?')">@csrf<button class="inline-flex items-center gap-2 rounded-md bg-emerald-400 px-3 py-2 text-sm font-semibold text-zinc-950"><x-icon name="check" />Approve</button></form>
                            <form method="POST" action="{{ route('admin.orders.reject', $order) }}" onsubmit="return confirm('Reject this order? / ยืนยันปฏิเสธออเดอร์นี้?')">@csrf<button class="inline-flex items-center gap-2 rounded-md bg-rose-400 px-3 py-2 text-sm font-semibold text-zinc-950"><x-icon name="x" />Reject</button></form>
                        @elseif($order->status === 'approved')
                            <button class="inline-flex items-center gap-2 rounded-md border border-zinc-200 px-3 py-2 text-sm font-semibold text-zinc-800 hover:border-emerald-300 dark:border-white/10 dark:text-zinc-100" type="button" @click="editStatus = !editStatus"><x-icon name="pencil" /><span x-text="editStatus ? 'Hide' : 'Edit status'"></span></button>
                            <div class="flex flex-wrap gap-2" x-cloak x-show="editStatus" x-transition>
                                <form method="POST" action="{{ route('admin.orders.cancel', $order) }}" onsubmit="return confirm('Cancel this approved order? / ยืนยันยกเลิกออเดอร์ที่อนุมัติแล้ว?')">@csrf<button class="inline-flex items-center gap-2 rounded-md border border-amber-300 px-3 py-2 text-sm font-semibold text-amber-700 dark:border-amber-400/40 dark:text-amber-100"><x-icon name="ban" />Cancel</button></form>
                                <form method="POST" action="{{ route('admin.orders.refund', $order) }}" onsubmit="return confirm('Refund this approved order? / ยืนยันคืนเงินออเดอร์นี้?')">@csrf<button class="inline-flex items-center gap-2 rounded-md border border-zinc-200 px-3 py-2 text-sm text-zinc-800 dark:border-white/10 dark:text-zinc-100"><x-icon name="undo" />Refund</button></form>
                            </div>
                        @elseif(in_array($order->status, ['cancelled', 'refunded'], true))
                            <form method="POST" action="{{ route('admin.orders.destroy', $order) }}" onsubmit="return confirm('Delete this order and its tickets? / ลบออเดอร์และตั๋วทั้งหมด?')">@csrf @method('DELETE')<button class="inline-flex items-center gap-2 rounded-md border border-rose-300 px-3 py-2 text-sm font-semibold text-rose-700 dark:border-rose-400/40 dark:text-rose-200"><x-icon name="trash-2" />Delete</button></form>
                        @endif
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
                    @foreach(['pending', 'approved', 'checked_in', 'checked_out', 'expired', 'rejected', 'cancelled', 'refunded'] as $status)
                        <option value="{{ $status }}" @selected(request('ticket_status') === $status)>{{ str_replace('_', ' ', $status) }}</option>
                    @endforeach
                </select>
                <button class="inline-flex items-center gap-2 rounded-md border border-zinc-200 dark:border-white/10 px-3 py-2 text-sm text-zinc-800 dark:text-zinc-100"><x-icon name="search" />Filter / กรอง</button>
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
                    <div class="flex flex-wrap items-start gap-2">
                        <form method="POST" action="{{ route('admin.events.tickets.status', [$event, $ticket]) }}" class="flex flex-wrap items-start gap-2">
                            @csrf
                            @method('PATCH')
                            <select class="rounded-md border border-zinc-200 dark:border-white/10 bg-white dark:bg-zinc-950 px-3 py-2 text-sm text-zinc-950 dark:text-white" name="status">
                                @foreach(['pending', 'approved', 'checked_in', 'checked_out', 'expired', 'rejected', 'cancelled', 'refunded'] as $status)
                                    <option value="{{ $status }}" @selected($ticket->status === $status)>{{ str_replace('_', ' ', $status) }}</option>
                                @endforeach
                            </select>
                            <button class="inline-flex items-center gap-2 rounded-md bg-emerald-400 px-3 py-2 text-sm font-semibold text-zinc-950"><x-icon name="save" />Update</button>
                        </form>
                        @if(in_array($ticket->order->status, ['cancelled', 'refunded'], true))
                            <form method="POST" action="{{ route('admin.events.tickets.destroy', [$event, $ticket]) }}" onsubmit="return confirm('Delete this ticket? / ลบตั๋วนี้?')">
                                @csrf
                                @method('DELETE')
                                <button class="inline-flex items-center gap-2 rounded-md border border-rose-300 px-3 py-2 text-sm font-semibold text-rose-700 dark:border-rose-400/40 dark:text-rose-200"><x-icon name="trash-2" />Delete</button>
                            </form>
                        @endif
                    </div>
                </div>
            @empty
                <div class="p-6 text-zinc-600 dark:text-zinc-400">No tickets found for this event. / ไม่พบตั๋วของอีเวนต์นี้</div>
            @endforelse
        </div>
        <div class="p-4">{{ $tickets->withQueryString()->links() }}</div>
    </section>
</x-layouts.app>
