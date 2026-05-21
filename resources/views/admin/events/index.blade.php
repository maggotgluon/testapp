<x-layouts.app title="Manage events">
    <div class="flex items-center justify-between gap-3">
        <h1 class="text-3xl font-semibold text-zinc-950 dark:text-white">Events</h1>
        <a class="rounded-md bg-emerald-400 px-4 py-2 font-semibold text-zinc-950" href="{{ route('admin.events.create') }}">New event</a>
    </div>
    <div class="mt-6 grid gap-4">
        @foreach($events as $event)
            <div class="rounded-lg border border-zinc-200 dark:border-white/10 bg-white dark:bg-white/[0.04] p-5">
                <div class="flex flex-wrap items-center justify-between gap-4">
                    <div>
                        <div class="font-semibold text-zinc-950 dark:text-white">{{ $event->name }}</div>
                        <div class="text-sm text-zinc-600 dark:text-zinc-400">{{ $event->starts_at->format('M j, Y H:i') }} · {{ $event->venue }}</div>
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="text-sm text-emerald-700 dark:text-emerald-200">{{ $event->ticketTypes->count() }} ticket types</span>
                        <a class="rounded-md bg-emerald-400 px-3 py-2 text-sm font-semibold text-zinc-950" href="{{ route('admin.events.overview', $event) }}">Overview</a>
                        <a class="rounded-md border border-zinc-200 dark:border-white/10 px-3 py-2 text-sm text-zinc-800 dark:text-zinc-100" href="{{ route('admin.events.edit', $event) }}">Edit</a>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</x-layouts.app>
