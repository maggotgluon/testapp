<x-layouts.app title="Events">
    <section class="mb-8 grid gap-6 lg:grid-cols-[1.25fr_.75fr]">
        <div>
            <p class="text-sm font-medium uppercase tracking-[0.18em] text-emerald-300">Live events</p>
            <h1 class="mt-3 max-w-3xl text-4xl font-semibold tracking-tight text-white sm:text-6xl">Book tickets, pay by QR or transfer, and check in with one scan.</h1>
        </div>
        <div class="self-end rounded-lg border border-white/10 bg-white/[0.04] p-5">
            <div class="text-sm text-zinc-300">Built for LINE-first ticketing with admin approval, coupons, slips, gate roles, and instant ticket status.</div>
        </div>
    </section>

    <div class="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
        @forelse($events as $event)
            <a href="{{ route('events.show', $event) }}" class="group overflow-hidden rounded-lg border border-white/10 bg-white/[0.04] transition hover:-translate-y-0.5 hover:border-emerald-300/50">
                <div class="aspect-[4/5] bg-gradient-to-br from-emerald-500 via-sky-500 to-zinc-800">
                    @if($event->poster_path)
                        <img class="h-full w-full object-cover" src="{{ asset('storage/'.$event->poster_path) }}" alt="{{ $event->name }}">
                    @endif
                </div>
                <div class="p-5">
                    <div class="flex items-start justify-between gap-3">
                        <h2 class="text-xl font-semibold text-white">{{ $event->name }}</h2>
                        <span class="rounded bg-white/10 px-2 py-1 text-xs text-emerald-200">{{ $event->starts_at->format('M j') }}</span>
                    </div>
                    <p class="mt-2 line-clamp-2 text-sm text-zinc-400">{{ $event->description }}</p>
                    <div class="mt-4 flex items-center justify-between text-sm">
                        <span class="text-zinc-300">{{ $event->venue }}</span>
                        <span class="font-semibold text-emerald-300">From THB {{ number_format($event->ticketTypes->min('price_thb') ?? 0) }}</span>
                    </div>
                </div>
            </a>
        @empty
            <div class="rounded-lg border border-white/10 bg-white/[0.04] p-8 text-zinc-300">No published events are on sale right now.</div>
        @endforelse
    </div>
</x-layouts.app>
