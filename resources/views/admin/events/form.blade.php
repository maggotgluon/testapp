<x-layouts.app :title="$event->exists ? 'Edit event' : 'New event'">
    <form method="POST" enctype="multipart/form-data" action="{{ $event->exists ? route('admin.events.update', $event) : route('admin.events.store') }}" class="grid gap-6 lg:grid-cols-[1fr_.8fr]">
        @csrf
        @if($event->exists) @method('PUT') @endif
        <section class="rounded-lg border border-zinc-200 dark:border-white/10 bg-white dark:bg-white/[0.04] p-6">
            <h1 class="text-2xl font-semibold text-zinc-950 dark:text-white">{{ $event->exists ? 'Edit event / แก้ไขอีเวนต์' : 'New event / เพิ่มอีเวนต์' }}</h1>
            <div class="mt-5 grid gap-4 sm:grid-cols-2">
                <label class="text-sm text-zinc-700 dark:text-zinc-300 sm:col-span-2">Event name / ชื่ออีเวนต์<input class="mt-1 w-full rounded-md border border-zinc-200 dark:border-white/10 bg-white dark:bg-zinc-950 px-3 py-2 text-zinc-950 dark:text-white" name="name" value="{{ old('name', $event->name) }}" required></label>
                <label class="text-sm text-zinc-700 dark:text-zinc-300 sm:col-span-2">Description / รายละเอียด <span class="text-xs text-zinc-500">safe HTML allowed / ใช้ HTML ที่ปลอดภัยได้</span><textarea class="mt-1 w-full rounded-md border border-zinc-200 dark:border-white/10 bg-white dark:bg-zinc-950 px-3 py-2 text-zinc-950 dark:text-white" name="description" rows="6">{{ old('description', $event->description) }}</textarea><span class="mt-1 block text-xs text-zinc-500">Allowed tags: p, br, strong, em, u, ul, ol, li, a, h2, h3, blockquote.</span></label>
                <label class="text-sm text-zinc-700 dark:text-zinc-300 sm:col-span-2">Social snippet / ข้อความตัวอย่างเวลาแชร์<textarea class="mt-1 w-full rounded-md border border-zinc-200 dark:border-white/10 bg-white dark:bg-zinc-950 px-3 py-2 text-zinc-950 dark:text-white" name="social_description" rows="2" maxlength="500">{{ old('social_description', $event->social_description) }}</textarea></label>
                <label class="text-sm text-zinc-700 dark:text-zinc-300">Venue / สถานที่<input class="mt-1 w-full rounded-md border border-zinc-200 dark:border-white/10 bg-white dark:bg-zinc-950 px-3 py-2 text-zinc-950 dark:text-white" name="venue" value="{{ old('venue', $event->venue) }}" required></label>
                <label class="text-sm text-zinc-700 dark:text-zinc-300">Location / ที่ตั้ง<input class="mt-1 w-full rounded-md border border-zinc-200 dark:border-white/10 bg-white dark:bg-zinc-950 px-3 py-2 text-zinc-950 dark:text-white" name="location" value="{{ old('location', $event->location) }}"></label>
                <label class="text-sm text-zinc-700 dark:text-zinc-300">Google Maps link / ลิงก์ Google Maps<input class="mt-1 w-full rounded-md border border-zinc-200 dark:border-white/10 bg-white dark:bg-zinc-950 px-3 py-2 text-zinc-950 dark:text-white" name="location_url" value="{{ old('location_url', $event->location_url) }}" placeholder="https://maps.app.goo.gl/..."></label>
                <label class="text-sm text-zinc-700 dark:text-zinc-300">Hosted by / ผู้จัด<input class="mt-1 w-full rounded-md border border-zinc-200 dark:border-white/10 bg-white dark:bg-zinc-950 px-3 py-2 text-zinc-950 dark:text-white" name="hosted_by" value="{{ old('hosted_by', $event->hosted_by) }}"></label>
                <label class="text-sm text-zinc-700 dark:text-zinc-300">Host link / ลิงก์ผู้จัด<input class="mt-1 w-full rounded-md border border-zinc-200 dark:border-white/10 bg-white dark:bg-zinc-950 px-3 py-2 text-zinc-950 dark:text-white" name="hosted_by_url" value="{{ old('hosted_by_url', $event->hosted_by_url) }}" placeholder="https://..."></label>
                <label class="text-sm text-zinc-700 dark:text-zinc-300">Event poster 4:5 / โปสเตอร์อีเวนต์ 4:5<input class="mt-1 w-full rounded-md border border-zinc-200 dark:border-white/10 bg-white dark:bg-zinc-950 px-3 py-2 text-zinc-950 dark:text-white" type="file" name="poster" accept="image/*"></label>
                <label class="text-sm text-zinc-700 dark:text-zinc-300">Ticket image 4:5 / รูปตั๋ว 4:5<input class="mt-1 w-full rounded-md border border-zinc-200 dark:border-white/10 bg-white dark:bg-zinc-950 px-3 py-2 text-zinc-950 dark:text-white" type="file" name="ticket_image" accept="image/*"></label>
                <label class="text-sm text-zinc-700 dark:text-zinc-300 sm:col-span-2">Social share image / รูปสำหรับแชร์โซเชียล<input class="mt-1 w-full rounded-md border border-zinc-200 dark:border-white/10 bg-white dark:bg-zinc-950 px-3 py-2 text-zinc-950 dark:text-white" type="file" name="social_image" accept="image/*"></label>
                <label class="text-sm text-zinc-700 dark:text-zinc-300">Starts at / เริ่มงาน<input class="mt-1 w-full rounded-md border border-zinc-200 dark:border-white/10 bg-white dark:bg-zinc-950 px-3 py-2 text-zinc-950 dark:text-white" type="datetime-local" name="starts_at" value="{{ old('starts_at', $event->starts_at?->format('Y-m-d\TH:i')) }}" required></label>
                <label class="text-sm text-zinc-700 dark:text-zinc-300">Ends at / จบงาน<input class="mt-1 w-full rounded-md border border-zinc-200 dark:border-white/10 bg-white dark:bg-zinc-950 px-3 py-2 text-zinc-950 dark:text-white" type="datetime-local" name="ends_at" value="{{ old('ends_at', $event->ends_at?->format('Y-m-d\TH:i')) }}" required></label>
                <label class="flex items-center gap-2 text-sm text-zinc-700 dark:text-zinc-300"><input class="rounded border-zinc-200 dark:border-white/10 bg-white dark:bg-zinc-950" type="checkbox" name="is_published" value="1" @checked(old('is_published', $event->is_published ?? true))> Published / เผยแพร่</label>
                <label class="flex items-center gap-2 text-sm text-zinc-700 dark:text-zinc-300"><input class="rounded border-zinc-200 dark:border-white/10 bg-white dark:bg-zinc-950" type="checkbox" name="show_countdown" value="1" @checked(old('show_countdown', $event->show_countdown ?? false))> Show countdown / แสดงเวลานับถอยหลัง</label>
            </div>
            @php
                $banks = collect(config('thai_banks'))->map(fn ($bank) => $bank + ['logo_url' => asset($bank['logo'])])->values();
                $selectedBank = old('bank_name', $event->bank_name);
                $selectedBankExists = $selectedBank && $banks->contains('name', $selectedBank);
            @endphp
            <div class="mt-6 border-t border-zinc-200 dark:border-white/10 pt-6" x-data="{ bank: @js($selectedBank), banks: @js($banks) }">
                <h2 class="text-xl font-semibold text-zinc-950 dark:text-white">Payment accounts / บัญชีรับชำระเงิน</h2>
                <div class="mt-4 grid gap-4 sm:grid-cols-2">
                    <label class="text-sm text-zinc-700 dark:text-zinc-300">Bank name / ชื่อธนาคาร
                        <select class="mt-1 w-full rounded-md border border-zinc-200 dark:border-white/10 bg-white dark:bg-zinc-950 px-3 py-2 text-zinc-950 dark:text-white" name="bank_name" x-model="bank">
                            <option value="">Select bank / เลือกธนาคาร</option>
                            @if($selectedBank && ! $selectedBankExists)
                                <option value="{{ $selectedBank }}">{{ $selectedBank }}</option>
                            @endif
                            @foreach($banks as $bank)
                                <option value="{{ $bank['name'] }}">{{ $bank['name'] }} / {{ $bank['thai_name'] }}</option>
                            @endforeach
                        </select>
                        <template x-if="banks.find((item) => item.name === bank)">
                            <div class="mt-2 flex items-center gap-2 rounded-md border border-zinc-200 dark:border-white/10 bg-zinc-50 dark:bg-zinc-900 p-2">
                                <img class="h-8 w-8 rounded-md" :src="banks.find((item) => item.name === bank).logo_url" :alt="banks.find((item) => item.name === bank).name">
                                <span class="text-sm font-medium text-zinc-950 dark:text-white" x-text="`${banks.find((item) => item.name === bank).name} / ${banks.find((item) => item.name === bank).thai_name}`"></span>
                            </div>
                        </template>
                    </label>
                    <label class="text-sm text-zinc-700 dark:text-zinc-300">Bank account name / ชื่อบัญชี<input class="mt-1 w-full rounded-md border border-zinc-200 dark:border-white/10 bg-white dark:bg-zinc-950 px-3 py-2 text-zinc-950 dark:text-white" name="bank_account_name" value="{{ old('bank_account_name', $event->bank_account_name) }}"></label>
                    <label class="text-sm text-zinc-700 dark:text-zinc-300">Bank account number / เลขบัญชี<input class="mt-1 w-full rounded-md border border-zinc-200 dark:border-white/10 bg-white dark:bg-zinc-950 px-3 py-2 text-zinc-950 dark:text-white" name="bank_account_number" value="{{ old('bank_account_number', $event->bank_account_number) }}"></label>
                    <label class="text-sm text-zinc-700 dark:text-zinc-300">QR payment account name / ชื่อบัญชี QR<input class="mt-1 w-full rounded-md border border-zinc-200 dark:border-white/10 bg-white dark:bg-zinc-950 px-3 py-2 text-zinc-950 dark:text-white" name="qr_payment_account_name" value="{{ old('qr_payment_account_name', $event->qr_payment_account_name) }}"></label>
                    <label class="text-sm text-zinc-700 dark:text-zinc-300">QR payment account / PromptPay / บัญชี QR หรือพร้อมเพย์<input class="mt-1 w-full rounded-md border border-zinc-200 dark:border-white/10 bg-white dark:bg-zinc-950 px-3 py-2 text-zinc-950 dark:text-white" name="qr_payment_account" value="{{ old('qr_payment_account', $event->qr_payment_account) }}"></label>
                    <label class="text-sm text-zinc-700 dark:text-zinc-300">QR payment image / รูป QR ชำระเงิน<input class="mt-1 w-full rounded-md border border-zinc-200 dark:border-white/10 bg-white dark:bg-zinc-950 px-3 py-2 text-zinc-950 dark:text-white" type="file" name="qr_payment_image" accept="image/*"></label>
                    <label class="text-sm text-zinc-700 dark:text-zinc-300 sm:col-span-2">Payment instructions / คำแนะนำการชำระเงิน<textarea class="mt-1 w-full rounded-md border border-zinc-200 dark:border-white/10 bg-white dark:bg-zinc-950 px-3 py-2 text-zinc-950 dark:text-white" name="payment_instructions" rows="3">{{ old('payment_instructions', $event->payment_instructions) }}</textarea></label>
                </div>
            </div>
        </section>
        @php
            $ticketRows = collect(old('tickets', $ticketTypes->map(fn ($ticket) => [
                'id' => $ticket->id,
                'name' => $ticket->name,
                'description' => $ticket->description,
                'price_thb' => $ticket->price_thb,
                'full_price_thb' => $ticket->full_price_thb,
                'capacity' => $ticket->capacity,
                'sale_starts_at' => $ticket->sale_starts_at?->format('Y-m-d\TH:i'),
                'sale_ends_at' => $ticket->sale_ends_at?->format('Y-m-d\TH:i'),
                'status' => $ticket->status,
            ])->values()->all()))->values();
        @endphp
        <section class="rounded-lg border border-zinc-200 dark:border-white/10 bg-white dark:bg-white/[0.04] p-6" x-data="adminTicketTypes({ rows: @js($ticketRows) })">
            <h2 class="text-xl font-semibold text-zinc-950 dark:text-white">Ticket types / ประเภทตั๋ว</h2>
            <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">Add rows for new ticket types. Remove existing rows to hide them from sale without deleting old orders. / เพิ่มแถวสำหรับประเภทตั๋วใหม่ ลบแถวเดิมเพื่อปิดขายโดยไม่ลบออเดอร์เก่า</p>
            <template x-for="id in inactiveIds" :key="`inactive-${id}`">
                <input type="hidden" name="inactive_ticket_type_ids[]" :value="id">
            </template>
            <div class="mt-4 grid gap-4">
                <template x-for="(ticket, index) in rows" :key="ticket.id || `new-${index}`">
                    <div class="rounded-md border border-zinc-200 dark:border-white/10 bg-zinc-50 dark:bg-zinc-900 p-4">
                        <input type="hidden" :name="`tickets[${index}][id]`" x-model="ticket.id">
                        <input type="hidden" :name="`tickets[${index}][status]`" value="active">
                        <div class="flex items-start justify-between gap-3">
                            <label class="flex-1 text-sm text-zinc-700 dark:text-zinc-300">Type / ประเภท<input class="mt-1 w-full rounded-md border border-zinc-200 dark:border-white/10 bg-white dark:bg-zinc-950 px-3 py-2 text-zinc-950 dark:text-white" :name="`tickets[${index}][name]`" x-model="ticket.name"></label>
                            <button class="mt-6 rounded-md border border-rose-300 px-3 py-2 text-sm text-rose-700 dark:border-rose-400/40 dark:text-rose-200" type="button" @click="removeRow(index)">Remove / ลบ</button>
                        </div>
                        <div class="mt-3 grid gap-3 sm:grid-cols-2">
                            <label class="text-sm text-zinc-700 dark:text-zinc-300">Sale price THB / ราคาขาย<input class="mt-1 w-full rounded-md border border-zinc-200 dark:border-white/10 bg-white dark:bg-zinc-950 px-3 py-2 text-zinc-950 dark:text-white" type="number" :name="`tickets[${index}][price_thb]`" x-model="ticket.price_thb"></label>
                            <label class="text-sm text-zinc-700 dark:text-zinc-300">Full price THB / ราคาเต็ม <span class="text-xs text-zinc-500">optional / ไม่บังคับ</span><input class="mt-1 w-full rounded-md border border-zinc-200 dark:border-white/10 bg-white dark:bg-zinc-950 px-3 py-2 text-zinc-950 dark:text-white" type="number" :name="`tickets[${index}][full_price_thb]`" x-model="ticket.full_price_thb"></label>
                            <label class="text-sm text-zinc-700 dark:text-zinc-300">Capacity / จำนวนจำกัด<input class="mt-1 w-full rounded-md border border-zinc-200 dark:border-white/10 bg-white dark:bg-zinc-950 px-3 py-2 text-zinc-950 dark:text-white" type="number" :name="`tickets[${index}][capacity]`" x-model="ticket.capacity"></label>
                            <label class="text-sm text-zinc-700 dark:text-zinc-300">Sale starts / เริ่มขาย<input class="mt-1 w-full rounded-md border border-zinc-200 dark:border-white/10 bg-white dark:bg-zinc-950 px-3 py-2 text-zinc-950 dark:text-white" type="datetime-local" :name="`tickets[${index}][sale_starts_at]`" x-model="ticket.sale_starts_at"></label>
                            <label class="text-sm text-zinc-700 dark:text-zinc-300">Sale ends / สิ้นสุดขาย<input class="mt-1 w-full rounded-md border border-zinc-200 dark:border-white/10 bg-white dark:bg-zinc-950 px-3 py-2 text-zinc-950 dark:text-white" type="datetime-local" :name="`tickets[${index}][sale_ends_at]`" x-model="ticket.sale_ends_at"></label>
                        </div>
                        <label class="mt-3 block text-sm text-zinc-700 dark:text-zinc-300">Description / รายละเอียด<textarea class="mt-1 w-full rounded-md border border-zinc-200 dark:border-white/10 bg-white dark:bg-zinc-950 px-3 py-2 text-zinc-950 dark:text-white" :name="`tickets[${index}][description]`" rows="2" x-model="ticket.description"></textarea></label>
                    </div>
                </template>
            </div>
            <button class="mt-5 w-full rounded-md border border-zinc-200 dark:border-white/10 px-4 py-3 font-semibold text-zinc-800 dark:text-zinc-100 hover:border-emerald-300" type="button" @click="addRow()">Add ticket type / เพิ่มประเภทตั๋ว</button>
            <button class="mt-5 w-full rounded-md bg-emerald-400 px-4 py-3 font-semibold text-zinc-950">Save event / บันทึกอีเวนต์</button>
        </section>
    </form>
</x-layouts.app>
