@php
    $lineNotificationsEnabled = filled(config('services.line.messaging_channel_access_token')) && filled(config('services.line.messaging_channel_secret'));
    $webPushEnabled = filled(config('webpush.vapid.public_key')) && filled(config('webpush.vapid.private_key'));
    $notificationsEnabled = $lineNotificationsEnabled || $webPushEnabled;
@endphp
<x-layouts.app :title="$event->name.' overview'">
    <div x-data="{ tab: (new URLSearchParams(window.location.search).get('tab') === 'notifications' && ! @js($notificationsEnabled)) ? 'operations' : (new URLSearchParams(window.location.search).get('tab') || 'operations'), orderView: new URLSearchParams(window.location.search).has('ticket_status') ? 'tickets' : 'orders' }">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <p class="text-sm font-medium uppercase tracking-[0.18em] text-emerald-600 dark:text-emerald-300"><x-t en="Event operations" th="ภาพรวมการจัดงาน" /></p>
            <h1 class="mt-2 text-3xl font-semibold text-zinc-950 dark:text-white">{{ $event->name }}</h1>
            <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">{{ $event->starts_at->format('M j, Y H:i') }} · {{ $event->venue }}</p>
        </div>
        <a class="inline-flex items-center gap-2 rounded-md border border-zinc-200 dark:border-white/10 px-3 py-2 text-sm text-zinc-800 dark:text-zinc-100" href="{{ route('admin.events.edit', $event) }}"><x-icon name="edit" /><x-t en="Edit event" th="แก้ไขอีเวนต์" /></a>
    </div>

    <div class="mt-6 flex flex-wrap gap-2 rounded-lg border border-zinc-200 bg-white p-2 dark:border-white/10 dark:bg-white/[0.04]">
        <button class="inline-flex items-center gap-2 rounded-md px-3 py-2 text-sm font-semibold" type="button" @click="tab = 'operations'" :class="tab === 'operations' ? 'bg-emerald-400 text-zinc-950' : 'text-zinc-700 hover:bg-zinc-100 dark:text-zinc-200 dark:hover:bg-white/10'"><x-icon name="layout-dashboard" /><x-t en="Event operations" th="งานอีเวนต์" /></button>
        @if($notificationsEnabled)
        <button class="inline-flex items-center gap-2 rounded-md px-3 py-2 text-sm font-semibold" type="button" @click="tab = 'notifications'" :class="tab === 'notifications' ? 'bg-emerald-400 text-zinc-950' : 'text-zinc-700 hover:bg-zinc-100 dark:text-zinc-200 dark:hover:bg-white/10'"><x-icon name="bell" /><x-t en="Attendee notifications" th="แจ้งเตือนผู้เข้าร่วม" /></button>
        @endif
        <button class="inline-flex items-center gap-2 rounded-md px-3 py-2 text-sm font-semibold" type="button" @click="tab = 'orders'" :class="tab === 'orders' ? 'bg-emerald-400 text-zinc-950' : 'text-zinc-700 hover:bg-zinc-100 dark:text-zinc-200 dark:hover:bg-white/10'"><x-icon name="receipt" /><x-t en="Orders for this event" th="ออเดอร์ของอีเวนต์" /></button>
    </div>

    <div class="mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="rounded-lg border border-zinc-200 dark:border-white/10 bg-white dark:bg-white/[0.04] p-5">
            <div class="text-sm text-zinc-600 dark:text-zinc-400"><x-t en="Gross event revenue" th="รายได้รวม" /></div>
            <div class="mt-2 text-3xl font-semibold text-zinc-950 dark:text-white">THB {{ number_format($grossRevenue) }}</div>
        </div>
        <div class="rounded-lg border border-zinc-200 dark:border-white/10 bg-white dark:bg-white/[0.04] p-5">
            <div class="text-sm text-zinc-600 dark:text-zinc-400"><x-t en="Orders" th="ออเดอร์" /></div>
            <div class="mt-2 text-3xl font-semibold text-zinc-950 dark:text-white">{{ number_format($orderStatusCounts->sum()) }}</div>
        </div>
        <div class="rounded-lg border border-zinc-200 dark:border-white/10 bg-white dark:bg-white/[0.04] p-5">
            <div class="text-sm text-zinc-600 dark:text-zinc-400"><x-t en="Tickets issued" th="ตั๋วที่ออกแล้ว" /></div>
            <div class="mt-2 text-3xl font-semibold text-zinc-950 dark:text-white">{{ number_format($totalTickets) }}</div>
        </div>
        <div class="rounded-lg border border-zinc-200 dark:border-white/10 bg-white dark:bg-white/[0.04] p-5">
            <div class="text-sm text-zinc-600 dark:text-zinc-400"><x-t en="Checked in/out" th="เช็กอินหรือเช็กเอาต์" /></div>
            <div class="mt-2 text-3xl font-semibold text-zinc-950 dark:text-white">{{ number_format($checkedInTickets) }}</div>
        </div>
    </div>

    <div class="mt-6 grid gap-6 xl:grid-cols-[.8fr_1.2fr]" x-show="tab === 'operations'" x-cloak>
        <section class="rounded-lg border border-zinc-200 dark:border-white/10 bg-white dark:bg-white/[0.04] p-5">
            <h2 class="text-xl font-semibold text-zinc-950 dark:text-white"><x-t en="Ticket type sales" th="ยอดขายตามประเภทตั๋ว" /></h2>
            <div class="mt-4 grid gap-3">
                @foreach($ticketTypeStats as $stat)
                    <div class="rounded-md border border-zinc-200 dark:border-white/10 bg-zinc-50 dark:bg-zinc-900 p-4">
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <div class="font-medium text-zinc-950 dark:text-white">{{ $stat['name'] }}</div>
                                <div class="text-sm text-zinc-600 dark:text-zinc-400">{{ number_format($stat['quantity']) }} <x-t en="sold" th="ขายแล้ว" /> @if($stat['capacity']) / {{ number_format($stat['capacity']) }} <x-t en="capacity" th="ความจุ" /> @endif</div>
                            </div>
                            <div class="text-right text-sm text-emerald-700 dark:text-emerald-200">THB {{ number_format($stat['revenue']) }}</div>
                        </div>
                    </div>
                @endforeach
            </div>
        </section>

        <section class="grid gap-4 sm:grid-cols-2">
            <div class="rounded-lg border border-zinc-200 dark:border-white/10 bg-white dark:bg-white/[0.04] p-5">
                <h2 class="text-xl font-semibold text-zinc-950 dark:text-white"><x-t en="Order status" th="สถานะออเดอร์" /></h2>
                <div class="mt-4 grid gap-2 text-sm">
                    @foreach(['pending', 'approved', 'rejected', 'cancelled', 'refunded'] as $status)
                        <div class="flex items-center justify-between rounded bg-zinc-50 dark:bg-zinc-900 px-3 py-2"><x-status-badge :status="$status" /><span class="font-semibold text-zinc-950 dark:text-white">{{ $orderStatusCounts[$status] ?? 0 }}</span></div>
                    @endforeach
                </div>
            </div>
            <div class="rounded-lg border border-zinc-200 dark:border-white/10 bg-white dark:bg-white/[0.04] p-5">
                <h2 class="text-xl font-semibold text-zinc-950 dark:text-white"><x-t en="Ticket/check-in status" th="สถานะตั๋วและเช็กอิน" /></h2>
                <div class="mt-4 grid gap-2 text-sm">
                    @foreach(['pending', 'approved', 'checked_in', 'checked_out', 'expired', 'rejected', 'cancelled', 'refunded'] as $status)
                        <div class="flex items-center justify-between rounded bg-zinc-50 dark:bg-zinc-900 px-3 py-2"><x-status-badge :status="$status" type="ticket" /><span class="font-semibold text-zinc-950 dark:text-white">{{ $ticketStatusCounts[$status] ?? 0 }}</span></div>
                    @endforeach
                </div>
            </div>
        </section>
    </div>

    @if($notificationsEnabled)
    <section class="mt-6 rounded-lg border border-zinc-200 dark:border-white/10 bg-white dark:bg-white/[0.04] p-5" x-show="tab === 'notifications'" x-cloak>
    @if($messageRecipientCount > 0)
        <div class="grid gap-4 lg:grid-cols-[.7fr_1.3fr]">
            <div>
                <h2 class="text-xl font-semibold text-zinc-950 dark:text-white"><x-t en="Attendee notifications" th="ส่งการแจ้งเตือนถึงผู้เข้าร่วม" /></h2>
                <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400"><x-t en="Send ticket updates or event reminders to logged-in attendees. Approved audience currently has" th="ส่งอัปเดตตั๋วหรือแจ้งเตือนอีเวนต์ถึงผู้เข้าร่วมที่ล็อกอิน กลุ่มที่อนุมัติแล้วมี" /> {{ number_format($messageRecipientCount) }} <x-t en="recipients." th="คน" /></p>
            </div>
            <form method="POST" action="{{ route('admin.events.message-attendees', $event) }}" class="grid gap-3" onsubmit="return confirm(TicketFlowLanguage.format({ en: 'Send this notification to attendees?', th: 'ส่งการแจ้งเตือนนี้ถึงผู้เข้าร่วม?' }))">
                @csrf
                <div class="grid gap-3 sm:grid-cols-[1fr_180px]">
                    <label class="text-sm font-medium text-zinc-700 dark:text-zinc-200"><x-t en="Subject" th="หัวข้อ" /> <span class="rounded bg-rose-400/20 px-1.5 py-0.5 text-xs text-rose-700 dark:text-rose-200"><x-t en="required" th="จำเป็น" /></span>
                        <input class="mt-1 w-full rounded-md border border-emerald-400/40 bg-white dark:bg-zinc-950 px-3 py-2 text-zinc-950 dark:text-white focus:border-emerald-300 focus:outline-none" name="subject" value="{{ old('subject') }}" required>
                    </label>
                    <label class="text-sm font-medium text-zinc-700 dark:text-zinc-200"><x-t en="Audience" th="กลุ่มผู้รับ" />
                        <select class="mt-1 w-full rounded-md border border-zinc-200 dark:border-white/10 bg-white dark:bg-zinc-950 px-3 py-2 text-zinc-950 dark:text-white" name="audience">
                            <option value="approved" @selected(old('audience', 'approved') === 'approved') data-i18n-en="Approved only" data-i18n-th="อนุมัติแล้ว">Approved only</option>
                            <option value="all" @selected(old('audience') === 'all') data-i18n-en="All logged-in buyers" data-i18n-th="ผู้ซื้อที่ล็อกอินทั้งหมด">All logged-in buyers</option>
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
                <label class="text-sm font-medium text-zinc-700 dark:text-zinc-200"><x-t en="Message" th="ข้อความ" /> <span class="rounded bg-rose-400/20 px-1.5 py-0.5 text-xs text-rose-700 dark:text-rose-200"><x-t en="required" th="จำเป็น" /></span>
                    <textarea class="mt-1 w-full rounded-md border border-emerald-400/40 bg-white dark:bg-zinc-950 px-3 py-2 text-zinc-950 dark:text-white focus:border-emerald-300 focus:outline-none" name="message" rows="5" required>{{ old('message') }}</textarea>
                </label>
                <button class="inline-flex items-center gap-2 justify-self-start rounded-md bg-emerald-400 px-4 py-2 font-semibold text-zinc-950 hover:bg-emerald-300"><x-icon name="send" /><x-t en="Send notification" th="ส่งการแจ้งเตือน" /></button>
            </form>
        </div>
    @else
        <h2 class="text-xl font-semibold text-zinc-950 dark:text-white"><x-t en="Attendee notifications" th="ส่งการแจ้งเตือนถึงผู้เข้าร่วม" /></h2>
        <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400"><x-t en="This event has no logged-in attendees ready to receive messages yet." th="ยังไม่มีผู้เข้าร่วมที่ล็อกอินสำหรับรับข้อความ" /></p>
    @endif
    </section>
    @endif

    <div class="mt-6" x-show="tab === 'orders'" x-cloak>
    <div class="mb-3 flex flex-wrap gap-2 rounded-lg border border-zinc-200 bg-white p-2 dark:border-white/10 dark:bg-white/[0.04]">
        <button class="inline-flex items-center gap-2 rounded-md px-3 py-2 text-sm font-semibold" type="button" @click="orderView = 'orders'" :class="orderView === 'orders' ? 'bg-emerald-400 text-zinc-950' : 'text-zinc-700 hover:bg-zinc-100 dark:text-zinc-200 dark:hover:bg-white/10'"><x-icon name="receipt" /><x-t en="Orders" th="ออเดอร์" /></button>
        <button class="inline-flex items-center gap-2 rounded-md px-3 py-2 text-sm font-semibold" type="button" @click="orderView = 'tickets'" :class="orderView === 'tickets' ? 'bg-emerald-400 text-zinc-950' : 'text-zinc-700 hover:bg-zinc-100 dark:text-zinc-200 dark:hover:bg-white/10'"><x-icon name="ticket" /><x-t en="Tickets" th="ตั๋ว" /></button>
    </div>
    <section class="rounded-lg border border-zinc-200 dark:border-white/10 bg-white dark:bg-white/[0.04]" x-show="orderView === 'orders'">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-zinc-200 dark:border-white/10 p-4">
            <h2 class="text-xl font-semibold text-zinc-950 dark:text-white"><x-t en="Orders for this event" th="ออเดอร์ของอีเวนต์นี้" /></h2>
            <form class="flex gap-2">
                <input type="hidden" name="tab" value="orders">
                <select class="rounded-md border border-zinc-200 dark:border-white/10 bg-white dark:bg-zinc-950 px-3 py-2 text-sm text-zinc-950 dark:text-white" name="order_status">
                    <option value="" data-i18n-en="All orders" data-i18n-th="ทุกออเดอร์">All orders</option>
                    @foreach(['pending', 'approved', 'rejected', 'cancelled', 'refunded'] as $status)
                        <option value="{{ $status }}" @selected(request('order_status') === $status)>{{ str_replace('_', ' ', $status) }}</option>
                    @endforeach
                </select>
                <button class="inline-flex items-center gap-2 rounded-md border border-zinc-200 dark:border-white/10 px-3 py-2 text-sm text-zinc-800 dark:text-zinc-100"><x-icon name="search" /><x-t en="Filter" th="กรอง" /></button>
            </form>
        </div>
        <div class="divide-y divide-white/10">
            @forelse($orders as $order)
                <div class="interactive-row group grid gap-4 p-4 lg:grid-cols-[1fr_auto]">
                    <a class="click-area-link" href="{{ route('admin.orders.show', $order) }}" aria-label="Open order {{ $order->order_number }}"></a>
                    <div class="click-area-content">
                        <div class="font-semibold text-zinc-950 group-hover:text-emerald-700 dark:text-white">{{ $order->order_number }}</div>
                        <div class="mt-1 flex flex-wrap items-center gap-2 text-sm text-zinc-600 dark:text-zinc-400">{{ $order->customer_name }} · {{ $order->customer_phone }} <x-status-badge :status="$order->status" /></div>
                        <div class="mt-2 flex flex-wrap gap-2 text-xs text-zinc-700 dark:text-zinc-300">
                            @foreach($order->items as $item)
                                <span class="rounded bg-zinc-100 dark:bg-white/10 px-2 py-1">{{ $item->ticketType->name }} x {{ $item->quantity }}</span>
                            @endforeach
                        </div>
                    </div>
                    <div class="click-area-content flex flex-wrap items-start gap-2" x-data="{ editStatus: false }">
                        @if($order->status === 'pending')
                            <form method="POST" action="{{ route('admin.orders.approve', $order) }}" onsubmit="return confirm(TicketFlowLanguage.format({ en: 'Approve this order?', th: 'ยืนยันอนุมัติออเดอร์นี้?' }))">@csrf<button class="inline-flex items-center gap-2 rounded-md bg-emerald-400 px-3 py-2 text-sm font-semibold text-zinc-950"><x-icon name="check" /><x-t en="Approve" th="อนุมัติ" /></button></form>
                            <form method="POST" action="{{ route('admin.orders.reject', $order) }}" onsubmit="return confirm(TicketFlowLanguage.format({ en: 'Reject this order?', th: 'ยืนยันปฏิเสธออเดอร์นี้?' }))">@csrf<button class="inline-flex items-center gap-2 rounded-md bg-rose-400 px-3 py-2 text-sm font-semibold text-zinc-950"><x-icon name="x" /><x-t en="Reject" th="ปฏิเสธ" /></button></form>
                        @elseif($order->status === 'approved')
                            <button class="inline-flex items-center gap-2 rounded-md border border-zinc-200 px-3 py-2 text-sm font-semibold text-zinc-800 hover:border-emerald-300 dark:border-white/10 dark:text-zinc-100" type="button" @click="editStatus = !editStatus"><x-icon name="pencil" /><span x-text="TicketFlowLanguage.format(editStatus ? { en: 'Hide', th: 'ซ่อน' } : { en: 'Edit status', th: 'แก้ไขสถานะ' })"></span></button>
                            <div class="flex flex-wrap gap-2" x-cloak x-show="editStatus" x-transition>
                                <form method="POST" action="{{ route('admin.orders.cancel', $order) }}" onsubmit="return confirm(TicketFlowLanguage.format({ en: 'Cancel this approved order?', th: 'ยืนยันยกเลิกออเดอร์ที่อนุมัติแล้ว?' }))">@csrf<button class="inline-flex items-center gap-2 rounded-md border border-amber-300 px-3 py-2 text-sm font-semibold text-amber-700 dark:border-amber-400/40 dark:text-amber-100"><x-icon name="ban" /><x-t en="Cancel" th="ยกเลิก" /></button></form>
                                <form method="POST" action="{{ route('admin.orders.refund', $order) }}" onsubmit="return confirm(TicketFlowLanguage.format({ en: 'Refund this approved order?', th: 'ยืนยันคืนเงินออเดอร์นี้?' }))">@csrf<button class="inline-flex items-center gap-2 rounded-md border border-zinc-200 px-3 py-2 text-sm text-zinc-800 dark:border-white/10 dark:text-zinc-100"><x-icon name="undo" /><x-t en="Refund" th="คืนเงิน" /></button></form>
                            </div>
                        @elseif(in_array($order->status, ['cancelled', 'refunded'], true))
                            <form method="POST" action="{{ route('admin.orders.destroy', $order) }}" onsubmit="return confirm(TicketFlowLanguage.format({ en: 'Delete this order and its tickets?', th: 'ลบออเดอร์และตั๋วทั้งหมด?' }))">@csrf @method('DELETE')<button class="inline-flex items-center gap-2 rounded-md border border-rose-300 px-3 py-2 text-sm font-semibold text-rose-700 dark:border-rose-400/40 dark:text-rose-200"><x-icon name="trash-2" /><x-t en="Delete" th="ลบ" /></button></form>
                        @endif
                    </div>
                </div>
            @empty
                <div class="p-6 text-zinc-600 dark:text-zinc-400"><x-t en="No orders found for this event." th="ไม่พบออเดอร์ของอีเวนต์นี้" /></div>
            @endforelse
        </div>
        <div class="p-4">{{ $orders->withQueryString()->links() }}</div>
    </section>

    <section class="mt-6 rounded-lg border border-zinc-200 dark:border-white/10 bg-white dark:bg-white/[0.04]" x-show="orderView === 'tickets'">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-zinc-200 dark:border-white/10 p-4">
            <h2 class="text-xl font-semibold text-zinc-950 dark:text-white"><x-t en="Tickets and check-in" th="ตั๋วและการเช็กอิน" /></h2>
            <form class="flex gap-2">
                <input type="hidden" name="tab" value="orders">
                <select class="rounded-md border border-zinc-200 dark:border-white/10 bg-white dark:bg-zinc-950 px-3 py-2 text-sm text-zinc-950 dark:text-white" name="ticket_status">
                    <option value="" data-i18n-en="All tickets" data-i18n-th="ทุกตั๋ว">All tickets</option>
                    @foreach(['pending', 'approved', 'checked_in', 'checked_out', 'expired', 'rejected', 'cancelled', 'refunded'] as $status)
                        <option value="{{ $status }}" @selected(request('ticket_status') === $status)>{{ str_replace('_', ' ', $status) }}</option>
                    @endforeach
                </select>
                <button class="inline-flex items-center gap-2 rounded-md border border-zinc-200 dark:border-white/10 px-3 py-2 text-sm text-zinc-800 dark:text-zinc-100"><x-icon name="search" /><x-t en="Filter" th="กรอง" /></button>
            </form>
        </div>
        <div class="divide-y divide-white/10">
            @forelse($tickets as $ticket)
                <div class="interactive-row group grid gap-4 p-4 lg:grid-cols-[1fr_auto]">
                    <a class="click-area-link" href="{{ route('tickets.show', $ticket->uuid) }}" aria-label="Open ticket for {{ $ticket->holder_name }}"></a>
                    <div class="click-area-content">
                        <div class="font-semibold text-zinc-950 group-hover:text-emerald-700 dark:text-white">{{ $ticket->holder_name }}</div>
                        <div class="mt-1 flex flex-wrap items-center gap-2 text-sm text-zinc-600 dark:text-zinc-400">
                            <span>{{ $ticket->ticketType->name }}</span>
                            <a class="font-medium text-emerald-700 hover:text-emerald-800 dark:text-emerald-200" href="{{ route('admin.orders.show', $ticket->order) }}">{{ $ticket->order->order_number }}</a>
                            <x-status-badge :status="$ticket->status" type="ticket" />
                        </div>
                        <div class="mt-1 text-xs text-zinc-500"><x-t en="In" th="เข้า" />: {{ $ticket->checked_in_at?->format('M j H:i') ?? '-' }} · <x-t en="Out" th="ออก" />: {{ $ticket->checked_out_at?->format('M j H:i') ?? '-' }}</div>
                    </div>
                    <div class="click-area-content flex flex-wrap items-start gap-2">
                        <form method="POST" action="{{ route('admin.events.tickets.status', [$event, $ticket]) }}" class="flex flex-wrap items-start gap-2">
                            @csrf
                            @method('PATCH')
                            <select class="rounded-md border border-zinc-200 dark:border-white/10 bg-white dark:bg-zinc-950 px-3 py-2 text-sm text-zinc-950 dark:text-white" name="status">
                                @foreach(['pending', 'approved', 'checked_in', 'checked_out', 'expired', 'rejected', 'cancelled', 'refunded'] as $status)
                                    <option value="{{ $status }}" @selected($ticket->status === $status)>{{ str_replace('_', ' ', $status) }}</option>
                                @endforeach
                            </select>
                            <button class="inline-flex items-center gap-2 rounded-md bg-emerald-400 px-3 py-2 text-sm font-semibold text-zinc-950"><x-icon name="save" /><x-t en="Update" th="อัปเดต" /></button>
                        </form>
                        @if(in_array($ticket->order->status, ['cancelled', 'refunded'], true))
                            <form method="POST" action="{{ route('admin.events.tickets.destroy', [$event, $ticket]) }}" onsubmit="return confirm(TicketFlowLanguage.format({ en: 'Delete this ticket?', th: 'ลบตั๋วนี้?' }))">
                                @csrf
                                @method('DELETE')
                                <button class="inline-flex items-center gap-2 rounded-md border border-rose-300 px-3 py-2 text-sm font-semibold text-rose-700 dark:border-rose-400/40 dark:text-rose-200"><x-icon name="trash-2" /><x-t en="Delete" th="ลบ" /></button>
                            </form>
                        @endif
                    </div>
                </div>
            @empty
                <div class="p-6 text-zinc-600 dark:text-zinc-400"><x-t en="No tickets found for this event." th="ไม่พบตั๋วของอีเวนต์นี้" /></div>
            @endforelse
        </div>
        <div class="p-4">{{ $tickets->withQueryString()->links() }}</div>
    </section>
    </div>
    </div>
</x-layouts.app>
