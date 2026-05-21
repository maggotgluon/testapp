<x-layouts.app title="Admin">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <h1 class="text-3xl font-semibold text-zinc-950 dark:text-white">Admin dashboard</h1>
    </div>
    <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        @foreach([['Events', $eventCount], ['Pending', $pendingOrders], ['Revenue THB', number_format($revenueThb)], ['Checked in', $checkedIn]] as [$label, $value])
            <div class="rounded-lg border border-zinc-200 dark:border-white/10 bg-white dark:bg-white/[0.04] p-5">
                <div class="text-sm text-zinc-600 dark:text-zinc-400">{{ $label }}</div>
                <div class="mt-2 text-3xl font-semibold text-zinc-950 dark:text-white">{{ $value }}</div>
            </div>
        @endforeach
    </div>
    <div class="mt-6 rounded-lg border border-emerald-400/20 bg-emerald-400/10 p-5">
        <div class="text-sm font-semibold uppercase tracking-[0.18em] text-emerald-700 dark:text-emerald-200">AI ops assistant</div>
        <div class="mt-3 grid gap-2 text-sm text-emerald-950 dark:text-emerald-50">
            @foreach($aiInsights as $insight)
                <p>{{ $insight }}</p>
            @endforeach
        </div>
    </div>
    <div class="mt-6 rounded-lg border border-zinc-200 dark:border-white/10 bg-white dark:bg-white/[0.04]">
        <div class="border-b border-zinc-200 dark:border-white/10 p-4 font-semibold text-zinc-950 dark:text-white">Recent orders</div>
        <div class="divide-y divide-white/10">
            @foreach($recentOrders as $order)
                <a class="flex items-center justify-between gap-4 p-4 hover:bg-zinc-50 dark:bg-white/[0.03]" href="{{ route('admin.orders.show', $order) }}">
                    <div><div class="font-medium text-zinc-950 dark:text-white">{{ $order->order_number }}</div><div class="text-sm text-zinc-600 dark:text-zinc-400">{{ $order->customer_name }} · {{ $order->tickets_count }} tickets</div></div>
                    <span class="text-sm text-emerald-700 dark:text-emerald-200">{{ $order->status }}</span>
                </a>
            @endforeach
        </div>
    </div>
</x-layouts.app>
