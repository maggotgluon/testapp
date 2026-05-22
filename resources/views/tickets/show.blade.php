@php
    $ticketUrl = route('tickets.show', ['uuid' => $ticket->uuid, 'phone' => $ticket->holder_phone]);
    $ticketIsActive = $ticket->order->status === 'approved' && in_array($ticket->status, ['approved', 'checked_in', 'checked_out'], true);
    $calendarUrl = 'https://calendar.google.com/calendar/render?'.http_build_query([
        'action' => 'TEMPLATE',
        'text' => $ticket->event->name,
        'dates' => $ticket->event->starts_at->copy()->utc()->format('Ymd\THis\Z').'/'.$ticket->event->ends_at->copy()->utc()->format('Ymd\THis\Z'),
        'details' => trim(($ticket->event->description ?? '')."\n\nTicket / ตั๋ว: ".$ticketUrl),
        'location' => trim($ticket->event->venue.' '.$ticket->event->location),
    ]);
@endphp
<x-layouts.app :title="$ticket->event->name">
    <div class="mx-auto grid max-w-4xl gap-6 lg:grid-cols-[.8fr_1fr]">
        <div class="overflow-hidden rounded-lg border border-zinc-200 dark:border-white/10 bg-white dark:bg-white/[0.04]">
            <div class="aspect-[4/5] bg-gradient-to-br from-emerald-500 via-sky-500 to-zinc-800">
                @if($ticket->event->ticket_image_path || $ticket->event->poster_path)
                    <img class="h-full w-full object-cover" src="{{ asset('uploads/'.($ticket->event->ticket_image_path ?: $ticket->event->poster_path)) }}" alt="{{ $ticket->event->name }}">
                @endif
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
                        <a class="rounded-md border border-zinc-200 dark:border-white/10 px-3 py-2 text-emerald-700 dark:text-emerald-200 hover:border-emerald-300" href="{{ $ticket->event->location_url }}" target="_blank" rel="noopener">Open map / เปิดแผนที่</a>
                    @endif
                    <a class="rounded-md border border-zinc-200 dark:border-white/10 px-3 py-2 text-emerald-700 dark:text-emerald-200 hover:border-emerald-300" href="{{ $calendarUrl }}" target="_blank" rel="noopener">Add to calendar / เพิ่มในปฏิทิน</a>
                </div>
            </div>
            <span class="rounded bg-zinc-100 dark:bg-white/10 px-3 py-1 text-sm text-emerald-700 dark:text-emerald-200">{{ str_replace('_', ' ', $ticket->status) }}</span>
        </div>
        @if($ticketIsActive)
            <div class="mt-6 grid place-items-center rounded-lg bg-white p-5 text-zinc-950">
                <img class="h-56 w-56" src="{{ route('tickets.qr', $ticket->uuid) }}" alt="Ticket QR code / QR ตั๋ว">
                <div class="mt-4 font-mono text-xs">{{ $ticket->uuid }}</div>
            </div>
        @else
            <div class="mt-6 rounded-lg border border-amber-400/30 bg-amber-400/10 p-5 text-amber-900 dark:text-amber-100">
                <div class="text-lg font-semibold">Ticket not active yet / ตั๋วยังไม่พร้อมใช้งาน</div>
                <p class="mt-2 text-sm">QR code will show after payment is approved. Current status: {{ str_replace('_', ' ', $ticket->order->status) }} / {{ str_replace('_', ' ', $ticket->status) }}.</p>
            </div>
        @endif
        <dl class="mt-6 grid gap-3 text-sm">
            <div><dt class="text-zinc-500">Holder / ผู้ถือบัตร</dt><dd class="text-zinc-950 dark:text-white">{{ $ticket->holder_name }}</dd></div>
            <div><dt class="text-zinc-500">Location / ที่ตั้ง</dt><dd class="text-zinc-950 dark:text-white">
                @if($ticket->event->location_url)
                    <a class="text-emerald-700 underline dark:text-emerald-200" href="{{ $ticket->event->location_url }}" target="_blank" rel="noopener">{{ $ticket->event->location ?: 'Open map / เปิดแผนที่' }}</a>
                @else
                    {{ $ticket->event->location ?: '-' }}
                @endif
            </dd></div>
            <div><dt class="text-zinc-500">Order / ออเดอร์</dt><dd class="text-zinc-950 dark:text-white">{{ $ticket->order->order_number }}</dd></div>
            <div><dt class="text-zinc-500">Check in / เช็กอิน</dt><dd class="text-zinc-950 dark:text-white">{{ $ticket->checked_in_at?->format('M j, Y H:i') ?? 'Not yet / ยังไม่เช็กอิน' }}</dd></div>
            <div><dt class="text-zinc-500">Check out / เช็กเอาต์</dt><dd class="text-zinc-950 dark:text-white">{{ $ticket->checked_out_at?->format('M j, Y H:i') ?? 'Not yet / ยังไม่เช็กเอาต์' }}</dd></div>
        </dl>
        </div>
    </div>
</x-layouts.app>
