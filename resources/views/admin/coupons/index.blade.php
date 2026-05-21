<x-layouts.app title="Coupons">
    <div class="flex items-center justify-between gap-3">
        <h1 class="text-3xl font-semibold text-white">Coupons</h1>
        <a class="rounded-md bg-emerald-400 px-4 py-2 font-semibold text-zinc-950" href="{{ route('admin.coupons.create') }}">New coupon</a>
    </div>
    <div class="mt-6 rounded-lg border border-white/10 bg-white/[0.04]">
        <div class="divide-y divide-white/10">
            @foreach($coupons as $coupon)
                <a class="flex flex-wrap items-center justify-between gap-4 p-4 hover:bg-white/[0.03]" href="{{ route('admin.coupons.edit', $coupon) }}">
                    <div>
                        <div class="font-semibold text-white">{{ $coupon->code }}</div>
                        <div class="text-sm text-zinc-400">{{ $coupon->event?->name ?? 'All events' }} · {{ $coupon->discount_type === 'percent' ? $coupon->discount_value.'%' : 'THB '.number_format($coupon->discount_value) }}</div>
                    </div>
                    <span class="rounded bg-white/10 px-3 py-1 text-sm {{ $coupon->is_active ? 'text-emerald-200' : 'text-zinc-400' }}">{{ $coupon->is_active ? 'active' : 'inactive' }}</span>
                </a>
            @endforeach
        </div>
        <div class="p-4">{{ $coupons->links() }}</div>
    </div>
</x-layouts.app>
