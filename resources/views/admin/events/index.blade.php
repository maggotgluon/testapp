<x-layouts.app title="Manage events">
    <div class="flex items-center justify-between gap-3">
        <h1 class="text-3xl font-semibold text-zinc-950 dark:text-white">Events / อีเวนต์</h1>
        <a class="inline-flex items-center gap-2 rounded-md bg-emerald-400 px-4 py-2 font-semibold text-zinc-950" href="{{ route('admin.events.create') }}"><x-icon name="plus" />New event / เพิ่มอีเวนต์</a>
    </div>
    <div class="mt-6 grid gap-4">
        @foreach($events as $event)
            <div class="interactive-row group rounded-lg border border-zinc-200 bg-white p-5 dark:border-white/10 dark:bg-white/[0.04]">
                <a class="click-area-link" href="{{ route('admin.events.overview', $event) }}" aria-label="Open event overview for {{ $event->name }}"></a>
                <div class="flex flex-wrap items-center justify-between gap-4">
                    <div class="click-area-content">
                        <div class="font-semibold text-zinc-950 group-hover:text-emerald-700 dark:text-white">{{ $event->name }}</div>
                        <div class="text-sm text-zinc-600 dark:text-zinc-400">{{ $event->starts_at->format('M j, Y H:i') }} · {{ $event->venue }}</div>
                    </div>
                    <div class="click-area-content flex flex-wrap items-center gap-2">
                        <span class="text-sm text-emerald-700 dark:text-emerald-200">{{ $event->ticketTypes->count() }} ticket types / ประเภทตั๋ว</span>
                        <a class="inline-flex items-center gap-2 rounded-md bg-emerald-400 px-3 py-2 text-sm font-semibold text-zinc-950" href="{{ route('admin.events.overview', $event) }}"><x-icon name="eye" /></a>
                        <a class="inline-flex items-center gap-2 rounded-md border border-zinc-200 dark:border-white/10 px-3 py-2 text-sm text-zinc-800 dark:text-zinc-100" href="{{ route('admin.events.edit', $event) }}"><x-icon name="edit" />Edit</a>
                        @if(auth()->user()->role === 'super_admin')
                            <form method="POST" action="{{ route('admin.events.destroy', $event) }}" onsubmit="return confirm('Delete this event and related records? / ลบอีเวนต์และข้อมูลที่เกี่ยวข้อง?')">
                                @csrf
                                @method('DELETE')
                                <button class="inline-flex items-center gap-2 rounded-md border border-rose-300 px-3 py-2 text-sm font-semibold text-rose-700 dark:border-rose-400/40 dark:text-rose-200"><x-icon name="trash-2" />Delete</button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</x-layouts.app>
