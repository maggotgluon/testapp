<x-layouts.app title="Admin">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-3xl font-semibold text-zinc-950 dark:text-white"><x-t en="Admin dashboard" th="แดชบอร์ดผู้ดูแล" /></h1>
            @if($selectedEvent)
                <a class="mt-2 inline-flex items-center gap-2 text-sm font-medium text-emerald-700 hover:text-emerald-800 dark:text-emerald-200" href="{{ route('admin.events.overview', $selectedEvent) }}"><x-icon name="calendar-days" />{{ $selectedEvent->name }} <x-t en="overview" th="ภาพรวมอีเวนต์" /></a>
            @endif
        </div>
        @if($manageableEvents->count() > 1)
            <form class="flex flex-wrap items-center gap-2">
                <label class="text-sm text-zinc-600 dark:text-zinc-300"><x-t en="Event" th="อีเวนต์" /></label>
                <select class="rounded-md border border-zinc-200 bg-white px-3 py-2 text-sm text-zinc-950 dark:border-white/10 dark:bg-zinc-950 dark:text-white" name="event_id" onchange="this.form.submit()">
                    <option value="" data-i18n-en="All events" data-i18n-th="ทุกอีเวนต์">All events</option>
                    @foreach($manageableEvents as $event)
                        <option value="{{ $event->id }}" @selected(request('event_id') == $event->id)>{{ $event->name }}</option>
                    @endforeach
                </select>
            </form>
        @elseif($manageableEvents->count() === 1)
            <a class="inline-flex items-center gap-2 rounded-md border border-zinc-200 px-3 py-2 text-sm font-semibold text-zinc-800 hover:border-emerald-300 dark:border-white/10 dark:text-zinc-100" href="{{ route('admin.events.overview', $manageableEvents->first()) }}"><x-icon name="calendar-days" />{{ $manageableEvents->first()->name }}</a>
        @endif
    </div>
    <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        @foreach([['Events', 'อีเวนต์', $eventCount, 'calendar-days'], ['Pending', 'รอตรวจสอบ', $pendingOrders, 'clock'], ['Revenue THB', 'รายได้', number_format($revenueThb), 'wallet'], ['Checked in', 'เช็กอินแล้ว', $checkedIn, 'check']] as [$labelEn, $labelTh, $value, $icon])
            <div class="rounded-lg border border-zinc-200 dark:border-white/10 bg-white dark:bg-white/[0.04] p-5">
                <div class="inline-flex items-center gap-2 text-sm text-zinc-600 dark:text-zinc-400"><x-icon :name="$icon" class="h-4 w-4 text-emerald-500" /><x-t :en="$labelEn" :th="$labelTh" /></div>
                <div class="mt-2 text-3xl font-semibold text-zinc-950 dark:text-white">{{ $value }}</div>
            </div>
        @endforeach
    </div>
    <div class="mt-6 rounded-lg border border-emerald-400/20 bg-emerald-400/10 p-5">
        <div class="text-sm font-semibold uppercase tracking-[0.18em] text-emerald-700 dark:text-emerald-200"><x-t en="AI ops assistant" th="ผู้ช่วยสรุปงาน" /></div>
        <div class="mt-3 grid gap-2 text-sm text-emerald-950 dark:text-emerald-50">
            @foreach($aiInsights as $insight)
                <p>{{ $insight }}</p>
            @endforeach
        </div>
    </div>
    <div class="mt-6 rounded-lg border border-zinc-200 dark:border-white/10 bg-white dark:bg-white/[0.04]">
        <div class="border-b border-zinc-200 dark:border-white/10 p-4 font-semibold text-zinc-950 dark:text-white"><x-t en="Recent orders" th="ออเดอร์ล่าสุด" /></div>
        <div class="divide-y divide-white/10">
            @foreach($recentOrders as $order)
            <a class="interactive-row flex items-center justify-between gap-4 p-4 dark:bg-white/[0.03]" href="{{ route('admin.orders.show', $order) }}">
                    <div><div class="font-medium text-zinc-950 dark:text-white">{{ $order->order_number }}</div><div class="text-sm text-zinc-600 dark:text-zinc-400">{{ $order->customer_name }} · {{ $order->items->pluck('event.name')->filter()->unique()->join(', ') }} · {{ $order->tickets_count }} <x-t en="tickets" th="ตั๋ว" /></div></div>
                    <x-status-badge :status="$order->status" />
                </a>
            @endforeach
        </div>
    </div>
</x-layouts.app>
