@php
    $payment = $order->payments->first();
    $qrAmountMatches = $payment?->slip_qr_amount_thb !== null
        ? abs((float) $payment->slip_qr_amount_thb - (float) $order->total_thb) < 0.01
        : null;
    $emvco = $payment?->slip_qr_data['emv']['emvco'] ?? null;
    $duplicate = $payment?->slip_qr_data['duplicate'] ?? null;
@endphp

<x-layouts.app :title="$order->order_number">
    <div class="grid gap-6 lg:grid-cols-[1fr_.8fr]">
        <section class="rounded-lg border border-zinc-200 dark:border-white/10 bg-white dark:bg-white/[0.04] p-6">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <p class="text-sm text-zinc-600 dark:text-zinc-400">Order / ออเดอร์</p>
                    <h1 class="text-3xl font-semibold text-zinc-950 dark:text-white">{{ $order->order_number }}</h1>
                </div>
                <span class="rounded bg-zinc-100 dark:bg-white/10 px-3 py-1 text-sm text-emerald-700 dark:text-emerald-200">{{ $order->status }}</span>
            </div>
            <dl class="mt-5 grid gap-2 text-sm text-zinc-700 dark:text-zinc-300">
                <div><dt class="text-zinc-500">Customer / ลูกค้า</dt><dd>{{ $order->customer_name }} · {{ $order->customer_phone }}</dd></div>
                <div><dt class="text-zinc-500">Email / อีเมล</dt><dd>{{ $order->customer_email ?: 'No email / ไม่มีอีเมล' }}</dd></div>
                <div><dt class="text-zinc-500">Account / บัญชี</dt><dd>{{ $order->user?->name ?: 'Guest checkout / ซื้อโดยไม่ล็อกอิน' }} @if($order->user?->provider) · {{ strtoupper($order->user->provider) }} @endif</dd></div>
                <div><dt class="text-zinc-500">Payment / การชำระเงิน</dt><dd>{{ str_replace('_', ' ', $order->payment_method) }} · THB {{ number_format($order->total_thb) }}</dd></div>
                <div><dt class="text-zinc-500">Note / หมายเหตุ</dt><dd>{{ $order->payment_note ?: 'No note / ไม่มีหมายเหตุ' }}</dd></div>
            </dl>
            @if($order->user?->avatar)
                <img class="mt-4 h-16 w-16 rounded-full object-cover" src="{{ $order->user->avatar }}" alt="{{ $order->user->name }}">
            @endif
            <div class="mt-6 flex flex-wrap gap-2">
                <form method="POST" action="{{ route('admin.orders.approve', $order) }}">@csrf<button class="inline-flex items-center gap-2 rounded-md bg-emerald-400 px-4 py-2 font-semibold text-zinc-950"><x-icon name="check" />Approve / อนุมัติ</button></form>
                <form method="POST" action="{{ route('admin.orders.reject', $order) }}">@csrf<button class="inline-flex items-center gap-2 rounded-md bg-rose-400 px-4 py-2 font-semibold text-zinc-950"><x-icon name="x" />Reject / ปฏิเสธ</button></form>
                <form method="POST" action="{{ route('admin.orders.refund', $order) }}">@csrf<button class="inline-flex items-center gap-2 rounded-md border border-zinc-200 dark:border-white/10 px-4 py-2 font-semibold text-zinc-800 dark:text-zinc-100"><x-icon name="undo" />Refund / คืนเงิน</button></form>
                @if($order->payment_slip_path)
                    <form method="POST" action="{{ route('admin.orders.check-slip-qr', $order) }}">@csrf<button class="inline-flex items-center gap-2 rounded-md border border-emerald-300 px-4 py-2 font-semibold text-emerald-800 dark:border-emerald-400/40 dark:text-emerald-100"><x-icon name="qr-code" />Check slip QR / ตรวจ QR สลิป</button></form>
                @endif
                <form method="POST" action="{{ route('admin.orders.destroy', $order) }}" onsubmit="return confirm('Delete this order and its tickets? / ลบออเดอร์และตั๋วทั้งหมด?')">@csrf @method('DELETE')<button class="inline-flex items-center gap-2 rounded-md border border-rose-300 px-4 py-2 font-semibold text-rose-700 dark:border-rose-400/40 dark:text-rose-200"><x-icon name="trash-2" />Delete / ลบ</button></form>
            </div>
            @if($order->payment_slip_path)
                <img class="mt-6 max-h-96 rounded-lg border border-zinc-200 dark:border-white/10 object-contain" src="{{ asset('uploads/'.$order->payment_slip_path) }}" alt="Payment slip / สลิปชำระเงิน">
            @endif
            @if($payment?->slip_qr_status)
                <div class="mt-6 rounded-lg border border-zinc-200 bg-zinc-50 p-4 dark:border-white/10 dark:bg-white/[0.03]">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <h2 class="inline-flex items-center gap-2 text-lg font-semibold text-zinc-950 dark:text-white"><x-icon name="scan-line" class="h-5 w-5 text-emerald-500" />Slip QR assist / ช่วยอ่าน QR จากสลิป</h2>
                        <span class="rounded px-2 py-1 text-xs font-semibold {{ $payment->slip_qr_status === 'decoded' ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-400/15 dark:text-emerald-100' : ($payment->slip_qr_status === 'duplicate' ? 'bg-rose-100 text-rose-800 dark:bg-rose-400/15 dark:text-rose-100' : 'bg-amber-100 text-amber-800 dark:bg-amber-400/15 dark:text-amber-100') }}">{{ str_replace('_', ' ', $payment->slip_qr_status) }}</span>
                    </div>
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
                    <div class="rounded-md border border-zinc-200 p-4 dark:border-white/10">
                        <a class="block hover:text-emerald-700 dark:hover:text-emerald-200" href="{{ route('tickets.show', $ticket->uuid) }}">
                            <div class="font-medium text-zinc-950 dark:text-white">{{ $ticket->event->name }}</div>
                            <div class="text-sm text-zinc-600 dark:text-zinc-400">{{ $ticket->ticketType->name }} · {{ $ticket->status }}</div>
                        </a>
                        <form class="mt-3" method="POST" action="{{ route('admin.events.tickets.destroy', [$ticket->event, $ticket]) }}" onsubmit="return confirm('Delete this ticket? / ลบตั๋วนี้?')">
                            @csrf
                            @method('DELETE')
                            <button class="inline-flex items-center gap-2 rounded-md border border-rose-300 px-3 py-2 text-sm font-semibold text-rose-700 dark:border-rose-400/40 dark:text-rose-200"><x-icon name="trash-2" />Delete ticket / ลบตั๋ว</button>
                        </form>
                    </div>
                @endforeach
            </div>
        </section>
    </div>
</x-layouts.app>
