<x-layouts.app title="Find order">
    <div class="mx-auto max-w-2xl">
        <form class="rounded-lg border border-zinc-200 dark:border-white/10 bg-white dark:bg-white/[0.04] p-6">
            <h1 class="text-2xl font-semibold text-zinc-950 dark:text-white">Find order without account</h1>
            <div class="mt-5 grid gap-4 sm:grid-cols-2">
                <label class="text-sm text-zinc-700 dark:text-zinc-300">Phone<input class="mt-1 w-full rounded-md border border-zinc-200 dark:border-white/10 bg-white dark:bg-zinc-950 px-3 py-2 text-zinc-950 dark:text-white" name="phone" value="{{ request('phone') }}" required></label>
                <label class="text-sm text-zinc-700 dark:text-zinc-300">Order number<input class="mt-1 w-full rounded-md border border-zinc-200 dark:border-white/10 bg-white dark:bg-zinc-950 px-3 py-2 uppercase text-zinc-950 dark:text-white" name="order_number" value="{{ request('order_number') }}" placeholder="BNML-0521-001" required></label>
            </div>
            <button class="mt-5 rounded-md bg-emerald-400 px-4 py-2 font-semibold text-zinc-950">Search</button>
        </form>
        <div class="mt-6 grid gap-4">
            @foreach($orders as $order)
                <a class="rounded-lg border border-zinc-200 dark:border-white/10 bg-white dark:bg-white/[0.04] p-5 hover:border-emerald-300" href="{{ route('orders.show', ['order' => $order, 'phone' => $order->customer_phone]) }}">
                    <div class="font-semibold text-zinc-950 dark:text-white">{{ $order->order_number }}</div>
                    <div class="text-sm text-zinc-600 dark:text-zinc-400">{{ $order->status }} · THB {{ number_format($order->total_thb) }}</div>
                </a>
            @endforeach
        </div>
    </div>
</x-layouts.app>
