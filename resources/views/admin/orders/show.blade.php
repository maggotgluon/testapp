@php
    $payment = $order->payments->first();
    $qrAmountMatches = $payment?->slip_qr_amount_thb !== null
        ? abs((float) $payment->slip_qr_amount_thb - (float) $order->total_thb) < 0.01
        : null;
    $emvco = $payment?->slip_qr_data['emv']['emvco'] ?? null;
    $duplicate = $payment?->slip_qr_data['duplicate'] ?? null;
    $reviewFlags = $payment?->slip_review_flags ?? [];
    $reviewStatus = $payment?->slip_review_status;
    $reviewBadgeClass = match ($reviewStatus) {
        'passed' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-400/15 dark:text-emerald-100',
        'risky' => 'bg-rose-100 text-rose-800 dark:bg-rose-400/15 dark:text-rose-100',
        'needs_manual_review' => 'bg-amber-100 text-amber-800 dark:bg-amber-400/15 dark:text-amber-100',
        default => 'bg-zinc-100 text-zinc-700 dark:bg-white/10 dark:text-zinc-200',
    };
    $canApprove = $order->status === 'pending';
    $canReject = $order->status === 'pending';
    $canCancel = $order->status === 'approved';
    $canRefund = $order->status === 'approved';
    $canDelete = in_array($order->status, ['cancelled', 'refunded'], true);
@endphp

