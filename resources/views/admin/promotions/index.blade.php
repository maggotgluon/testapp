<x-layouts.app title="Promotions">
    <div class="flex items-center justify-between gap-3">
        <h1 class="text-3xl font-semibold text-zinc-950 dark:text-white">Promotions / โปรโมชัน</h1>
        <a class="inline-flex items-center gap-2 rounded-md bg-emerald-400 px-4 py-2 font-semibold text-zinc-950" href="{{ route('admin.promotions.create') }}"><x-icon name="plus" />New promotion / เพิ่มโปรโมชัน</a>
    </div>
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
