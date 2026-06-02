@php
    $description = 'Discover upcoming fitness events, book tickets, pay by QR or bank transfer, and check in smoothly on event day.';
    $structuredData = [
        '@context' => 'https://schema.org',
        '@type' => 'ItemList',
        'name' => 'Upcoming fitness events',
        'description' => $description,
        'url' => route('events.index'),
        'itemListElement' => $events->values()->map(fn ($event, $index) => [
            '@type' => 'ListItem',
            'position' => $index + 1,
            'url' => route('events.show', $event),
            'name' => $event->name,
        ])->all(),
    ];
@endphp
<x-layouts.app
    title="Upcoming fitness events / อีเวนต์ฟิตเนสที่กำลังเปิดขาย"
    :meta-description="$description"
    :canonical-url="route('events.index')"
    :structured-data="$structuredData"
>
    <section class="mb-8 grid gap-6 lg:grid-cols-[1.25fr_.75fr]">
        <div>
            <p class="text-sm font-medium uppercase tracking-[0.18em] text-emerald-600 dark:text-emerald-300">Live events</p>
            <h1 class="mt-3 max-w-3xl text-4xl font-semibold tracking-tight text-zinc-950 dark:text-white sm:text-6xl">Book tickets, pay by QR or transfer, and check in with one scan.</h1>
        </div>
        <div class="self-end rounded-lg border border-zinc-200 dark:border-white/10 bg-white dark:bg-white/[0.04] p-5">
            <div class="text-sm text-zinc-700 dark:text-zinc-300">Built for LINE-first ticketing with admin approval, coupons, slips, gate roles, and instant ticket status.</div>
        </div>
    </section>

    <div class="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
        @forelse($events as $event)
            <a href="{{ route('events.show', $event) }}" class="interactive-card group overflow-hidden rounded-lg border border-zinc-200 bg-white dark:border-white/10 dark:bg-white/[0.04]">
                <div class="aspect-[4/5] bg-gradient-to-br from-emerald-500 via-sky-500 to-zinc-800">
                    @if($event->poster_path)
                        <img class="h-full w-full object-cover" src="{{ asset('uploads/'.$event->poster_path) }}" alt="{{ $event->name }}">
                    @endif
                </div>
                <div class="p-5">
                    <div class="flex items-start justify-between gap-3">
                        <h2 class="text-xl font-semibold text-zinc-950 dark:text-white">{{ $event->name }}</h2>
                        <span class="rounded bg-zinc-100 dark:bg-white/10 px-2 py-1 text-xs text-emerald-700 dark:text-emerald-200">{{ $event->starts_at->format('M j') }}</span>
                    </div>
                    <p class="mt-2 line-clamp-2 text-sm text-zinc-600 dark:text-zinc-400">{{ trim(preg_replace('/\s+/', ' ', strip_tags($event->description ?? ''))) }}</p>
                    <div class="mt-4 flex items-center justify-between text-sm">
                        <span class="text-zinc-700 dark:text-zinc-300">{{ $event->venue }}</span>
                        <span class="font-semibold text-emerald-600 dark:text-emerald-300">From / เริ่มที่ THB {{ number_format($event->ticketTypes->min('price_thb') ?? 0) }}</span>
                    </div>
                </div>
            </a>
        @empty
            <div class="rounded-lg border border-zinc-200 dark:border-white/10 bg-white dark:bg-white/[0.04] p-8 text-zinc-700 dark:text-zinc-300">No published events are on sale right now. / ยังไม่มีอีเวนต์ที่เปิดขายในขณะนี้</div>
        @endforelse
    </div>
</x-layouts.app>
