<x-layouts.app :title="$coupon->exists ? 'Edit coupon' : 'New coupon'">
    <form method="POST" action="{{ $coupon->exists ? route('admin.coupons.update', $coupon) : route('admin.coupons.store') }}" class="mx-auto max-w-3xl rounded-lg border border-zinc-200 dark:border-white/10 bg-white dark:bg-white/[0.04] p-6">
        @csrf
        @if($coupon->exists) @method('PUT') @endif
        <h1 class="inline-flex items-center gap-2 text-2xl font-semibold text-zinc-950 dark:text-white"><x-icon name="tag" class="h-6 w-6 text-emerald-500" />{{ $coupon->exists ? 'Edit coupon / แก้ไขคูปอง' : 'New coupon / เพิ่มคูปอง' }}</h1>
        <div class="mt-5 grid gap-4 sm:grid-cols-2">
            <label class="text-sm text-zinc-700 dark:text-zinc-300">Code / รหัสคูปอง<input class="mt-1 w-full rounded-md border border-zinc-200 dark:border-white/10 bg-white dark:bg-zinc-950 px-3 py-2 uppercase text-zinc-950 dark:text-white" name="code" value="{{ old('code', $coupon->code) }}" required></label>
            <label class="text-sm text-zinc-700 dark:text-zinc-300">Name / ชื่อ<input class="mt-1 w-full rounded-md border border-zinc-200 dark:border-white/10 bg-white dark:bg-zinc-950 px-3 py-2 text-zinc-950 dark:text-white" name="name" value="{{ old('name', $coupon->name) }}"></label>
            <label class="text-sm text-zinc-700 dark:text-zinc-300 sm:col-span-2">Event / อีเวนต์<select class="mt-1 w-full rounded-md border border-zinc-200 dark:border-white/10 bg-white dark:bg-zinc-950 px-3 py-2 text-zinc-950 dark:text-white" name="event_id">
                <option value="">All events / ทุกอีเวนต์</option>
                @foreach($events as $event)
                    <option value="{{ $event->id }}" @selected((string) old('event_id', $coupon->event_id) === (string) $event->id)>{{ $event->name }}</option>
                @endforeach
            </select></label>
            <label class="text-sm text-zinc-700 dark:text-zinc-300 sm:col-span-2">Apply to ticket type / ใช้กับประเภทตั๋ว<select class="mt-1 w-full rounded-md border border-zinc-200 dark:border-white/10 bg-white dark:bg-zinc-950 px-3 py-2 text-zinc-950 dark:text-white" name="ticket_type_id">
                <option value="">Any ticket type / ทุกประเภทตั๋ว</option>
                @foreach($events as $event)
                    @foreach($event->ticketTypes as $ticketType)
                        <option value="{{ $ticketType->id }}" @selected((string) old('ticket_type_id', $coupon->ticket_type_id) === (string) $ticketType->id)>{{ $event->name }} - {{ $ticketType->name }}</option>
                    @endforeach
                @endforeach
            </select></label>
            <label class="text-sm text-zinc-700 dark:text-zinc-300">Discount type / ประเภทส่วนลด<select class="mt-1 w-full rounded-md border border-zinc-200 dark:border-white/10 bg-white dark:bg-zinc-950 px-3 py-2 text-zinc-950 dark:text-white" name="discount_type">
                <option value="fixed" @selected(old('discount_type', $coupon->discount_type ?: 'fixed') === 'fixed')>Fixed THB / ลดเป็นบาท</option>
                <option value="percent" @selected(old('discount_type', $coupon->discount_type) === 'percent')>Percent / เปอร์เซ็นต์</option>
            </select></label>
            <label class="text-sm text-zinc-700 dark:text-zinc-300">Discount scope / วิธีคิดส่วนลด<select class="mt-1 w-full rounded-md border border-zinc-200 dark:border-white/10 bg-white dark:bg-zinc-950 px-3 py-2 text-zinc-950 dark:text-white" name="discount_scope">
                <option value="order" @selected(old('discount_scope', $coupon->discount_scope ?: 'order') === 'order')>Once per order / ต่อออเดอร์</option>
                <option value="item" @selected(old('discount_scope', $coupon->discount_scope) === 'item')>Per ticket item / ต่อใบตั๋ว</option>
            </select></label>
            <label class="text-sm text-zinc-700 dark:text-zinc-300">Discount value / มูลค่าส่วนลด<input class="mt-1 w-full rounded-md border border-zinc-200 dark:border-white/10 bg-white dark:bg-zinc-950 px-3 py-2 text-zinc-950 dark:text-white" type="number" name="discount_value" value="{{ old('discount_value', $coupon->discount_value) }}" required></label>
            <label class="text-sm text-zinc-700 dark:text-zinc-300">Usage limit / จำกัดจำนวนใช้<input class="mt-1 w-full rounded-md border border-zinc-200 dark:border-white/10 bg-white dark:bg-zinc-950 px-3 py-2 text-zinc-950 dark:text-white" type="number" name="usage_limit" value="{{ old('usage_limit', $coupon->usage_limit) }}"></label>
            <label class="text-sm text-zinc-700 dark:text-zinc-300">Starts at / เริ่มใช้<input class="mt-1 w-full rounded-md border border-zinc-200 dark:border-white/10 bg-white dark:bg-zinc-950 px-3 py-2 text-zinc-950 dark:text-white" type="datetime-local" name="starts_at" value="{{ old('starts_at', $coupon->starts_at?->format('Y-m-d\TH:i')) }}" data-date-start="coupon" data-default-days="7"></label>
            <label class="text-sm text-zinc-700 dark:text-zinc-300">Expires at / หมดอายุ<input class="mt-1 w-full rounded-md border border-zinc-200 dark:border-white/10 bg-white dark:bg-zinc-950 px-3 py-2 text-zinc-950 dark:text-white" type="datetime-local" name="expires_at" value="{{ old('expires_at', $coupon->expires_at?->format('Y-m-d\TH:i')) }}" data-date-end="coupon"></label>
            <label class="flex items-center gap-2 text-sm text-zinc-700 dark:text-zinc-300"><input class="rounded border-zinc-200 dark:border-white/10 bg-white dark:bg-zinc-950" type="checkbox" name="is_active" value="1" @checked(old('is_active', $coupon->is_active ?? true))> Active / เปิดใช้งาน</label>
            <label class="flex items-center gap-2 text-sm text-zinc-700 dark:text-zinc-300"><input class="rounded border-zinc-200 dark:border-white/10 bg-white dark:bg-zinc-950" type="checkbox" name="show_on_checkout" value="1" @checked(old('show_on_checkout', $coupon->show_on_checkout ?? true))> Show code on checkout / แสดงรหัสในหน้าซื้อ</label>
        </div>
        <button class="mt-6 inline-flex items-center gap-2 rounded-md bg-emerald-400 px-4 py-3 font-semibold text-zinc-950"><x-icon name="save" />Save coupon / บันทึกคูปอง</button>
    </form>
</x-layouts.app>
