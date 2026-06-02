<x-layouts.app :title="$event->exists ? 'Edit event' : 'New event'">
    <form method="POST" enctype="multipart/form-data" action="{{ $event->exists ? route('admin.events.update', $event) : route('admin.events.store') }}" class="grid gap-6 lg:grid-cols-[1fr_.8fr]" x-data="adminEventForm({
        description: @js(old('description', $event->description)),
        descriptionFormat: @js(old('description_format', $event->description_format ?: 'html')),
        paymentAccounts: @js(old('payment_accounts', $event->paymentOptions())),
        banks: @js(collect(config('thai_banks'))->map(fn ($bank) => $bank + ['logo_url' => asset($bank['logo'])])->values()),
        previewUrl: @js(route('admin.events.index')),
    })">
        @csrf
        @if($event->exists) @method('PUT') @endif
        <section class="rounded-lg border border-zinc-200 dark:border-white/10 bg-white dark:bg-white/[0.04] p-6">
            <h1 class="text-2xl font-semibold text-zinc-950 dark:text-white">{{ $event->exists ? 'Edit event / แก้ไขอีเวนต์' : 'New event / เพิ่มอีเวนต์' }}</h1>
            <div class="mt-5 grid gap-4 sm:grid-cols-2">
                <label class="text-sm text-zinc-700 dark:text-zinc-300 sm:col-span-2">Event name / ชื่ออีเวนต์<input class="mt-1 w-full rounded-md border border-zinc-200 dark:border-white/10 bg-white dark:bg-zinc-950 px-3 py-2 text-zinc-950 dark:text-white" name="name" value="{{ old('name', $event->name) }}" required></label>
                <div class="sm:col-span-2">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <label class="text-sm text-zinc-700 dark:text-zinc-300">Description / รายละเอียด</label>
                        <div class="inline-grid grid-cols-2 overflow-hidden rounded-md border border-zinc-200 text-sm dark:border-white/10">
                            <label class="px-3 py-2" x-bind:class="descriptionFormat === 'html' ? 'bg-emerald-400 text-zinc-950' : 'text-zinc-700 dark:text-zinc-200'"><input class="sr-only" type="radio" name="description_format" value="html" x-model="descriptionFormat">Safe HTML</label>
                            <label class="px-3 py-2" x-bind:class="descriptionFormat === 'markdown' ? 'bg-emerald-400 text-zinc-950' : 'text-zinc-700 dark:text-zinc-200'"><input class="sr-only" type="radio" name="description_format" value="markdown" x-model="descriptionFormat">Markdown</label>
                        </div>
                    </div>
                    <textarea class="mt-1 w-full rounded-md border border-zinc-200 dark:border-white/10 bg-white dark:bg-zinc-950 px-3 py-2 text-zinc-950 dark:text-white" name="description" rows="7" x-model="description">{{ old('description', $event->description) }}</textarea>
                    <div class="mt-2 flex flex-wrap items-center justify-between gap-2">
                        <span class="text-xs text-zinc-500" x-text="descriptionFormat === 'markdown' ? 'Markdown supported: headings, links, lists, bold, italic. / รองรับ Markdown เช่น หัวข้อ ลิงก์ รายการ ตัวหนา ตัวเอียง' : 'Allowed HTML tags: p, br, strong, em, u, ul, ol, li, a, h2, h3, blockquote.'"></span>
                        <button class="inline-flex items-center gap-2 rounded-md border border-zinc-200 px-3 py-2 text-sm font-semibold text-zinc-700 dark:border-white/10 dark:text-zinc-200" type="button" @click="previewOpen = !previewOpen"><x-icon name="eye" />Preview / ดูตัวอย่าง</button>
                    </div>
                    <div class="mt-3 rounded-md border border-zinc-200 bg-zinc-50 p-4 text-sm text-zinc-700 dark:border-white/10 dark:bg-zinc-900 dark:text-zinc-300" x-cloak x-show="previewOpen">
                        <div class="mb-2 font-semibold text-zinc-950 dark:text-white">Preview / ตัวอย่าง</div>
                        <div class="space-y-2" x-html="previewHtml()"></div>
                    </div>
                </div>
                <label class="text-sm text-zinc-700 dark:text-zinc-300 sm:col-span-2">Social snippet / ข้อความตัวอย่างเวลาแชร์<textarea class="mt-1 w-full rounded-md border border-zinc-200 dark:border-white/10 bg-white dark:bg-zinc-950 px-3 py-2 text-zinc-950 dark:text-white" name="social_description" rows="2" maxlength="500">{{ old('social_description', $event->social_description) }}</textarea></label>
                <label class="text-sm text-zinc-700 dark:text-zinc-300">Venue / สถานที่<input class="mt-1 w-full rounded-md border border-zinc-200 dark:border-white/10 bg-white dark:bg-zinc-950 px-3 py-2 text-zinc-950 dark:text-white" name="venue" value="{{ old('venue', $event->venue) }}" required></label>
                <label class="text-sm text-zinc-700 dark:text-zinc-300">Location / ที่ตั้ง<input class="mt-1 w-full rounded-md border border-zinc-200 dark:border-white/10 bg-white dark:bg-zinc-950 px-3 py-2 text-zinc-950 dark:text-white" name="location" value="{{ old('location', $event->location) }}"></label>
                <label class="text-sm text-zinc-700 dark:text-zinc-300">Google Maps link / ลิงก์ Google Maps<input class="mt-1 w-full rounded-md border border-zinc-200 dark:border-white/10 bg-white dark:bg-zinc-950 px-3 py-2 text-zinc-950 dark:text-white" name="location_url" value="{{ old('location_url', $event->location_url) }}" placeholder="https://maps.app.goo.gl/..."></label>
                <label class="text-sm text-zinc-700 dark:text-zinc-300">Hosted by / ผู้จัด<input class="mt-1 w-full rounded-md border border-zinc-200 dark:border-white/10 bg-white dark:bg-zinc-950 px-3 py-2 text-zinc-950 dark:text-white" name="hosted_by" value="{{ old('hosted_by', $event->hosted_by) }}"></label>
                <label class="text-sm text-zinc-700 dark:text-zinc-300">Host link / ลิงก์ผู้จัด<input class="mt-1 w-full rounded-md border border-zinc-200 dark:border-white/10 bg-white dark:bg-zinc-950 px-3 py-2 text-zinc-950 dark:text-white" name="hosted_by_url" value="{{ old('hosted_by_url', $event->hosted_by_url) }}" placeholder="https://..."></label>
                <label class="text-sm text-zinc-700 dark:text-zinc-300">Event poster 4:5 / โปสเตอร์อีเวนต์ 4:5<input class="mt-1 w-full rounded-md border border-zinc-200 dark:border-white/10 bg-white dark:bg-zinc-950 px-3 py-2 text-zinc-950 dark:text-white" type="file" name="poster" accept="image/*"></label>
                <label class="text-sm text-zinc-700 dark:text-zinc-300">Ticket image 4:5 / รูปตั๋ว 4:5<input class="mt-1 w-full rounded-md border border-zinc-200 dark:border-white/10 bg-white dark:bg-zinc-950 px-3 py-2 text-zinc-950 dark:text-white" type="file" name="ticket_image" accept="image/*"></label>
                <label class="text-sm text-zinc-700 dark:text-zinc-300 sm:col-span-2">Social share image / รูปสำหรับแชร์โซเชียล<input class="mt-1 w-full rounded-md border border-zinc-200 dark:border-white/10 bg-white dark:bg-zinc-950 px-3 py-2 text-zinc-950 dark:text-white" type="file" name="social_image" accept="image/*"></label>
                <label class="text-sm text-zinc-700 dark:text-zinc-300">Starts at / เริ่มงาน<input class="mt-1 w-full rounded-md border border-zinc-200 dark:border-white/10 bg-white dark:bg-zinc-950 px-3 py-2 text-zinc-950 dark:text-white" type="datetime-local" name="starts_at" value="{{ old('starts_at', $event->starts_at?->format('Y-m-d\TH:i')) }}" data-date-start="event" data-default-hours="3" required></label>
                <label class="text-sm text-zinc-700 dark:text-zinc-300">Ends at / จบงาน<input class="mt-1 w-full rounded-md border border-zinc-200 dark:border-white/10 bg-white dark:bg-zinc-950 px-3 py-2 text-zinc-950 dark:text-white" type="datetime-local" name="ends_at" value="{{ old('ends_at', $event->ends_at?->format('Y-m-d\TH:i')) }}" data-date-end="event" required></label>
                <label class="flex items-center gap-2 text-sm text-zinc-700 dark:text-zinc-300"><input class="rounded border-zinc-200 dark:border-white/10 bg-white dark:bg-zinc-950" type="checkbox" name="is_published" value="1" @checked(old('is_published', $event->is_published ?? true))> Published / เผยแพร่</label>
                <label class="flex items-center gap-2 text-sm text-zinc-700 dark:text-zinc-300"><input class="rounded border-zinc-200 dark:border-white/10 bg-white dark:bg-zinc-950" type="checkbox" name="show_countdown" value="1" @checked(old('show_countdown', $event->show_countdown ?? false))> Show countdown / แสดงเวลานับถอยหลัง</label>
            </div>
            <div class="mt-6 border-t border-zinc-200 dark:border-white/10 pt-6">
                <h2 class="text-xl font-semibold text-zinc-950 dark:text-white">Payment accounts / บัญชีรับชำระเงิน</h2>
                <input type="hidden" name="bank_name" :value="firstAccount('bank_transfer').bank_name || ''">
                <input type="hidden" name="bank_account_name" :value="firstAccount('bank_transfer').account_name || ''">
                <input type="hidden" name="bank_account_number" :value="firstAccount('bank_transfer').account_number || ''">
                <input type="hidden" name="qr_payment_account_name" :value="firstAccount('qr_payment').account_name || ''">
                <input type="hidden" name="qr_payment_account" :value="firstAccount('qr_payment').account_number || ''">
                <input type="hidden" name="payment_instructions" :value="firstPaymentInstructions()">
                <div class="mt-4 grid gap-4">
                    <template x-for="(account, index) in paymentAccounts" :key="account.key || index">
                        <div class="rounded-md border border-zinc-200 bg-zinc-50 p-4 dark:border-white/10 dark:bg-zinc-900">
                            <input type="hidden" :name="`payment_accounts[${index}][key]`" x-model="account.key">
                            <div class="flex items-start justify-between gap-3">
                                <label class="text-sm text-zinc-700 dark:text-zinc-300">Method / วิธีชำระเงิน
                                    <select class="mt-1 w-full rounded-md border border-zinc-200 dark:border-white/10 bg-white dark:bg-zinc-950 px-3 py-2 text-zinc-950 dark:text-white" :name="`payment_accounts[${index}][method]`" x-model="account.method">
                                        <option value="qr_payment">QR payment / QR</option>
                                        <option value="bank_transfer">Bank transfer / โอนธนาคาร</option>
                                        <option value="cash">Cash sale / เงินสด</option>
                                    </select>
                                </label>
                                <button class="mt-6 inline-flex items-center gap-2 rounded-md border border-rose-300 px-3 py-2 text-sm text-rose-700 dark:border-rose-400/40 dark:text-rose-200" type="button" @click="removePaymentAccount(index)"><x-icon name="trash-2" />Remove / ลบ</button>
                            </div>
                            <div class="mt-3 grid gap-3 sm:grid-cols-2">
                                <label class="text-sm text-zinc-700 dark:text-zinc-300">Display name / ชื่อที่แสดง<input class="mt-1 w-full rounded-md border border-zinc-200 dark:border-white/10 bg-white dark:bg-zinc-950 px-3 py-2 text-zinc-950 dark:text-white" :name="`payment_accounts[${index}][label]`" x-model="account.label"></label>
                                <label class="flex items-center gap-2 pt-7 text-sm text-zinc-700 dark:text-zinc-300"><input type="hidden" :name="`payment_accounts[${index}][is_active]`" value="0"><input class="rounded border-zinc-200 dark:border-white/10 bg-white dark:bg-zinc-950" type="checkbox" :name="`payment_accounts[${index}][is_active]`" value="1" x-model="account.is_active"> Active / เปิดใช้งาน</label>
                                <label class="text-sm text-zinc-700 dark:text-zinc-300" x-show="account.method === 'bank_transfer'">Bank name / ชื่อธนาคาร
                                    <select class="mt-1 w-full rounded-md border border-zinc-200 dark:border-white/10 bg-white dark:bg-zinc-950 px-3 py-2 text-zinc-950 dark:text-white" :name="`payment_accounts[${index}][bank_name]`" x-model="account.bank_name">
                                        <option value="">Select bank / เลือกธนาคาร</option>
                                        <template x-for="bank in banks" :key="bank.name">
                                            <option :value="bank.name" x-text="`${bank.name} / ${bank.thai_name}`"></option>
                                        </template>
                                    </select>
                                </label>
                                <label class="text-sm text-zinc-700 dark:text-zinc-300">Account name / ชื่อบัญชี<input class="mt-1 w-full rounded-md border border-zinc-200 dark:border-white/10 bg-white dark:bg-zinc-950 px-3 py-2 text-zinc-950 dark:text-white" :name="`payment_accounts[${index}][account_name]`" x-model="account.account_name"></label>
                                <label class="text-sm text-zinc-700 dark:text-zinc-300" x-show="account.method !== 'cash'">Account / PromptPay / เลขบัญชีหรือพร้อมเพย์<input class="mt-1 w-full rounded-md border border-zinc-200 dark:border-white/10 bg-white dark:bg-zinc-950 px-3 py-2 text-zinc-950 dark:text-white" :name="`payment_accounts[${index}][account_number]`" x-model="account.account_number"></label>
                                <label class="text-sm text-zinc-700 dark:text-zinc-300 sm:col-span-2">Instructions / คำแนะนำ<textarea class="mt-1 w-full rounded-md border border-zinc-200 dark:border-white/10 bg-white dark:bg-zinc-950 px-3 py-2 text-zinc-950 dark:text-white" :name="`payment_accounts[${index}][instructions]`" rows="2" x-model="account.instructions"></textarea></label>
                            </div>
                        </div>
                    </template>
                </div>
                <button class="mt-4 inline-flex w-full items-center justify-center gap-2 rounded-md border border-zinc-200 px-4 py-3 font-semibold text-zinc-800 hover:border-emerald-300 dark:border-white/10 dark:text-zinc-100" type="button" @click="addPaymentAccount()"><x-icon name="plus" />Add payment account / เพิ่มบัญชีรับชำระเงิน</button>
                <label class="mt-4 block text-sm text-zinc-700 dark:text-zinc-300">QR payment image / รูป QR ชำระเงิน<input class="mt-1 w-full rounded-md border border-zinc-200 dark:border-white/10 bg-white dark:bg-zinc-950 px-3 py-2 text-zinc-950 dark:text-white" type="file" name="qr_payment_image" accept="image/*"></label>
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
                            <button class="mt-6 inline-flex items-center gap-2 rounded-md border border-rose-300 px-3 py-2 text-sm text-rose-700 dark:border-rose-400/40 dark:text-rose-200" type="button" @click="removeRow(index)"><x-icon name="trash-2" />Remove / ลบ</button>
                        </div>
                        <div class="mt-3 grid gap-3 sm:grid-cols-2">
                            <label class="text-sm text-zinc-700 dark:text-zinc-300">Sale price THB / ราคาขาย<input class="mt-1 w-full rounded-md border border-zinc-200 dark:border-white/10 bg-white dark:bg-zinc-950 px-3 py-2 text-zinc-950 dark:text-white" type="number" :name="`tickets[${index}][price_thb]`" x-model="ticket.price_thb"></label>
                            <label class="text-sm text-zinc-700 dark:text-zinc-300">Full price THB / ราคาเต็ม <span class="text-xs text-zinc-500">optional / ไม่บังคับ</span><input class="mt-1 w-full rounded-md border border-zinc-200 dark:border-white/10 bg-white dark:bg-zinc-950 px-3 py-2 text-zinc-950 dark:text-white" type="number" :name="`tickets[${index}][full_price_thb]`" x-model="ticket.full_price_thb"></label>
                            <label class="text-sm text-zinc-700 dark:text-zinc-300">Capacity / จำนวนจำกัด<input class="mt-1 w-full rounded-md border border-zinc-200 dark:border-white/10 bg-white dark:bg-zinc-950 px-3 py-2 text-zinc-950 dark:text-white" type="number" :name="`tickets[${index}][capacity]`" x-model="ticket.capacity"></label>
                            <label class="text-sm text-zinc-700 dark:text-zinc-300">Sale starts / เริ่มขาย<input class="mt-1 w-full rounded-md border border-zinc-200 dark:border-white/10 bg-white dark:bg-zinc-950 px-3 py-2 text-zinc-950 dark:text-white" type="datetime-local" :name="`tickets[${index}][sale_starts_at]`" x-model="ticket.sale_starts_at" @change="reflectTicketEnd(ticket)"></label>
                            <label class="text-sm text-zinc-700 dark:text-zinc-300">Sale ends / สิ้นสุดขาย<input class="mt-1 w-full rounded-md border border-zinc-200 dark:border-white/10 bg-white dark:bg-zinc-950 px-3 py-2 text-zinc-950 dark:text-white" type="datetime-local" :name="`tickets[${index}][sale_ends_at]`" x-model="ticket.sale_ends_at"></label>
                        </div>
                        <label class="mt-3 block text-sm text-zinc-700 dark:text-zinc-300">Description / รายละเอียด<textarea class="mt-1 w-full rounded-md border border-zinc-200 dark:border-white/10 bg-white dark:bg-zinc-950 px-3 py-2 text-zinc-950 dark:text-white" :name="`tickets[${index}][description]`" rows="2" x-model="ticket.description"></textarea></label>
                    </div>
                </template>
            </div>
            <button class="mt-5 inline-flex w-full items-center justify-center gap-2 rounded-md border border-zinc-200 dark:border-white/10 px-4 py-3 font-semibold text-zinc-800 dark:text-zinc-100 hover:border-emerald-300" type="button" @click="addRow()"><x-icon name="plus" />Add ticket type / เพิ่มประเภทตั๋ว</button>
            <button class="mt-5 inline-flex w-full items-center justify-center gap-2 rounded-md bg-emerald-400 px-4 py-3 font-semibold text-zinc-950"><x-icon name="save" />Save event / บันทึกอีเวนต์</button>
        </section>
    </form>
</x-layouts.app>
