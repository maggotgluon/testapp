<x-layouts.app :title="$promotion->exists ? 'Edit promotion' : 'New promotion'">
    <form
        method="POST"
        action="{{ $promotion->exists ? route('admin.promotions.update', $promotion) : route('admin.promotions.store') }}"
        class="mx-auto max-w-3xl rounded-lg border border-zinc-200 dark:border-white/10 bg-white dark:bg-white/[0.04] p-6"
        x-data="{
            type: @js(old('promotion_type', $promotion->promotion_type ?: 'buy_x_get_y')),
            scope: @js(old('discount_scope', $promotion->discount_scope ?: 'order')),
            buyQuantity: Number(@js(old('buy_quantity', $promotion->buy_quantity ?: 2))) || 2,
            getQuantity: Number(@js(old('get_quantity', $promotion->get_quantity ?: 1))) || 1,
            minQuantity: Number(@js(old('min_quantity', $promotion->min_quantity))) || 0,
            discountValue: Number(@js(old('discount_value', $promotion->discount_value))) || 0,
            maxDiscount: Number(@js(old('max_discount_thb', $promotion->max_discount_thb))) || 0,
            usageLimit: Number(@js(old('usage_limit', $promotion->usage_limit))) || 0,
            number(value, fallback = 0) {
                return Math.max(fallback, Number(value) || fallback);
            },
            scenarioTitle() {
                if (this.type === 'buy_x_get_y') {
                    return `Buy ${this.number(this.buyQuantity, 1)} get ${this.number(this.getQuantity, 1)} free / ซื้อ ${this.number(this.buyQuantity, 1)} แถม ${this.number(this.getQuantity, 1)}`;
                }

                if (this.type === 'percent') {
                    return `${this.number(this.discountValue, 1)}% discount / ลด ${this.number(this.discountValue, 1)}%`;
                }

                return `THB ${this.number(this.discountValue, 1).toLocaleString()} discount / ลด THB ${this.number(this.discountValue, 1).toLocaleString()}`;
            },
            scenarioText() {
                const min = this.number(this.minQuantity, 0);
                const limit = this.number(this.usageLimit, 0);
                const cap = this.number(this.maxDiscount, 0);
                const scopeText = this.scope === 'item' ? 'per eligible ticket / ต่อใบตั๋วที่เข้าเงื่อนไข' : 'once per order / ต่อออเดอร์';
                const notes = [];

                if (this.type === 'buy_x_get_y') {
                    const paid = this.number(this.buyQuantity, 1);
                    const free = this.number(this.getQuantity, 1);
                    notes.push(`When a customer has ${paid + free} eligible tickets, ${free} ticket${free === 1 ? '' : 's'} will be free. / เมื่อลูกค้าซื้อตั๋วที่เข้าเงื่อนไข ${paid + free} ใบ ระบบจะแถม ${free} ใบ`);
                } else {
                    notes.push(`Discount is calculated ${scopeText}. / ระบบจะคิดส่วนลดแบบ ${scopeText}`);
                    if (min > 0) {
                        notes.push(`Customer needs at least ${min} eligible ticket${min === 1 ? '' : 's'}. / ต้องมีตั๋วที่เข้าเงื่อนไขอย่างน้อย ${min} ใบ`);
                    }
                }

                if (cap > 0) {
                    notes.push(`Maximum discount is THB ${cap.toLocaleString()}. / ส่วนลดสูงสุด THB ${cap.toLocaleString()}`);
                }

                if (limit > 0) {
                    notes.push(`Can be used ${limit.toLocaleString()} time${limit === 1 ? '' : 's'}. / ใช้ได้ ${limit.toLocaleString()} ครั้ง`);
                }

                return notes.join(' ');
            },
        }"
    >
        @csrf
        @if($promotion->exists) @method('PUT') @endif
        <h1 class="inline-flex items-center gap-2 text-2xl font-semibold text-zinc-950 dark:text-white"><x-icon name="sparkles" class="h-6 w-6 text-emerald-500" />{{ $promotion->exists ? 'Edit promotion / แก้ไขโปรโมชัน' : 'New promotion / เพิ่มโปรโมชัน' }}</h1>
        <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">Create automatic discounts such as buy 2 get 1 free, buy 4 get THB 200 off, or 10% off for a selected ticket. / สร้างส่วนลดอัตโนมัติ เช่น ซื้อ 2 แถม 1 หรือซื้อครบจำนวนแล้วรับส่วนลด</p>
        <div class="mt-5 grid gap-4 sm:grid-cols-2">
            <label class="text-sm text-zinc-700 dark:text-zinc-300 sm:col-span-2">Name / ชื่อโปรโมชัน<input class="mt-1 w-full rounded-md border border-zinc-200 dark:border-white/10 bg-white dark:bg-zinc-950 px-3 py-2 text-zinc-950 dark:text-white" name="name" value="{{ old('name', $promotion->name) }}" required></label>
            <label class="text-sm text-zinc-700 dark:text-zinc-300 sm:col-span-2">Description / รายละเอียด<textarea class="mt-1 w-full rounded-md border border-zinc-200 dark:border-white/10 bg-white dark:bg-zinc-950 px-3 py-2 text-zinc-950 dark:text-white" name="description" rows="3">{{ old('description', $promotion->description) }}</textarea></label>
            <label class="text-sm text-zinc-700 dark:text-zinc-300 sm:col-span-2">Event / อีเวนต์<select class="mt-1 w-full rounded-md border border-zinc-200 dark:border-white/10 bg-white dark:bg-zinc-950 px-3 py-2 text-zinc-950 dark:text-white" name="event_id">
                <option value="">All events / ทุกอีเวนต์</option>
                @foreach($events as $event)
                    <option value="{{ $event->id }}" @selected((string) old('event_id', $promotion->event_id) === (string) $event->id)>{{ $event->name }}</option>
                @endforeach
            </select></label>
            <label class="text-sm text-zinc-700 dark:text-zinc-300 sm:col-span-2">Apply to ticket type / ใช้กับประเภทตั๋ว<select class="mt-1 w-full rounded-md border border-zinc-200 dark:border-white/10 bg-white dark:bg-zinc-950 px-3 py-2 text-zinc-950 dark:text-white" name="ticket_type_id">
                <option value="">Any ticket type / ทุกประเภทตั๋ว</option>
                @foreach($events as $event)
                    @foreach($event->ticketTypes as $ticketType)
                        <option value="{{ $ticketType->id }}" @selected((string) old('ticket_type_id', $promotion->ticket_type_id) === (string) $ticketType->id)>{{ $event->name }} - {{ $ticketType->name }}</option>
                    @endforeach
                @endforeach
            </select></label>
            <label class="text-sm text-zinc-700 dark:text-zinc-300">Promotion type / รูปแบบโปรโมชัน<select class="mt-1 w-full rounded-md border border-zinc-200 dark:border-white/10 bg-white dark:bg-zinc-950 px-3 py-2 text-zinc-950 dark:text-white" name="promotion_type" x-model="type">
                <option value="buy_x_get_y" @selected(old('promotion_type', $promotion->promotion_type ?: 'buy_x_get_y') === 'buy_x_get_y')>Buy X get Y free / ซื้อ X แถม Y</option>
                <option value="fixed" @selected(old('promotion_type', $promotion->promotion_type) === 'fixed')>Fixed THB discount / ลดเป็นบาท</option>
                <option value="percent" @selected(old('promotion_type', $promotion->promotion_type) === 'percent')>Percent discount / ลดเป็นเปอร์เซ็นต์</option>
            </select></label>
            <div class="rounded-md border border-emerald-400/30 bg-emerald-400/10 p-4 text-sm text-emerald-950 dark:text-emerald-50 sm:col-span-2">
                <div class="inline-flex items-center gap-2 font-semibold"><x-icon name="sparkles" />Promotion scenario / ตัวอย่างโปรโมชัน</div>
                <p class="mt-2 font-semibold" x-text="scenarioTitle()"></p>
                <p class="mt-1 text-emerald-800 dark:text-emerald-100" x-text="scenarioText()"></p>
            </div>
            <label class="text-sm text-zinc-700 dark:text-zinc-300" x-show="type !== 'buy_x_get_y'" x-cloak>Discount scope / วิธีคิดส่วนลด<select class="mt-1 w-full rounded-md border border-zinc-200 dark:border-white/10 bg-white dark:bg-zinc-950 px-3 py-2 text-zinc-950 dark:text-white" name="discount_scope" x-model="scope">
                <option value="order" @selected(old('discount_scope', $promotion->discount_scope ?: 'order') === 'order')>Once per order / ต่อออเดอร์</option>
                <option value="item" @selected(old('discount_scope', $promotion->discount_scope) === 'item')>Per ticket item / ต่อใบตั๋ว</option>
            </select></label>
            <label class="text-sm text-zinc-700 dark:text-zinc-300" x-show="type === 'buy_x_get_y'" x-cloak>Buy quantity / จำนวนที่ซื้อ<input class="mt-1 w-full rounded-md border border-zinc-200 dark:border-white/10 bg-white dark:bg-zinc-950 px-3 py-2 text-zinc-950 dark:text-white" type="number" name="buy_quantity" value="{{ old('buy_quantity', $promotion->buy_quantity ?: 2) }}" x-model.number="buyQuantity" min="1"></label>
            <label class="text-sm text-zinc-700 dark:text-zinc-300" x-show="type === 'buy_x_get_y'" x-cloak>Free quantity / จำนวนที่แถม<input class="mt-1 w-full rounded-md border border-zinc-200 dark:border-white/10 bg-white dark:bg-zinc-950 px-3 py-2 text-zinc-950 dark:text-white" type="number" name="get_quantity" value="{{ old('get_quantity', $promotion->get_quantity ?: 1) }}" x-model.number="getQuantity" min="1"></label>
            <label class="text-sm text-zinc-700 dark:text-zinc-300" x-show="type !== 'buy_x_get_y'" x-cloak>Minimum ticket quantity / จำนวนขั้นต่ำ<input class="mt-1 w-full rounded-md border border-zinc-200 dark:border-white/10 bg-white dark:bg-zinc-950 px-3 py-2 text-zinc-950 dark:text-white" type="number" name="min_quantity" value="{{ old('min_quantity', $promotion->min_quantity) }}" x-model.number="minQuantity" min="1"></label>
            <label class="text-sm text-zinc-700 dark:text-zinc-300" x-show="type !== 'buy_x_get_y'" x-cloak><span x-text="type === 'percent' ? 'Discount percent / เปอร์เซ็นต์ส่วนลด' : 'Discount THB / จำนวนเงินส่วนลด'"></span><input class="mt-1 w-full rounded-md border border-zinc-200 dark:border-white/10 bg-white dark:bg-zinc-950 px-3 py-2 text-zinc-950 dark:text-white" type="number" name="discount_value" value="{{ old('discount_value', $promotion->discount_value) }}" x-model.number="discountValue" min="1"></label>
            <label class="text-sm text-zinc-700 dark:text-zinc-300">Maximum discount THB / ส่วนลดสูงสุด<input class="mt-1 w-full rounded-md border border-zinc-200 dark:border-white/10 bg-white dark:bg-zinc-950 px-3 py-2 text-zinc-950 dark:text-white" type="number" name="max_discount_thb" value="{{ old('max_discount_thb', $promotion->max_discount_thb) }}" x-model.number="maxDiscount" min="1"></label>
            <label class="text-sm text-zinc-700 dark:text-zinc-300">Usage limit / จำกัดจำนวนใช้<input class="mt-1 w-full rounded-md border border-zinc-200 dark:border-white/10 bg-white dark:bg-zinc-950 px-3 py-2 text-zinc-950 dark:text-white" type="number" name="usage_limit" value="{{ old('usage_limit', $promotion->usage_limit) }}" x-model.number="usageLimit" min="1"></label>
            <label class="text-sm text-zinc-700 dark:text-zinc-300">Starts at / เริ่มใช้<input class="mt-1 w-full rounded-md border border-zinc-200 dark:border-white/10 bg-white dark:bg-zinc-950 px-3 py-2 text-zinc-950 dark:text-white" type="datetime-local" name="starts_at" value="{{ old('starts_at', $promotion->starts_at?->format('Y-m-d\TH:i')) }}" data-date-start="promotion" data-default-days="7"></label>
            <label class="text-sm text-zinc-700 dark:text-zinc-300">Expires at / หมดอายุ<input class="mt-1 w-full rounded-md border border-zinc-200 dark:border-white/10 bg-white dark:bg-zinc-950 px-3 py-2 text-zinc-950 dark:text-white" type="datetime-local" name="expires_at" value="{{ old('expires_at', $promotion->expires_at?->format('Y-m-d\TH:i')) }}" data-date-end="promotion"></label>
            <label class="flex items-center gap-2 text-sm text-zinc-700 dark:text-zinc-300"><input class="rounded border-zinc-200 dark:border-white/10 bg-white dark:bg-zinc-950" type="checkbox" name="combines_with_coupons" value="1" @checked(old('combines_with_coupons', $promotion->combines_with_coupons ?? true))> Can combine with coupons / ใช้ร่วมกับคูปองได้</label>
            <label class="flex items-center gap-2 text-sm text-zinc-700 dark:text-zinc-300"><input class="rounded border-zinc-200 dark:border-white/10 bg-white dark:bg-zinc-950" type="checkbox" name="is_active" value="1" @checked(old('is_active', $promotion->is_active ?? true))> Active / เปิดใช้งาน</label>
            <label class="flex items-center gap-2 text-sm text-zinc-700 dark:text-zinc-300"><input class="rounded border-zinc-200 dark:border-white/10 bg-white dark:bg-zinc-950" type="checkbox" name="show_on_event_page" value="1" @checked(old('show_on_event_page', $promotion->show_on_event_page ?? true))> Show promotion hint on checkout / แสดงคำใบ้โปรโมชันในหน้าซื้อ</label>
        </div>
        <button class="mt-6 inline-flex items-center gap-2 rounded-md bg-emerald-400 px-4 py-3 font-semibold text-zinc-950"><x-icon name="save" />Save promotion / บันทึกโปรโมชัน</button>
    </form>
</x-layouts.app>