<x-layouts.app :title="$order->order_number">
    <div class="mb-5 flex flex-wrap items-center justify-between gap-2">
        <a class="inline-flex items-center gap-2 rounded-md border border-zinc-200 px-3 py-2 text-sm font-semibold text-zinc-800 hover:border-emerald-300 dark:border-white/10 dark:text-zinc-100" href="{{ route('admin.orders.index') }}"><x-icon name="undo" />Back to orders / กลับไปหน้าออเดอร์</a>
        <div class="flex flex-wrap gap-2">
            @if($previousOrder)
                <a class="inline-flex items-center gap-2 rounded-md border border-zinc-200 px-3 py-2 text-sm font-semibold text-zinc-800 hover:border-emerald-300 dark:border-white/10 dark:text-zinc-100" href="{{ route('admin.orders.show', $previousOrder) }}">Previous / ก่อนหน้า</a>
            @endif
            @if($nextOrder)
                <a class="inline-flex items-center gap-2 rounded-md border border-zinc-200 px-3 py-2 text-sm font-semibold text-zinc-800 hover:border-emerald-300 dark:border-white/10 dark:text-zinc-100" href="{{ route('admin.orders.show', $nextOrder) }}">Next / ถัดไป</a>
            @endif
        </div>
    </div>
    <div class="grid gap-6 lg:grid-cols-[1fr_.8fr]">
        <section class="rounded-lg border border-zinc-200 dark:border-white/10 bg-white dark:bg-white/[0.04] p-6">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <p class="text-sm text-zinc-600 dark:text-zinc-400">Order / ออเดอร์</p>
                    <h1 class="text-3xl font-semibold text-zinc-950 dark:text-white">{{ $order->order_number }}</h1>
                </div>
                <x-status-badge :status="$order->status" />
            </div>
            <div class="mt-5 grid gap-3">
                @foreach($order->items->groupBy('event_id') as $eventItems)
                    @php $event = $eventItems->first()->event; @endphp
                    @if($event)
                        <a class="rounded-md border border-emerald-400/20 bg-emerald-400/10 p-4 hover:border-emerald-400/50" href="{{ route('admin.events.overview', $event) }}">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <div>
                                    <div class="font-semibold text-zinc-950 dark:text-white">{{ $event->name }}</div>
                                    <div class="text-sm text-zinc-600 dark:text-zinc-400">{{ $event->starts_at->format('M j, Y H:i') }} · {{ $event->venue }}</div>
                                </div>
                                <span class="text-sm font-semibold text-emerald-700 dark:text-emerald-200">Event overview / ภาพรวม</span>
                            </div>
                            <div class="mt-2 flex flex-wrap gap-2 text-xs text-zinc-700 dark:text-zinc-300">
                                @foreach($eventItems as $item)
                                    <span class="rounded bg-white/80 px-2 py-1 dark:bg-zinc-950/60">{{ $item->ticketType?->name }} x {{ $item->quantity }}</span>
                                @endforeach
                            </div>
                        </a>
                    @endif
                @endforeach
            </div>
            <dl class="mt-5 grid gap-2 text-sm text-zinc-700 dark:text-zinc-300">
                <div><dt class="text-zinc-500">Customer / ลูกค้า</dt><dd>{{ $order->customer_name }} · {{ $order->customer_phone }}</dd></div>
                <div><dt class="text-zinc-500">Email / อีเมล</dt><dd>{{ $order->customer_email ?: 'No email / ไม่มีอีเมล' }}</dd></div>
                <div><dt class="text-zinc-500">Account / บัญชี</dt><dd>{{ $order->user?->name ?: 'Guest checkout / ซื้อโดยไม่ล็อกอิน' }} @if($order->user?->provider) · {{ strtoupper($order->user->provider) }} @endif</dd></div>
                <div><dt class="text-zinc-500">Payment / การชำระเงิน</dt><dd>{{ str_replace('_', ' ', $order->payment_method) }} · THB {{ number_format($order->total_thb) }}</dd></div>
                @if($payment)
                    <div><dt class="text-zinc-500">Expected account / บัญชีที่ควรได้รับเงิน</dt><dd>{{ $payment->payment_account_label ?: $payment->payment_account_name ?: 'Not stored / ไม่ได้บันทึก' }} @if($payment->payment_account_number) · <span class="font-mono">{{ $payment->payment_account_number }}</span>@endif</dd></div>
                    <div><dt class="text-zinc-500">Expected amount / ยอดที่ควรได้รับ</dt><dd>THB {{ number_format((float) ($payment->expected_amount_thb ?? $order->total_thb), 2) }}</dd></div>
                @endif
                <div><dt class="text-zinc-500">Note / หมายเหตุ</dt><dd>{{ $order->payment_note ?: 'No note / ไม่มีหมายเหตุ' }}</dd></div>
            </dl>
            @if($order->user?->avatar)
                <img class="mt-4 h-16 w-16 rounded-full object-cover" src="{{ $order->user->avatar }}" alt="{{ $order->user->name }}">
            @endif
            <div class="mt-6" x-data="{ editStatus: false }">
                <div class="flex flex-wrap gap-2">
                    @if($canApprove)
                        <form class="grid gap-2" method="POST" action="{{ route('admin.orders.approve', $order) }}" onsubmit="return confirm('Approve this order? / ยืนยันอนุมัติออเดอร์นี้?')">
                            @csrf
                            @if($reviewStatus === 'needs_manual_review')
                                <label class="flex max-w-lg items-start gap-2 rounded-md border border-amber-300 bg-amber-50 p-3 text-xs text-amber-900 dark:border-amber-400/30 dark:bg-amber-400/10 dark:text-amber-100">
                                    <input class="mt-0.5" type="checkbox" name="manual_payment_review_confirmed" value="1">
                                    <span>I manually checked the slip image, amount, receiver, and duplicate risk. / ตรวจสลิป ยอด ผู้รับ และความเสี่ยงสลิปซ้ำด้วยตัวเองแล้ว</span>
                                </label>
                                <input class="max-w-lg rounded-md border border-amber-300 bg-white px-3 py-2 text-sm text-zinc-950 dark:border-amber-400/30 dark:bg-zinc-950 dark:text-white" name="manual_payment_review_note" placeholder="Manual review note / หมายเหตุการตรวจเอง">
                            @endif
                            <button class="inline-flex w-fit items-center gap-2 rounded-md bg-emerald-400 px-4 py-2 font-semibold text-zinc-950 {{ $reviewStatus === 'risky' ? 'opacity-60' : '' }}"><x-icon name="check" />Approve / อนุมัติ</button>
                        </form>
                    @endif
                    @if($canReject)
                        <form method="POST" action="{{ route('admin.orders.reject', $order) }}" onsubmit="return confirm('Reject this order? / ยืนยันปฏิเสธออเดอร์นี้?')">@csrf<button class="inline-flex items-center gap-2 rounded-md bg-rose-400 px-4 py-2 font-semibold text-zinc-950"><x-icon name="x" />Reject / ปฏิเสธ</button></form>
                    @endif
                    @if($canCancel || $canRefund)
                        <button class="inline-flex items-center gap-2 rounded-md border border-zinc-200 px-4 py-2 font-semibold text-zinc-800 hover:border-emerald-300 dark:border-white/10 dark:text-zinc-100" type="button" @click="editStatus = !editStatus"><x-icon name="pencil" /><span x-text="editStatus ? 'Hide status actions / ซ่อนการแก้ไขสถานะ' : 'Edit status / แก้ไขสถานะ'"></span></button>
                    @endif
                    @if($canDelete)
                        <form method="POST" action="{{ route('admin.orders.destroy', $order) }}" onsubmit="return confirm('Delete this order and its tickets? / ลบออเดอร์และตั๋วทั้งหมด?')">@csrf @method('DELETE')<button class="inline-flex items-center gap-2 rounded-md border border-rose-300 px-4 py-2 font-semibold text-rose-700 dark:border-rose-400/40 dark:text-rose-200"><x-icon name="trash-2" />Delete / ลบ</button></form>
                    @endif
                    @if(! $canApprove && ! $canReject && ! $canCancel && ! $canRefund && ! $canDelete)
                        <span class="inline-flex items-center gap-2 rounded-md bg-zinc-100 px-4 py-2 text-sm font-semibold text-zinc-700 dark:bg-white/10 dark:text-zinc-200"><x-icon name="lock" />No status actions available / ไม่มีปุ่มเปลี่ยนสถานะ</span>
                    @endif
                </div>
                @if($canCancel || $canRefund)
                    <div class="mt-3 flex flex-wrap gap-2 rounded-md border border-zinc-200 bg-zinc-50 p-3 dark:border-white/10 dark:bg-zinc-900" x-cloak x-show="editStatus" x-transition>
                        @if($canCancel)
                            <form method="POST" action="{{ route('admin.orders.cancel', $order) }}" onsubmit="return confirm('Cancel this approved order? / ยืนยันยกเลิกออเดอร์ที่อนุมัติแล้ว?')">@csrf<button class="inline-flex items-center gap-2 rounded-md border border-amber-300 px-4 py-2 font-semibold text-amber-700 dark:border-amber-400/40 dark:text-amber-100"><x-icon name="ban" />Cancel / ยกเลิก</button></form>
                        @endif
                        @if($canRefund)
                            <form method="POST" action="{{ route('admin.orders.refund', $order) }}" onsubmit="return confirm('Refund this approved order? / ยืนยันคืนเงินออเดอร์นี้?')">@csrf<button class="inline-flex items-center gap-2 rounded-md border border-zinc-200 px-4 py-2 font-semibold text-zinc-800 dark:border-white/10 dark:text-zinc-100"><x-icon name="undo" />Refund / คืนเงิน</button></form>
                        @endif
                    </div>
                @endif
            </div>
            <div class="mt-3 flex flex-wrap gap-2">
                @if($order->payment_slip_path)
                    <form method="POST" action="{{ route('admin.orders.check-slip-qr', $order) }}">@csrf<button class="inline-flex items-center gap-2 rounded-md border border-emerald-300 px-4 py-2 font-semibold text-emerald-800 dark:border-emerald-400/40 dark:text-emerald-100"><x-icon name="qr-code" />Check slip QR / ตรวจ QR สลิป</button></form>
                @endif
                <form class="flex flex-wrap items-center gap-2" method="POST" action="{{ route('admin.orders.payment-slip', $order) }}" enctype="multipart/form-data">
                    @csrf
                    <label class="inline-flex cursor-pointer items-center gap-2 rounded-md border border-zinc-200 px-4 py-2 font-semibold text-zinc-800 hover:border-emerald-300 dark:border-white/10 dark:text-zinc-100">
                        <x-icon name="upload" />Reupload slip / อัปโหลดสลิปใหม่
                        <input class="sr-only" type="file" name="slip" accept="image/*" required onchange="this.form.submit()">
                    </label>
                </form>
            </div>
            @if($order->payment_slip_path)
                <img class="mt-6 max-h-96 rounded-lg border border-zinc-200 dark:border-white/10 object-contain" src="{{ asset('uploads/'.$order->payment_slip_path) }}" alt="Payment slip / สลิปชำระเงิน">
            @endif
            @if($payment?->slip_qr_status || $payment?->slip_review_status)
                <div class="mt-6 rounded-lg border border-zinc-200 bg-zinc-50 p-4 dark:border-white/10 dark:bg-white/[0.03]">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <h2 class="inline-flex items-center gap-2 text-lg font-semibold text-zinc-950 dark:text-white"><x-icon name="scan-line" class="h-5 w-5 text-emerald-500" />Slip QR assist / ช่วยอ่าน QR จากสลิป</h2>
                        <div class="flex flex-wrap gap-2">
                            @if($reviewStatus)
                                <span class="rounded px-2 py-1 text-xs font-semibold {{ $reviewBadgeClass }}">{{ str_replace('_', ' ', $reviewStatus) }}</span>
                            @endif
                            @if($payment->slip_qr_status)
                                <span class="rounded bg-white px-2 py-1 text-xs font-semibold text-zinc-700 dark:bg-zinc-950 dark:text-zinc-200">{{ str_replace('_', ' ', $payment->slip_qr_status) }}</span>
                            @endif
                        </div>
                    </div>
                    @if($reviewFlags)
                        <div class="mt-4 rounded-md border border-amber-300 bg-amber-50 p-3 text-sm text-amber-900 dark:border-amber-400/30 dark:bg-amber-400/10 dark:text-amber-100">
                            <div class="font-semibold">Review flags / สิ่งที่ต้องตรวจ</div>
                            <ul class="mt-2 grid gap-1">
                                @foreach($reviewFlags as $flag => $message)
                                    <li><span class="font-mono text-xs">{{ str_replace('_', ' ', $flag) }}</span>: {{ $message }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                    @if(in_array($payment->slip_qr_status, ['decoded', 'duplicate'], true))
                        @if($duplicate)
                            <div class="mt-4 rounded-md border border-rose-300 bg-rose-50 p-3 text-sm text-rose-800 dark:border-rose-400/30 dark:bg-rose-400/10 dark:text-rose-100">
                                <div class="font-semibold">Possible duplicate slip / อาจเป็นสลิปซ้ำ</div>
                                <p class="mt-1">Matched {{ $duplicate['matched_by'] ?? 'QR data' }} with order {{ $duplicate['order_number'] ?? ('#'.($duplicate['ticket_order_id'] ?? '-')) }}. Please treat this slip as used/invalid until manually verified. / พบข้อมูลซ้ำกับออเดอร์เดิม กรุณาถือว่าสลิปนี้ถูกใช้แล้วหรือไม่ถูกต้องจนกว่าจะตรวจสอบเอง</p>
                            </div>
                        @endif
                        <dl class="mt-4 grid gap-3 text-sm text-zinc-700 dark:text-zinc-300 sm:grid-cols-2">
                            @if(($payment->slip_qr_data['format'] ?? null) === 'slip_verify')
                                <div><dt class="text-zinc-500 dark:text-zinc-400">Slip QR type / ประเภท QR</dt><dd class="font-semibold text-zinc-950 dark:text-white">Thai Slip Verify / ตรวจสอบสลิปไทย</dd></div>
                                <div><dt class="text-zinc-500 dark:text-zinc-400">Sending bank / ธนาคารต้นทาง</dt><dd>{{ $payment->slip_qr_data['slip_verify']['sending_bank_name'] ?? 'Not found / ไม่พบข้อมูล' }} @if($payment->slip_qr_data['slip_verify']['sending_bank'] ?? null)({{ $payment->slip_qr_data['slip_verify']['sending_bank'] }})@endif</dd></div>
                            @endif
                            @if($emvco)
                                <div><dt class="text-zinc-500 dark:text-zinc-400">EMVCo initiation / รูปแบบ QR</dt><dd class="font-semibold text-zinc-950 dark:text-white">{{ ucfirst($emvco['initiation_method'] ?? 'unknown') }} @if($emvco['initiation_method_code'] ?? null)({{ $emvco['initiation_method_code'] }})@endif</dd></div>
                                <div><dt class="text-zinc-500 dark:text-zinc-400">PromptPay ID / พร้อมเพย์</dt><dd>{{ $emvco['merchant_account_information']['promptpay_id'] ?? 'Not found / ไม่พบข้อมูล' }} @if($emvco['merchant_account_information']['promptpay_type'] ?? null)({{ str_replace('_', ' ', $emvco['merchant_account_information']['promptpay_type']) }})@endif</dd></div>
                                <div><dt class="text-zinc-500 dark:text-zinc-400">Currency / สกุลเงิน</dt><dd>{{ $emvco['currency'] ?? '-' }} @if($emvco['currency_code'] ?? null)({{ $emvco['currency_code'] }})@endif</dd></div>
                                <div><dt class="text-zinc-500 dark:text-zinc-400">Country / ประเทศ</dt><dd>{{ $emvco['country_code'] ?? '-' }}</dd></div>
                                <div class="sm:col-span-2">
                                    <dt class="text-zinc-500 dark:text-zinc-400">CRC checksum / ตรวจสอบ CRC</dt>
                                    <dd class="{{ ($emvco['crc_checksum']['valid'] ?? false) ? 'text-emerald-700 dark:text-emerald-200' : 'text-rose-700 dark:text-rose-200' }}">
                                        {{ ($emvco['crc_checksum']['valid'] ?? false) ? 'Valid / ถูกต้อง' : 'Invalid / ไม่ถูกต้อง' }}
                                        @if($emvco['crc_checksum']['value'] ?? null)
                                            · {{ $emvco['crc_checksum']['value'] }}
                                        @endif
                                    </dd>
                                </div>
                            @endif
                            <div>
                                <dt class="text-zinc-500 dark:text-zinc-400">Decoded amount / ยอดที่อ่านได้</dt>
                                <dd class="font-semibold text-zinc-950 dark:text-white">{{ $payment->slip_qr_amount_thb ? 'THB '.number_format((float) $payment->slip_qr_amount_thb, 2) : 'Not found / ไม่พบข้อมูล' }}</dd>
                                @if($qrAmountMatches !== null)
                                    <p class="mt-1 text-xs {{ $qrAmountMatches ? 'text-emerald-700 dark:text-emerald-200' : 'text-amber-700 dark:text-amber-200' }}">{{ $qrAmountMatches ? 'Amount matches this order. / ยอดตรงกับออเดอร์' : 'Amount differs from this order. / ยอดไม่ตรงกับออเดอร์' }}</p>
                                @endif
                            </div>
                            <div><dt class="text-zinc-500 dark:text-zinc-400">Reference / เลขอ้างอิง</dt><dd>{{ $payment->slip_qr_reference ?: 'Not found / ไม่พบข้อมูล' }}</dd></div>
                            <div><dt class="text-zinc-500 dark:text-zinc-400">Receiver / ผู้รับเงิน</dt><dd>{{ $payment->slip_qr_receiver ?: 'Not found / ไม่พบข้อมูล' }}</dd></div>
                            <div><dt class="text-zinc-500 dark:text-zinc-400">Paid at / เวลาชำระเงิน</dt><dd>{{ $payment->slip_qr_paid_at?->format('M j, Y H:i') ?: 'Not found / ไม่พบข้อมูล' }}</dd></div>
                        </dl>
                        <details class="mt-4 text-sm">
                            <summary class="cursor-pointer font-medium text-zinc-700 dark:text-zinc-200">Raw QR payload / ข้อมูล QR ดิบ</summary>
                            <pre class="mt-2 max-h-40 overflow-auto rounded-md bg-white p-3 text-xs text-zinc-700 dark:bg-zinc-950 dark:text-zinc-200">{{ $payment->slip_qr_payload }}</pre>
                        </details>
                    @else
                        <p class="mt-3 text-sm text-zinc-600 dark:text-zinc-300">No readable QR data was found. Please continue manual slip review before approving. / ไม่พบข้อมูล QR ที่อ่านได้ กรุณาตรวจสลิปด้วยตัวเองก่อนอนุมัติ</p>
                    @endif
                </div>
            @endif
        </section>
        <section class="rounded-lg border border-zinc-200 dark:border-white/10 bg-white dark:bg-white/[0.04] p-6">
            <h2 class="inline-flex items-center gap-2 text-xl font-semibold text-zinc-950 dark:text-white"><x-icon name="ticket" class="h-5 w-5 text-emerald-500" />Tickets / ตั๋ว</h2>
            <div class="mt-4 grid gap-3">
                @foreach($order->tickets as $ticket)
                    <div class="interactive-card group rounded-md border border-zinc-200 p-4 dark:border-white/10" x-data="{ editHolder: false }">
                        <a class="click-area-link" href="{{ route('tickets.show', $ticket->uuid) }}" aria-label="Open ticket for {{ $ticket->holder_name }}"></a>
                        <div class="click-area-content">
                            <div class="font-medium text-zinc-950 group-hover:text-emerald-700 dark:text-white">{{ $ticket->event->name }}</div>
                            <div class="mt-1 flex flex-wrap items-center gap-2 text-sm text-zinc-600 dark:text-zinc-400"><span>{{ $ticket->ticketType->name }}</span><x-status-badge :status="$ticket->status" type="ticket" /></div>
                        </div>
                        <div class="click-area-content mt-2 flex flex-wrap gap-2 text-sm">
                            <a class="inline-flex items-center gap-1 font-medium text-emerald-700 hover:text-emerald-800 dark:text-emerald-200" href="{{ route('tickets.show', $ticket->uuid) }}"><x-icon name="ticket" />Open ticket / เปิดตั๋ว</a>
                            <a class="inline-flex items-center gap-1 font-medium text-emerald-700 hover:text-emerald-800 dark:text-emerald-200" href="{{ route('admin.events.overview', $ticket->event) }}"><x-icon name="calendar-days" />Event overview / ภาพรวมอีเวนต์</a>
                        </div>
                        <div class="click-area-content mt-3 rounded-md bg-zinc-50 p-3 text-sm dark:bg-white/5">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <div>
                                    <div class="text-zinc-500">Holder / ผู้ถือบัตร</div>
                                    <div class="font-semibold text-zinc-950 dark:text-white">{{ $ticket->holder_name }}</div>
                                    <div class="text-zinc-600 dark:text-zinc-400">{{ $ticket->holder_phone ?: 'No phone / ไม่มีเบอร์โทร' }}</div>
                                </div>
                                <button class="inline-flex items-center gap-2 rounded-md border border-zinc-200 px-3 py-2 text-sm font-semibold text-zinc-800 hover:border-emerald-300 dark:border-white/10 dark:text-zinc-100" type="button" @click="editHolder = !editHolder"><x-icon name="pencil" />
                                <!-- <span x-text="editHolder ? 'Cancel / ยกเลิก' : 'Edit / แก้ไข'"></span> -->
                            </button>
                            </div>
                            <form class="mt-3 grid gap-3 sm:grid-cols-[1fr_1fr_auto]" method="POST" action="{{ route('admin.events.tickets.holder', [$ticket->event, $ticket]) }}" x-cloak x-show="editHolder" x-transition>
                                @csrf
                                @method('PATCH')
                                <label class="text-sm text-zinc-700 dark:text-zinc-300">Name<input class="mt-1 w-full rounded-md border border-zinc-200 bg-white px-3 py-2 text-zinc-950 dark:border-white/10 dark:bg-zinc-950 dark:text-white" name="holder_name" value="{{ $ticket->holder_name }}" required></label>
                                <label class="text-sm text-zinc-700 dark:text-zinc-300">Phone<input class="mt-1 w-full rounded-md border border-zinc-200 bg-white px-3 py-2 text-zinc-950 dark:border-white/10 dark:bg-zinc-950 dark:text-white" name="holder_phone" value="{{ $ticket->holder_phone }}"></label>
                                <button class="self-end inline-flex items-center justify-center gap-2 rounded-md bg-emerald-400 px-3 py-2 text-sm font-semibold text-zinc-950"><x-icon name="save" />Save / บันทึก</button>
                            </form>
                        </div>
                        @if(in_array($order->status, ['cancelled', 'refunded'], true))
                            <form class="click-area-content mt-3" method="POST" action="{{ route('admin.events.tickets.destroy', [$ticket->event, $ticket]) }}" onsubmit="return confirm('Delete this ticket? / ลบตั๋วนี้?')">
                                @csrf
                                @method('DELETE')
                                <button class="inline-flex items-center gap-2 rounded-md border border-rose-300 px-3 py-2 text-sm font-semibold text-rose-700 dark:border-rose-400/40 dark:text-rose-200"><x-icon name="trash-2" />Delete ticket / ลบตั๋ว</button>
                            </form>
                        @endif
                    </div>
                @endforeach
            </div>
        </section>
    </div>
</x-layouts.app>
