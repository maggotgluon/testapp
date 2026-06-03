<x-layouts.app title="Coupons and promotions">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-3xl font-semibold text-zinc-950 dark:text-white"><x-t en="Coupons and promotions" th="คูปองและโปรโมชัน" /></h1>
            <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400"><x-t en="Manage discounts and event offers from one workspace." th="จัดการส่วนลดและข้อเสนอของอีเวนต์จากหน้าเดียว" /></p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a class="inline-flex items-center gap-2 rounded-md bg-emerald-400 px-4 py-2 font-semibold text-zinc-950" href="{{ route('admin.coupons.create') }}"><x-icon name="plus" /><x-t en="New coupon" th="เพิ่มคูปอง" /></a>
            <a class="inline-flex items-center gap-2 rounded-md border border-zinc-200 px-4 py-2 font-semibold text-zinc-800 hover:border-emerald-300 dark:border-white/10 dark:text-zinc-100" href="{{ route('admin.promotions.create') }}"><x-icon name="sparkles" /><x-t en="New promotion" th="เพิ่มโปรโมชัน" /></a>
        </div>
    </div>

    <div class="mt-5 flex flex-wrap gap-2 rounded-lg border border-zinc-200 bg-white p-2 dark:border-white/10 dark:bg-white/[0.04]">
        <a class="inline-flex items-center gap-2 rounded-md px-4 py-2 text-sm font-semibold {{ $activeTab === 'coupons' ? 'bg-emerald-400 text-zinc-950' : 'text-zinc-700 hover:bg-zinc-100 dark:text-zinc-200 dark:hover:bg-white/10' }}" href="{{ route('admin.coupons.index', request()->except(['promotions_page', 'coupons_page'])) }}"><x-icon name="tag" /><x-t en="Coupons" th="คูปอง" /></a>
        <a class="inline-flex items-center gap-2 rounded-md px-4 py-2 text-sm font-semibold {{ $activeTab === 'promotions' ? 'bg-emerald-400 text-zinc-950' : 'text-zinc-700 hover:bg-zinc-100 dark:text-zinc-200 dark:hover:bg-white/10' }}" href="{{ route('admin.promotions.index', request()->except(['promotions_page', 'coupons_page'])) }}"><x-icon name="sparkles" /><x-t en="Promotions" th="โปรโมชัน" /></a>
    </div>

    <form class="mt-5 rounded-lg border border-zinc-200 bg-white p-4 dark:border-white/10 dark:bg-white/[0.04]" x-data="{ moreFiltersOpen: false }">
        <div class="grid gap-3 lg:grid-cols-[1fr_auto]">
            <label class="text-sm text-zinc-700 dark:text-zinc-300"><x-t en="Event" th="อีเวนต์" />
                <select class="mt-1 w-full rounded-md border border-zinc-200 bg-white px-3 py-2 text-zinc-950 dark:border-white/10 dark:bg-zinc-950 dark:text-white" name="event_id" onchange="this.form.submit()">
                    <option value="" data-i18n-en="All events" data-i18n-th="ทุกอีเวนต์">All events</option>
                    @foreach($events as $event)
                        <option value="{{ $event->id }}" @selected(request('event_id') == $event->id)>{{ $event->name }}</option>
                    @endforeach
                </select>
            </label>
            <div class="flex flex-wrap items-end gap-2">
                @foreach(['type' => request('type'), 'status' => request('status')] as $label => $value)
                    @if(filled($value))
                        <span class="inline-flex items-center gap-1 rounded-md bg-emerald-400/10 px-3 py-2 text-xs font-semibold text-emerald-700 dark:text-emerald-200">{{ ucfirst($label) }}: {{ str_replace('_', ' ', $value) }}</span>
                    @endif
                @endforeach
                <button class="inline-flex items-center gap-2 rounded-md border border-zinc-200 px-3 py-2 text-sm font-semibold text-zinc-800 hover:border-emerald-300 dark:border-white/10 dark:text-zinc-100" type="button" @click="moreFiltersOpen = !moreFiltersOpen">
                    <x-icon name="filter" />
                    <span x-text="TicketFlowLanguage.format(moreFiltersOpen ? { en: 'Hide more filters', th: 'ซ่อนตัวกรองเพิ่มเติม' } : { en: 'Show more filters', th: 'แสดงตัวกรองเพิ่มเติม' })"></span>
                </button>
            </div>
        </div>
        <div class="mt-3 grid gap-3 sm:grid-cols-3" x-show="moreFiltersOpen" x-cloak x-transition>
            <label class="text-sm text-zinc-700 dark:text-zinc-300"><x-t en="Type" th="ประเภท" />
                <select class="mt-1 w-full rounded-md border border-zinc-200 bg-white px-3 py-2 text-zinc-950 dark:border-white/10 dark:bg-zinc-950 dark:text-white" name="type">
                    <option value="" data-i18n-en="All types" data-i18n-th="ทุกประเภท">All types</option>
                    @if($activeTab === 'promotions')
                        <option value="buy_x_get_y" @selected(request('type') === 'buy_x_get_y') data-i18n-en="Buy X get Y" data-i18n-th="ซื้อ X แถม Y">Buy X get Y</option>
                    @endif
                    <option value="fixed" @selected(request('type') === 'fixed') data-i18n-en="Fixed THB" data-i18n-th="ลดเป็นบาท">Fixed THB</option>
                    <option value="percent" @selected(request('type') === 'percent') data-i18n-en="Percent" data-i18n-th="เปอร์เซ็นต์">Percent</option>
                </select>
            </label>
            <label class="text-sm text-zinc-700 dark:text-zinc-300"><x-t en="Status" th="สถานะ" />
                <select class="mt-1 w-full rounded-md border border-zinc-200 bg-white px-3 py-2 text-zinc-950 dark:border-white/10 dark:bg-zinc-950 dark:text-white" name="status">
                    <option value="" data-i18n-en="All statuses" data-i18n-th="ทุกสถานะ">All statuses</option>
                    <option value="active" @selected(request('status') === 'active') data-i18n-en="Active" data-i18n-th="ใช้งาน">Active</option>
                    <option value="inactive" @selected(request('status') === 'inactive') data-i18n-en="Inactive" data-i18n-th="ปิดอยู่">Inactive</option>
                </select>
            </label>
            <div class="flex items-end gap-2">
                <button class="inline-flex flex-1 items-center justify-center gap-2 rounded-md bg-emerald-400 px-4 py-2 font-semibold text-zinc-950"><x-icon name="search" /><x-t en="Filter" th="กรอง" /></button>
                <a class="inline-flex items-center justify-center rounded-md border border-zinc-200 px-3 py-2 text-sm font-semibold text-zinc-700 dark:border-white/10 dark:text-zinc-200" href="{{ $activeTab === 'promotions' ? route('admin.promotions.index') : route('admin.coupons.index') }}"><x-t en="Reset" th="ล้าง" /></a>
            </div>
        </div>
    </form>

    @if($activeTab === 'coupons')
        <section class="mt-6 rounded-lg border border-zinc-200 bg-white dark:border-white/10 dark:bg-white/[0.04]">
            <div class="divide-y divide-white/10">
                @forelse($coupons as $coupon)
                    <div class="interactive-row group flex flex-wrap items-center justify-between gap-4 p-4 dark:bg-white/[0.03]">
                        <a class="click-area-link" href="{{ route('admin.coupons.edit', $coupon) }}" aria-label="Edit coupon {{ $coupon->code }}"></a>
                        <div class="click-area-content">
                            <div class="font-semibold text-zinc-950 group-hover:text-emerald-700 dark:text-white">{{ $coupon->code }}</div>
                            <div class="text-sm text-zinc-600 dark:text-zinc-400">
                                {{ $coupon->event?->name ?? 'All events' }} · {{ $coupon->ticketType?->name ?? 'Any ticket' }} ·
                                <span data-i18n-en="{{ $coupon->discount_scope === 'item' ? 'per item' : 'per order' }}" data-i18n-th="{{ $coupon->discount_scope === 'item' ? 'ต่อใบ' : 'ต่อออเดอร์' }}">{{ $coupon->discount_scope === 'item' ? 'per item' : 'per order' }}</span>
                                · {{ $coupon->discount_type === 'percent' ? $coupon->discount_value.'%' : 'THB '.number_format($coupon->discount_value) }}
                            </div>
                        </div>
                        <div class="click-area-content flex flex-wrap items-center gap-2">
                            <span class="rounded bg-zinc-100 px-3 py-1 text-sm dark:bg-white/10 {{ $coupon->is_active ? 'text-emerald-700 dark:text-emerald-200' : 'text-zinc-600 dark:text-zinc-400' }}" data-i18n-en="{{ $coupon->is_active ? 'active' : 'inactive' }}" data-i18n-th="{{ $coupon->is_active ? 'ใช้งาน' : 'ปิดอยู่' }}">{{ $coupon->is_active ? 'active' : 'inactive' }}</span>
                            <form method="POST" action="{{ route('admin.coupons.destroy', $coupon) }}" onsubmit="return confirm(TicketFlowLanguage.format({ en: 'Delete this coupon?', th: 'ลบคูปองนี้?' }))">
                                @csrf
                                @method('DELETE')
                                <button class="inline-flex items-center gap-2 rounded-md border border-rose-300 px-3 py-2 text-sm font-semibold text-rose-700 dark:border-rose-400/40 dark:text-rose-200"><x-icon name="trash-2" /><x-t en="Delete" th="ลบ" /></button>
                            </form>
                        </div>
                    </div>
                @empty
                    <div class="p-6 text-sm text-zinc-600 dark:text-zinc-400"><x-t en="No coupons found." th="ไม่พบคูปอง" /></div>
                @endforelse
            </div>
            <div class="p-4">{{ $coupons->links() }}</div>
        </section>
    @else
        <section class="mt-6 rounded-lg border border-zinc-200 bg-white dark:border-white/10 dark:bg-white/[0.04]">
            <div class="divide-y divide-white/10">
                @forelse($promotions as $promotion)
                    <div class="interactive-row group flex flex-wrap items-center justify-between gap-4 p-4 dark:bg-white/[0.03]">
                        <a class="click-area-link" href="{{ route('admin.promotions.edit', $promotion) }}" aria-label="Edit promotion {{ $promotion->name }}"></a>
                        <div class="click-area-content">
                            <div class="font-semibold text-zinc-950 group-hover:text-emerald-700 dark:text-white">{{ $promotion->name }}</div>
                            <div class="text-sm text-zinc-600 dark:text-zinc-400">
                                {{ $promotion->event?->name ?? 'All events' }} · {{ $promotion->ticketType?->name ?? 'Any ticket' }} · {{ $promotion->displaySummary() }} ·
                                <span data-i18n-en="{{ $promotion->combines_with_coupons ? 'stacks with coupons' : 'no coupon stacking' }}" data-i18n-th="{{ $promotion->combines_with_coupons ? 'ใช้ร่วมคูปองได้' : 'ไม่ร่วมคูปอง' }}">{{ $promotion->combines_with_coupons ? 'stacks with coupons' : 'no coupon stacking' }}</span>
                            </div>
                        </div>
                        <div class="click-area-content flex flex-wrap items-center gap-2">
                            <span class="rounded bg-zinc-100 px-3 py-1 text-sm dark:bg-white/10 {{ $promotion->is_active ? 'text-emerald-700 dark:text-emerald-200' : 'text-zinc-600 dark:text-zinc-400' }}" data-i18n-en="{{ $promotion->is_active ? 'active' : 'inactive' }}" data-i18n-th="{{ $promotion->is_active ? 'ใช้งาน' : 'ปิดอยู่' }}">{{ $promotion->is_active ? 'active' : 'inactive' }}</span>
                            <form method="POST" action="{{ route('admin.promotions.destroy', $promotion) }}" onsubmit="return confirm(TicketFlowLanguage.format({ en: 'Delete this promotion?', th: 'ลบโปรโมชันนี้?' }))">
                                @csrf
                                @method('DELETE')
                                <button class="inline-flex items-center gap-2 rounded-md border border-rose-300 px-3 py-2 text-sm font-semibold text-rose-700 dark:border-rose-400/40 dark:text-rose-200"><x-icon name="trash-2" /><x-t en="Delete" th="ลบ" /></button>
                            </form>
                        </div>
                    </div>
                @empty
                    <div class="p-6 text-sm text-zinc-600 dark:text-zinc-400"><x-t en="No promotions found." th="ไม่พบโปรโมชัน" /></div>
                @endforelse
            </div>
            <div class="p-4">{{ $promotions->links() }}</div>
        </section>
    @endif
</x-layouts.app>
