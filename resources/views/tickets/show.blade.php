@php
    $ticketUrl = route('tickets.show', ['uuid' => $ticket->uuid, 'phone' => $ticket->holder_phone]);
    $ticketIsActive = $ticket->order->status === 'approved' && in_array($ticket->status, ['approved', 'checked_in', 'checked_out'], true);
    $ticketImageUrl = $ticket->event->ticket_image_path || $ticket->event->poster_path
        ? asset('uploads/'.($ticket->event->ticket_image_path ?: $ticket->event->poster_path))
        : null;
    $ticketQrUrl = $ticketIsActive ? route('tickets.qr', $ticket->uuid) : null;
    $calendarUrl = 'https://calendar.google.com/calendar/render?'.http_build_query([
        'action' => 'TEMPLATE',
        'text' => $ticket->event->name,
        'dates' => $ticket->event->starts_at->copy()->utc()->format('Ymd\THis\Z').'/'.$ticket->event->ends_at->copy()->utc()->format('Ymd\THis\Z'),
        'details' => trim(($ticket->event->description ?? '')."\n\nTicket / ตั๋ว: ".$ticketUrl),
        'location' => trim($ticket->event->venue.' '.$ticket->event->location),
    ]);
@endphp
<x-layouts.app :title="$ticket->event->name">
    <div
        class="mx-auto grid max-w-5xl gap-6 lg:grid-cols-[420px_1fr]"
        x-data="ticketExport({
            filename: @js(\Illuminate\Support\Str::slug($ticket->event->name.' '.$ticket->ticketType->name.' '.$ticket->uuid)),
            active: @js($ticketIsActive),
            imageUrl: @js($ticketImageUrl),
            qrUrl: @js($ticketQrUrl),
            eventName: @js($ticket->event->name),
            ticketType: @js($ticket->ticketType->name),
            holderName: @js($ticket->holder_name),
            venue: @js($ticket->event->venue),
            location: @js($ticket->event->location),
            startsAt: @js($ticket->event->starts_at->format('M j, Y H:i')),
            endsAt: @js($ticket->event->ends_at->format('M j, Y H:i')),
            orderNumber: @js($ticket->order->order_number),
            uuid: @js($ticket->uuid),
            status: @js(str_replace('_', ' ', $ticket->status)),
        })"
    >
        <div class="ticket-print-surface overflow-hidden rounded-lg border border-zinc-200 bg-white shadow-sm dark:border-white/10 dark:bg-zinc-950">
            <div class="ticket-export-card mx-auto flex min-h-[760px] max-w-[420px] flex-col bg-white text-zinc-950">
                <div class="relative h-[360px] overflow-hidden bg-gradient-to-br from-emerald-500 via-sky-500 to-zinc-800">
                    @if($ticketImageUrl)
                        <img class="h-full w-full object-cover object-center" src="{{ $ticketImageUrl }}" alt="{{ $ticket->event->name }}">
                    @endif
                    <div class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-zinc-950/90 to-transparent p-5 pt-16 text-white">
                        <p class="text-sm font-semibold uppercase tracking-wide">{{ $ticket->ticketType->name }}</p>
                        <h1 class="mt-1 text-3xl font-semibold leading-tight">{{ $ticket->event->name }}</h1>
                    </div>
                </div>
                <div class="flex flex-1 flex-col p-5">
                    <div class="grid gap-3 text-sm">
                        <div>
                            <div class="text-zinc-500">Holder / ผู้ถือบัตร</div>
                            <div class="text-lg font-semibold">{{ $ticket->holder_name }}</div>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <div class="text-zinc-500">Date / วันที่</div>
                                <div class="font-medium">{{ $ticket->event->starts_at->format('M j, Y') }}</div>
                            </div>
                            <div>
                                <div class="text-zinc-500">Time / เวลา</div>
                                <div class="font-medium">{{ $ticket->event->starts_at->format('H:i') }} - {{ $ticket->event->ends_at->format('H:i') }}</div>
                            </div>
                        </div>
                        <div>
                            <div class="text-zinc-500">Venue / สถานที่</div>
                            <div class="font-medium">{{ $ticket->event->venue }}</div>
                            @if($ticket->event->location)
                                <div class="text-zinc-600">{{ $ticket->event->location }}</div>
                            @endif
                        </div>
                    </div>
                    @if($ticketIsActive)
                        <div class="mt-5 grid place-items-center rounded-lg border border-zinc-200 bg-white p-4 text-zinc-950">
                            <img class="h-64 w-64" src="{{ $ticketQrUrl }}" alt="Ticket QR code / QR ตั๋ว">
                            <div class="mt-3 max-w-full break-all text-center font-mono text-xs">{{ $ticket->uuid }}</div>
                        </div>
                    @else
                        <div class="mt-5 rounded-lg border border-amber-300 bg-amber-50 p-4 text-amber-900">
                            <div class="text-lg font-semibold">Ticket not active yet / ตั๋วยังไม่พร้อมใช้งาน</div>
                            <p class="mt-2 text-sm">QR code will show after payment is approved. Current status: {{ str_replace('_', ' ', $ticket->order->status) }} / {{ str_replace('_', ' ', $ticket->status) }}.</p>
                        </div>
                    @endif
                    <div class="mt-auto grid gap-1 pt-4 text-xs text-zinc-500">
                        <div>Order / ออเดอร์: {{ $ticket->order->order_number }}</div>
                        <div>Status / สถานะ: {{ str_replace('_', ' ', $ticket->status) }}</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="rounded-lg border border-zinc-200 dark:border-white/10 bg-white dark:bg-white/[0.04] p-6">
        <div class="flex items-start justify-between gap-4">
            <div>
                <p class="text-sm text-emerald-600 dark:text-emerald-300">{{ $ticket->ticketType->name }}</p>
                <h1 class="text-3xl font-semibold text-zinc-950 dark:text-white">{{ $ticket->event->name }}</h1>
                <p class="mt-2 text-zinc-600 dark:text-zinc-400">{{ $ticket->event->venue }} · {{ $ticket->event->starts_at->format('M j, Y H:i') }}</p>
                <div class="mt-3 flex flex-wrap gap-2 text-sm">
                    @if($ticket->event->location_url)
                        <a class="inline-flex items-center gap-2 rounded-md border border-zinc-200 dark:border-white/10 px-3 py-2 text-emerald-700 dark:text-emerald-200 hover:border-emerald-300" href="{{ $ticket->event->location_url }}" target="_blank" rel="noopener"><x-icon name="map-pin" />Open map <br> เปิดแผนที่</a>
                    @endif
                    <a class="inline-flex items-center gap-2 rounded-md border border-zinc-200 dark:border-white/10 px-3 py-2 text-emerald-700 dark:text-emerald-200 hover:border-emerald-300" href="{{ $calendarUrl }}" target="_blank" rel="noopener"><x-icon name="calendar-plus" />Add to calendar <br> เพิ่มในปฏิทิน</a>
                </div>
            </div>
            <span class="rounded bg-zinc-100 dark:bg-white/10 px-3 py-1 text-sm text-emerald-700 dark:text-emerald-200">{{ str_replace('_', ' ', $ticket->status) }}</span>
        </div>
        @if($ticketIsActive)
            <div class="mt-5 grid gap-2 sm:grid-cols-3">
                <button class="inline-flex items-center justify-center gap-2 rounded-md border border-emerald-300 px-4 py-3 font-semibold text-emerald-700 hover:bg-emerald-400/10 dark:border-emerald-400/40 dark:text-emerald-100" type="button" @click="previewPng()" :disabled="previewLoading"><x-icon name="eye" /><span x-text="previewLoading ? 'Rendering... / กำลังสร้าง...' : 'Preview image / ดูตัวอย่างรูป'"></span></button>
                <button class="inline-flex items-center justify-center gap-2 rounded-md bg-emerald-400 px-4 py-3 font-semibold text-zinc-950 hover:bg-emerald-300" type="button" @click="downloadPng()"><x-icon name="image-down" />Save image / บันทึกรูป</button>
                <button class="inline-flex items-center justify-center gap-2 rounded-md border border-zinc-200 px-4 py-3 font-semibold text-zinc-800 hover:border-emerald-300 dark:border-white/10 dark:text-zinc-100" type="button" @click="printPdf()"><x-icon name="file-down" />Save PDF / บันทึก PDF</button>
            </div>
            <div class="fixed inset-0 z-50 grid place-items-center bg-zinc-950/70 p-4" x-cloak x-show="previewOpen" x-transition @keydown.escape.window="closePreview()">
                <div class="max-h-[92vh] w-full max-w-md overflow-hidden rounded-lg border border-zinc-200 bg-white shadow-xl dark:border-white/10 dark:bg-zinc-950">
                    <div class="flex items-center justify-between gap-3 border-b border-zinc-200 px-4 py-3 dark:border-white/10">
                        <h2 class="inline-flex items-center gap-2 font-semibold text-zinc-950 dark:text-white"><x-icon name="image" />Ticket image preview / ตัวอย่างรูปตั๋ว</h2>
                        <button class="rounded-md p-2 text-zinc-500 hover:bg-zinc-100 hover:text-zinc-900 dark:hover:bg-white/10 dark:hover:text-white" type="button" @click="closePreview()" aria-label="Close preview"><x-icon name="x" /></button>
                    </div>
                    <div class="max-h-[78vh] overflow-auto bg-zinc-100 p-4 dark:bg-zinc-900">
                        <img class="mx-auto w-full max-w-[360px] rounded-md bg-white shadow" :src="previewUrl" alt="Ticket image preview / ตัวอย่างรูปตั๋ว">
                    </div>
                    <div class="flex flex-wrap justify-end gap-2 border-t border-zinc-200 px-4 py-3 dark:border-white/10">
                        <button class="inline-flex items-center gap-2 rounded-md border border-zinc-200 px-3 py-2 text-sm font-semibold text-zinc-800 dark:border-white/10 dark:text-zinc-100" type="button" @click="closePreview()">Close / ปิด</button>
                        <button class="inline-flex items-center gap-2 rounded-md bg-emerald-400 px-3 py-2 text-sm font-semibold text-zinc-950" type="button" @click="downloadPng()"><x-icon name="image-down" />Save image / บันทึกรูป</button>
                    </div>
                </div>
            </div>
            <div class="mt-6 grid place-items-center rounded-lg bg-white p-5 text-zinc-950">
                <img class="h-64 w-64" src="{{ $ticketQrUrl }}" alt="Ticket QR code / QR ตั๋ว">
                <div class="mt-4 font-mono text-xs">{{ $ticket->uuid }}</div>
            </div>
        @else
            <div class="mt-6 rounded-lg border border-amber-400/30 bg-amber-400/10 p-5 text-amber-900 dark:text-amber-100">
                <div class="text-lg font-semibold">Ticket not active yet / ตั๋วยังไม่พร้อมใช้งาน</div>
                <p class="mt-2 text-sm">QR code will show after payment is approved. </p><p class="mt-2 text-sm">Current status: {{ str_replace('_', ' ', $ticket->order->status) }} / {{ str_replace('_', ' ', $ticket->status) }}.</p>
            </div>
        @endif
        <dl class="mt-6 grid gap-3 text-sm">
            <div><dt class="text-zinc-500">Holder / ผู้ถือบัตร</dt><dd class="text-zinc-950 dark:text-white">{{ $ticket->holder_name }}</dd></div>
            <div><dt class="text-zinc-500">Location / ที่ตั้ง</dt><dd class="text-zinc-950 dark:text-white">
                @if($ticket->event->location_url)
                    <a class="inline-flex items-center gap-1.5 text-emerald-700 underline dark:text-emerald-200" href="{{ $ticket->event->location_url }}" target="_blank" rel="noopener"><x-icon name="map-pin" class="h-3.5 w-3.5" />{{ $ticket->event->location ?: 'Open map / เปิดแผนที่' }}</a>
                @else
                    {{ $ticket->event->location ?: '-' }}
                @endif
            </dd></div>
            <div><dt class="text-zinc-500">Order / ออเดอร์</dt><dd class="text-zinc-950 dark:text-white"><a class="inline-flex items-center gap-1.5 text-emerald-700 underline dark:text-emerald-200" href="{{ route('orders.show', $ticket->order) }}"><x-icon name="receipt" class="h-3.5 w-3.5" />{{ $ticket->order->order_number }}</a></dd></div>
            @if($ticket->event->starts_at < now()->subHours(48))
            <div class="grid grid-cols-2">
                <div><dt class="text-zinc-500">Check in / เช็กอิน</dt><dd class="text-zinc-950 dark:text-white">{{ $ticket->checked_in_at?->format('M j, Y H:i') ?? 'Not yet / ยังไม่เช็กอิน' }}</dd></div>
                <div><dt class="text-zinc-500">Check out / เช็กเอาต์</dt><dd class="text-zinc-950 dark:text-white">{{ $ticket->checked_out_at?->format('M j, Y H:i') ?? 'Not yet / ยังไม่เช็กเอาต์' }}</dd></div>
            </div>
            @endif
        </dl>
        </div>
    </div>
</x-layouts.app>
