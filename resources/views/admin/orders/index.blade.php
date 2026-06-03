<x-layouts.app title="Orders">
    <h1 class="text-3xl font-semibold text-zinc-950 dark:text-white">Orders / ออเดอร์</h1>
    <form class="mt-5 rounded-lg border border-zinc-200 bg-white p-4 dark:border-white/10 dark:bg-white/[0.04]" x-data="{ moreFiltersOpen: false }">
        <div class="grid gap-3 lg:grid-cols-[1fr_auto]">
            <label class="text-sm text-zinc-700 dark:text-zinc-300">Event / อีเวนต์
                <select class="mt-1 w-full rounded-md border border-zinc-200 bg-white px-3 py-2 text-zinc-950 dark:border-white/10 dark:bg-zinc-950 dark:text-white" name="event_id" onchange="this.form.submit()">
                    <option value="">All events / ทุกอีเวนต์</option>
                    @foreach($events as $event)
                        <option value="{{ $event->id }}" @selected(request('event_id') == $event->id)>{{ $event->name }}</option>
                    @endforeach
                </select>
            </label>
            <div class="flex flex-wrap items-end gap-2">
                @foreach(['ticket_type_id' => request('ticket_type_id'), 'status' => request('status')] as $label => $value)
                    @if(filled($value))
                        <span class="inline-flex items-center gap-1 rounded-md bg-emerald-400/10 px-3 py-2 text-xs font-semibold text-emerald-700 dark:text-emerald-200">{{ $label === 'ticket_type_id' ? 'Ticket type' : 'Status' }}: {{ str_replace('_', ' ', $value) }}</span>
                    @endif
                @endforeach
                <button class="inline-flex items-center gap-2 rounded-md border border-zinc-200 px-3 py-2 text-sm font-semibold text-zinc-800 hover:border-emerald-300 dark:border-white/10 dark:text-zinc-100" type="button" @click="moreFiltersOpen = !moreFiltersOpen">
                    <x-icon name="filter" />
                    <span x-text="TicketFlowLanguage.format(moreFiltersOpen ? { en: 'Hide more filters', th: 'ซ่อนตัวกรองเพิ่มเติม' } : { en: 'Show more filters', th: 'แสดงตัวกรองเพิ่มเติม' })"></span>
                </button>
            </div>
        </div>
        <div class="mt-3 grid gap-3 sm:grid-cols-3" x-show="moreFiltersOpen" x-cloak x-transition>
            <label class="text-sm text-zinc-700 dark:text-zinc-300">Ticket type / ประเภทตั๋ว
                <select class="mt-1 w-full rounded-md border border-zinc-200 bg-white px-3 py-2 text-zinc-950 dark:border-white/10 dark:bg-zinc-950 dark:text-white" name="ticket_type_id">
                    <option value="">All ticket types / ทุกประเภทตั๋ว</option>
                    @foreach($ticketTypes as $ticketType)
                        <option value="{{ $ticketType->id }}" @selected(request('ticket_type_id') == $ticketType->id)>{{ $ticketType->event?->name }} - {{ $ticketType->name }}</option>
                    @endforeach
                </select>
            </label>
            <label class="text-sm text-zinc-700 dark:text-zinc-300">Status / สถานะ
                <select class="mt-1 w-full rounded-md border border-zinc-200 bg-white px-3 py-2 text-zinc-950 dark:border-white/10 dark:bg-zinc-950 dark:text-white" name="status">
                    <option value="">All statuses / ทุกสถานะ</option>
                    @foreach(['pending', 'approved', 'rejected', 'cancelled', 'refunded'] as $status)
                        <option value="{{ $status }}" @selected(request('status') === $status)>{{ str_replace('_', ' ', $status) }}</option>
                    @endforeach
                </select>
            </label>
            <div class="flex items-end gap-2">
                <button class="inline-flex flex-1 items-center justify-center gap-2 rounded-md bg-emerald-400 px-4 py-2 font-semibold text-zinc-950"><x-icon name="search" />Filter / กรอง</button>
                <a class="inline-flex items-center justify-center rounded-md border border-zinc-200 px-3 py-2 text-sm font-semibold text-zinc-700 dark:border-white/10 dark:text-zinc-200" href="{{ route('admin.orders.index') }}">Reset</a>
            </div>
        </div>
    </form>
    <div class="mt-6 rounded-lg border border-zinc-200 dark:border-white/10 bg-white dark:bg-white/[0.04]">
        <div class="divide-y divide-white/10">
            @forelse($orders as $order)
                @php
                    $eventNames = $order->items->pluck('event.name')->filter()->unique();
                    $ticketSummary = $order->items->map(fn ($item) => $item->ticketType?->name.' x '.$item->quantity)->filter()->join(', ');
                @endphp
                <div class="interactive-row group flex flex-wrap items-center justify-between gap-4 p-4 dark:bg-white/[0.03]">
                    <a class="click-area-link" href="{{ route('admin.orders.show', $order) }}" aria-label="Open order {{ $order->order_number }}"></a>
                    <div class="click-area-content">
                        <div class="font-semibold text-zinc-950 group-hover:text-emerald-700 dark:text-white">{{ $order->order_number }}</div>
                        <div class="text-sm text-zinc-600 dark:text-zinc-400">{{ $order->customer_name }} · {{ $order->customer_phone }} · THB {{ number_format($order->total_thb) }}</div>
                        <div class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">{{ $eventNames->join(', ') }} @if($ticketSummary) · {{ $ticketSummary }} @endif</div>
                    </div>
                    <div class="click-area-content flex flex-wrap items-center gap-2">
                        <x-status-badge :status="$order->status" />
                        @if(in_array($order->status, ['cancelled', 'refunded'], true))
                            <form method="POST" action="{{ route('admin.orders.destroy', $order) }}" onsubmit="return confirm('Delete this order and its tickets? / ลบออเดอร์และตั๋วทั้งหมด?')">
                                @csrf
                                @method('DELETE')
                                <button class="inline-flex items-center gap-2 rounded-md border border-rose-300 px-3 py-2 text-sm font-semibold text-rose-700 dark:border-rose-400/40 dark:text-rose-200"><x-icon name="trash-2" />Delete / ลบ</button>
                            </form>
                        @endif
                    </div>
                </div>
            @empty
                <div class="p-6 text-zinc-600 dark:text-zinc-400">No orders match these filters. / ไม่พบออเดอร์ตามตัวกรองนี้</div>
            @endforelse
        </div>
        <div class="p-4">{{ $orders->links() }}</div>
    </div>
</x-layouts.app>
