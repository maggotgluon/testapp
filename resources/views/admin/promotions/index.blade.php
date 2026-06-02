<x-layouts.app title="Promotions">
    <div class="flex items-center justify-between gap-3">
        <h1 class="text-3xl font-semibold text-zinc-950 dark:text-white">Promotions / โปรโมชัน</h1>
        <a class="inline-flex items-center gap-2 rounded-md bg-emerald-400 px-4 py-2 font-semibold text-zinc-950" href="{{ route('admin.promotions.create') }}"><x-icon name="plus" />New promotion / เพิ่มโปรโมชัน</a>
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
                <option value="buy_x_get_y" @selected(request('type') === 'buy_x_get_y')>Buy X get Y / ซื้อ X แถม Y</option>
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
            <a class="inline-flex items-center justify-center rounded-md border border-zinc-200 px-3 py-2 text-sm font-semibold text-zinc-700 dark:border-white/10 dark:text-zinc-200" href="{{ route('admin.promotions.index') }}">Reset</a>
        </div>
    </form>
    <div class="mt-6 rounded-lg border border-zinc-200 dark:border-white/10 bg-white dark:bg-white/[0.04]">
        <div class="divide-y divide-white/10">
            @foreach($promotions as $promotion)
                <div class="flex flex-wrap items-center justify-between gap-4 p-4 hover:bg-zinc-50 dark:bg-white/[0.03]">
                    <div>
                        <a class="font-semibold text-zinc-950 hover:text-emerald-700 dark:text-white dark:hover:text-emerald-200" href="{{ route('admin.promotions.edit', $promotion) }}">{{ $promotion->name }}</a>
                        <div class="text-sm text-zinc-600 dark:text-zinc-400">{{ $promotion->event?->name ?? 'All events / ทุกอีเวนต์' }} · {{ $promotion->ticketType?->name ?? 'Any ticket / ทุกตั๋ว' }} · {{ $promotion->displaySummary() }} · {{ $promotion->combines_with_coupons ? 'stacks with coupons / ใช้ร่วมคูปองได้' : 'no coupon stacking / ไม่ร่วมคูปอง' }}</div>
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="rounded bg-zinc-100 dark:bg-white/10 px-3 py-1 text-sm {{ $promotion->is_active ? 'text-emerald-700 dark:text-emerald-200' : 'text-zinc-600 dark:text-zinc-400' }}">{{ $promotion->is_active ? 'active / ใช้งาน' : 'inactive / ปิดอยู่' }}</span>
                        <form method="POST" action="{{ route('admin.promotions.destroy', $promotion) }}" onsubmit="return confirm('Delete this promotion? / ลบโปรโมชันนี้?')">
                            @csrf
                            @method('DELETE')
                            <button class="inline-flex items-center gap-2 rounded-md border border-rose-300 px-3 py-2 text-sm font-semibold text-rose-700 dark:border-rose-400/40 dark:text-rose-200"><x-icon name="trash-2" />Delete / ลบ</button>
                        </form>
                    </div>
                </div>
            @endforeach
        </div>
        <div class="p-4">{{ $promotions->links() }}</div>
    </div>
</x-layouts.app>
