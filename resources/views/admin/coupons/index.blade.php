<x-layouts.app title="Coupons">
    <div class="flex items-center justify-between gap-3">
        <h1 class="text-3xl font-semibold text-zinc-950 dark:text-white">Coupons / คูปอง</h1>
        <a class="inline-flex items-center gap-2 rounded-md bg-emerald-400 px-4 py-2 font-semibold text-zinc-950" href="{{ route('admin.coupons.create') }}"><x-icon name="plus" />New coupon / เพิ่มคูปอง</a>
    </div>
    <form class="mt-5 grid gap-3 rounded-lg border border-zinc-200 bg-white p-4 dark:border-white/10 dark:bg-white/[0.04] sm:grid-cols-4">
        <label class="text-sm text-zinc-700 dark:text-zinc-300">Event / อีเวนต์
            <select class="mt-1 w-full rounded-md border border-zinc-200 bg-white px-3 py-2 text-zinc-950 dark:border-white/10 dark:bg-zinc-950 dark:text-white" name="event_id">
                <option value="">All events / ทุกอีเวนต์</option>
                @foreach($events as $event)
                    <option value="{{ $event->id }}" @selected(request('event_id') == $event->id)>{{ $event->name }}</option>
                @endforeach
            </select>
        </label>
        <label class="text-sm text-zinc-700 dark:text-zinc-300">Type / ประเภท
            <select class="mt-1 w-full rounded-md border border-zinc-200 bg-white px-3 py-2 text-zinc-950 dark:border-white/10 dark:bg-zinc-950 dark:text-white" name="type">
                <option value="">All types / ทุกประเภท</option>
                <option value="fixed" @selected(request('type') === 'fixed')>Fixed THB / ลดเป็นบาท</option>
                <option value="percent" @selected(request('type') === 'percent')>Percent / เปอร์เซ็นต์</option>
            </select>
        </label>
        <label class="text-sm text-zinc-700 dark:text-zinc-300">Status / สถานะ
            <select class="mt-1 w-full rounded-md border border-zinc-200 bg-white px-3 py-2 text-zinc-950 dark:border-white/10 dark:bg-zinc-950 dark:text-white" name="status">
                <option value="">All statuses / ทุกสถานะ</option>
                <option value="active" @selected(request('status') === 'active')>Active / ใช้งาน</option>
                <option value="inactive" @selected(request('status') === 'inactive')>Inactive / ปิดอยู่</option>
            </select>
        </label>
        <div class="flex items-end gap-2">
            <button class="inline-flex flex-1 items-center justify-center gap-2 rounded-md bg-emerald-400 px-4 py-2 font-semibold text-zinc-950"><x-icon name="search" />Filter / กรอง</button>
            <a class="inline-flex items-center justify-center rounded-md border border-zinc-200 px-3 py-2 text-sm font-semibold text-zinc-700 dark:border-white/10 dark:text-zinc-200" href="{{ route('admin.coupons.index') }}">Reset</a>
        </div>
    </form>
    <div class="mt-6 rounded-lg border border-zinc-200 dark:border-white/10 bg-white dark:bg-white/[0.04]">
        <div class="divide-y divide-white/10">
            @foreach($coupons as $coupon)
                <div class="flex flex-wrap items-center justify-between gap-4 p-4 hover:bg-zinc-50 dark:bg-white/[0.03]">
                    <div>
                        <a class="font-semibold text-zinc-950 hover:text-emerald-700 dark:text-white dark:hover:text-emerald-200" href="{{ route('admin.coupons.edit', $coupon) }}">{{ $coupon->code }}</a>
                        <div class="text-sm text-zinc-600 dark:text-zinc-400">{{ $coupon->event?->name ?? 'All events / ทุกอีเวนต์' }} · {{ $coupon->ticketType?->name ?? 'Any ticket / ทุกตั๋ว' }} · {{ $coupon->discount_scope === 'item' ? 'per item / ต่อใบ' : 'per order / ต่อออเดอร์' }} · {{ $coupon->discount_type === 'percent' ? $coupon->discount_value.'%' : 'THB '.number_format($coupon->discount_value) }}</div>
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="rounded bg-zinc-100 dark:bg-white/10 px-3 py-1 text-sm {{ $coupon->is_active ? 'text-emerald-700 dark:text-emerald-200' : 'text-zinc-600 dark:text-zinc-400' }}">{{ $coupon->is_active ? 'active / ใช้งาน' : 'inactive / ปิดอยู่' }}</span>
                        <form method="POST" action="{{ route('admin.coupons.destroy', $coupon) }}" onsubmit="return confirm('Delete this coupon? / ลบคูปองนี้?')">
                            @csrf
                            @method('DELETE')
                            <button class="inline-flex items-center gap-2 rounded-md border border-rose-300 px-3 py-2 text-sm font-semibold text-rose-700 dark:border-rose-400/40 dark:text-rose-200"><x-icon name="trash-2" />Delete / ลบ</button>
                        </form>
                    </div>
                </div>
            @endforeach
        </div>
        <div class="p-4">{{ $coupons->links() }}</div>
    </div>
</x-layouts.app>
