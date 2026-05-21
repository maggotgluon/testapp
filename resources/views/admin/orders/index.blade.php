<x-layouts.app title="Orders">
    <h1 class="text-3xl font-semibold text-white">Orders</h1>
    <div class="mt-6 rounded-lg border border-white/10 bg-white/[0.04]">
        <div class="divide-y divide-white/10">
            @foreach($orders as $order)
                <a class="flex flex-wrap items-center justify-between gap-4 p-4 hover:bg-white/[0.03]" href="{{ route('admin.orders.show', $order) }}">
                    <div>
                        <div class="font-semibold text-white">{{ $order->order_number }}</div>
                        <div class="text-sm text-zinc-400">{{ $order->customer_name }} · {{ $order->customer_phone }} · THB {{ number_format($order->total_thb) }}</div>
                    </div>
                    <span class="rounded bg-white/10 px-3 py-1 text-sm text-emerald-200">{{ $order->status }}</span>
                </a>
            @endforeach
        </div>
        <div class="p-4">{{ $orders->links() }}</div>
    </div>
</x-layouts.app>
