<x-layouts.app title="Admin">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <h1 class="text-3xl font-semibold text-zinc-950 dark:text-white">Admin dashboard / แดชบอร์ดผู้ดูแล</h1>
    </div>
    <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        @foreach([['Events / อีเวนต์', $eventCount, 'calendar-days'], ['Pending / รอตรวจสอบ', $pendingOrders, 'clock'], ['Revenue THB / รายได้', number_format($revenueThb), 'wallet'], ['Checked in / เช็กอินแล้ว', $checkedIn, 'check']] as [$label, $value, $icon])
            <div class="rounded-lg border border-zinc-200 dark:border-white/10 bg-white dark:bg-white/[0.04] p-5">
                <div class="inline-flex items-center gap-2 text-sm text-zinc-600 dark:text-zinc-400"><x-icon :name="$icon" class="h-4 w-4 text-emerald-500" />{{ $label }}</div>
                <div class="mt-2 text-3xl font-semibold text-zinc-950 dark:text-white">{{ $value }}</div>
            </div>
        @endforeach
    </div>
    <div class="mt-6 rounded-lg border border-emerald-400/20 bg-emerald-400/10 p-5">
        <div class="text-sm font-semibold uppercase tracking-[0.18em] text-emerald-700 dark:text-emerald-200">AI ops assistant / ผู้ช่วยสรุปงาน</div>
        <div class="mt-3 grid gap-2 text-sm text-emerald-950 dark:text-emerald-50">
            @foreach($aiInsights as $insight)
                <p>{{ $insight }}</p>
            @endforeach
        </div>
    </div>
    <div class="mt-6 rounded-lg border border-zinc-200 dark:border-white/10 bg-white dark:bg-white/[0.04]">
        <div class="border-b border-zinc-200 dark:border-white/10 p-4 font-semibold text-zinc-950 dark:text-white">Recent orders / ออเดอร์ล่าสุด</div>
        <div class="divide-y divide-white/10">
            @foreach($recentOrders as $order)
                <a class="flex items-center justify-between gap-4 p-4 hover:bg-zinc-50 dark:bg-white/[0.03]" href="{{ route('admin.orders.show', $order) }}">
                    <div><div class="font-medium text-zinc-950 dark:text-white">{{ $order->order_number }}</div><div class="text-sm text-zinc-600 dark:text-zinc-400">{{ $order->customer_name }} · {{ $order->tickets_count }} tickets / ตั๋ว</div></div>
                    <span class="text-sm text-emerald-700 dark:text-emerald-200">{{ $order->status }}</span>
                </a>
            @endforeach
        </div>
    </div>
</x-layouts.app>
