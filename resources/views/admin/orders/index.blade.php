<x-layouts.app title="Orders">
    <h1 class="text-3xl font-semibold text-zinc-950 dark:text-white">Orders / ออเดอร์</h1>
    <div class="mt-6 rounded-lg border border-zinc-200 dark:border-white/10 bg-white dark:bg-white/[0.04]">
        <div class="divide-y divide-white/10">
            @foreach($orders as $order)
                <div class="flex flex-wrap items-center justify-between gap-4 p-4 hover:bg-zinc-50 dark:bg-white/[0.03]">
                    <div>
                        <a class="font-semibold text-zinc-950 hover:text-emerald-700 dark:text-white dark:hover:text-emerald-200" href="{{ route('admin.orders.show', $order) }}">{{ $order->order_number }}</a>
                        <div class="text-sm text-zinc-600 dark:text-zinc-400">{{ $order->customer_name }} · {{ $order->customer_phone }} · THB {{ number_format($order->total_thb) }}</div>
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="rounded bg-zinc-100 dark:bg-white/10 px-3 py-1 text-sm text-emerald-700 dark:text-emerald-200">{{ $order->status }}</span>
                        <form method="POST" action="{{ route('admin.orders.destroy', $order) }}" onsubmit="return confirm('Delete this order and its tickets? / ลบออเดอร์และตั๋วทั้งหมด?')">
                            @csrf
                            @method('DELETE')
                            <button class="inline-flex items-center gap-2 rounded-md border border-rose-300 px-3 py-2 text-sm font-semibold text-rose-700 dark:border-rose-400/40 dark:text-rose-200"><x-icon name="trash-2" />Delete / ลบ</button>
                        </form>
                    </div>
                </div>
            @endforeach
        </div>
        <div class="p-4">{{ $orders->links() }}</div>
    </div>
</x-layouts.app>
