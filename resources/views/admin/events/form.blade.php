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
            <h1 class="text-2xl font-semibold text-zinc-950 dark:text-white">
                @if($event->exists)
                    <x-t en="Edit event" th="แก้ไขอีเวนต์" />
                @else
                    <x-t en="New event" th="เพิ่มอีเวนต์" />
                @endif
            </h1>
            <div class="mt-5 grid gap-4 sm:grid-cols-2">
                <label class="text-sm text-zinc-700 dark:text-zinc-300 sm:col-span-2"><x-t en="Event name" th="ชื่ออีเวนต์" /><input class="mt-1 w-full rounded-md border border-zinc-200 dark:border-white/10 bg-white dark:bg-zinc-950 px-3 py-2 text-zinc-950 dark:text-white" name="name" value="{{ old('name', $event->name) }}" required></label>
                <div class="sm:col-span-2">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <label class="text-sm text-zinc-700 dark:text-zinc-300"><x-t en="Description" th="รายละเอียด" /></label>
                        <div class="inline-grid grid-cols-2 overflow-hidden rounded-md border border-zinc-200 text-sm dark:border-white/10">
                            <label class="px-3 py-2" x-bind:class="descriptionFormat === 'html' ? 'bg-emerald-400 text-zinc-950' : 'text-zinc-700 dark:text-zinc-200'"><input class="sr-only" type="radio" name="description_format" value="html" x-model="descriptionFormat">Safe HTML</label>
                            <label class="px-3 py-2" x-bind:class="descriptionFormat === 'markdown' ? 'bg-emerald-400 text-zinc-950' : 'text-zinc-700 dark:text-zinc-200'"><input class="sr-only" type="radio" name="description_format" value="markdown" x-model="descriptionFormat">Markdown</label>
                        </div>
                    </div>
                    <textarea class="mt-1 w-full rounded-md border border-zinc-200 dark:border-white/10 bg-white dark:bg-zinc-950 px-3 py-2 text-zinc-950 dark:text-white" name="description" rows="7" x-model="description">{{ old('description', $event->description) }}</textarea>
                    <div class="mt-2 flex flex-wrap items-center justify-between gap-2">
                        <span class="text-xs text-zinc-500" x-text="descriptionFormat === 'markdown' ? TicketFlowLanguage.format({ en: 'Markdown supported: headings, links, lists, bold, italic.', th: 'รองรับ Markdown เช่น หัวข้อ ลิงก์ รายการ ตัวหนา ตัวเอียง' }) : 'Allowed HTML tags: p, br, strong, em, u, ul, ol, li, a, h2, h3, blockquote.'"></span>
                        <button class="inline-flex items-center gap-2 rounded-md border border-zinc-200 px-3 py-2 text-sm font-semibold text-zinc-700 dark:border-white/10 dark:text-zinc-200" type="button" @click="previewOpen = !previewOpen"><x-icon name="eye" /><x-t en="Preview" th="ดูตัวอย่าง" /></button>
                    </div>
                    <div class="mt-3 rounded-md border border-zinc-200 bg-zinc-50 p-4 text-sm text-zinc-700 dark:border-white/10 dark:bg-zinc-900 dark:text-zinc-300" x-cloak x-show="previewOpen">
                        <div class="mb-2 font-semibold text-zinc-950 dark:text-white"><x-t en="Preview" th="ตัวอย่าง" /></div>
                        <div class="space-y-2" x-html="previewHtml()"></div>
                    </div>
                </div>
                <label class="text-sm text-zinc-700 dark:text-zinc-300 sm:col-span-2"><x-t en="Social snippet" th="ข้อความตัวอย่างเวลาแชร์" /><textarea class="mt-1 w-full rounded-md border border-zinc-200 dark:border-white/10 bg-white dark:bg-zinc-950 px-3 py-2 text-zinc-950 dark:text-white" name="social_description" rows="2" maxlength="500">{{ old('social_description', $event->social_description) }}</textarea></label>
                <label class="text-sm text-zinc-700 dark:text-zinc-300"><x-t en="Venue" th="สถานที่" /><input class="mt-1 w-full rounded-md border border-zinc-200 dark:border-white/10 bg-white dark:bg-zinc-950 px-3 py-2 text-zinc-950 dark:text-white" name="venue" value="{{ old('venue', $event->venue) }}" required></label>
                <label class="text-sm text-zinc-700 dark:text-zinc-300"><x-t en="Location" th="ที่ตั้ง" /><input class="mt-1 w-full rounded-md border border-zinc-200 dark:border-white/10 bg-white dark:bg-zinc-950 px-3 py-2 text-zinc-950 dark:text-white" name="location" value="{{ old('location', $event->location) }}"></label>
                <label class="text-sm text-zinc-700 dark:text-zinc-300"><x-t en="Google Maps link" th="ลิงก์ Google Maps" /><input class="mt-1 w-full rounded-md border border-zinc-200 dark:border-white/10 bg-white dark:bg-zinc-950 px-3 py-2 text-zinc-950 dark:text-white" name="location_url" value="{{ old('location_url', $event->location_url) }}" placeholder="https://maps.app.goo.gl/..."></label>
                <label class="text-sm text-zinc-700 dark:text-zinc-300"><x-t en="Hosted by" th="ผู้จัด" /><input class="mt-1 w-full rounded-md border border-zinc-200 dark:border-white/10 bg-white dark:bg-zinc-950 px-3 py-2 text-zinc-950 dark:text-white" name="hosted_by" value="{{ old('hosted_by', $event->hosted_by) }}"></label>
                <label class="text-sm text-zinc-700 dark:text-zinc-300"><x-t en="Host link" th="ลิงก์ผู้จัด" /><input class="mt-1 w-full rounded-md border border-zinc-200 dark:border-white/10 bg-white dark:bg-zinc-950 px-3 py-2 text-zinc-950 dark:text-white" name="hosted_by_url" value="{{ old('hosted_by_url', $event->hosted_by_url) }}" placeholder="https://..."></label>
                <label class="text-sm text-zinc-700 dark:text-zinc-300"><x-t en="Event poster 4:5" th="โปสเตอร์อีเวนต์ 4:5" /><input class="mt-1 w-full rounded-md border border-zinc-200 dark:border-white/10 bg-white dark:bg-zinc-950 px-3 py-2 text-zinc-950 dark:text-white" type="file" name="poster" accept="image/*"></label>
                <label class="text-sm text-zinc-700 dark:text-zinc-300"><x-t en="Ticket image 4:5" th="รูปตั๋ว 4:5" /><input class="mt-1 w-full rounded-md border border-zinc-200 dark:border-white/10 bg-white dark:bg-zinc-950 px-3 py-2 text-zinc-950 dark:text-white" type="file" name="ticket_image" accept="image/*"></label>
                <label class="text-sm text-zinc-700 dark:text-zinc-300 sm:col-span-2"><x-t en="Social share image" th="รูปสำหรับแชร์โซเชียล" /><input class="mt-1 w-full rounded-md border border-zinc-200 dark:border-white/10 bg-white dark:bg-zinc-950 px-3 py-2 text-zinc-950 dark:text-white" type="file" name="social_image" accept="image/*"></label>
                <label class="text-sm text-zinc-700 dark:text-zinc-300"><x-t en="Starts at" th="เริ่มงาน" /><input class="mt-1 w-full rounded-md border border-zinc-200 dark:border-white/10 bg-white dark:bg-zinc-950 px-3 py-2 text-zinc-950 dark:text-white" type="datetime-local" name="starts_at" value="{{ old('starts_at', $event->starts_at?->format('Y-m-d\TH:i')) }}" data-date-start="event" data-default-hours="3" required></label>
                <label class="text-sm text-zinc-700 dark:text-zinc-300"><x-t en="Ends at" th="จบงาน" /><input class="mt-1 w-full rounded-md border border-zinc-200 dark:border-white/10 bg-white dark:bg-zinc-950 px-3 py-2 text-zinc-950 dark:text-white" type="datetime-local" name="ends_at" value="{{ old('ends_at', $event->ends_at?->format('Y-m-d\TH:i')) }}" data-date-end="event" required></label>
                <label class="flex items-center gap-2 text-sm text-zinc-700 dark:text-zinc-300"><input class="rounded border-zinc-200 dark:border-white/10 bg-white dark:bg-zinc-950" type="checkbox" name="is_published" value="1" @checked(old('is_published', $event->is_published ?? true))> <x-t en="Published" th="เผยแพร่" /></label>
                <label class="flex items-center gap-2 text-sm text-zinc-700 dark:text-zinc-300"><input class="rounded border-zinc-200 dark:border-white/10 bg-white dark:bg-zinc-950" type="checkbox" name="show_countdown" value="1" @checked(old('show_countdown', $event->show_countdown ?? false))> <x-t en="Show countdown" th="แสดงเวลานับถอยหลัง" /></label>
            </div>
            <div class="mt-6 border-t border-zinc-200 dark:border-white/10 pt-6">
                <h2 class="text-xl font-semibold text-zinc-950 dark:text-white"><x-t en="Payment accounts" th="บัญชีรับชำระเงิน" /></h2>
                <input type="hidden" name="bank_name" :value="firstAccount('bank_transfer').bank_name || ''">
                <input type="hidden" name="bank_account_name" :value="firstAccount('bank_transfer').account_name || ''">
                <input type="hidden" name="bank_account_number" :value="firstAccount('bank_transfer').account_number || ''">
                <input type="hidden" name="qr_payment_account_name" :value="firstAccount('qr_payment').account_name || ''">
                <input type="hidden" name="qr_payment_account" :value="firstAccount('qr_payment').account_number || ''">
                <input type="hidden" name="payment_instructions" :value="firstPaymentInstructions()">
                <div class="mt-4 grid gap-4">
                    <template x-for="(account, index) in paymentAccounts" :key="account.key || index">
                        <div class="rounded-lg border border-zinc-200 bg-zinc-50 p-4 shadow-sm dark:border-white/10 dark:bg-zinc-900">
                            <input type="hidden" :name="`payment_accounts[${index}][key]`" x-model="account.key">
                            <div class="flex flex-wrap items-start justify-between gap-3 border-b border-zinc-200 pb-3 dark:border-white/10">
                                <div>
                                    <div class="text-sm font-semibold text-zinc-950 dark:text-white"><span x-text="account.label || TicketFlowLanguage.format({ en: 'Payment account', th: 'บัญชีรับชำระเงิน' })"></span></div>
                                    <div class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">#<span x-text="index + 1"></span> · <span x-text="account.method.replace('_', ' ')"></span></div>
                                </div>
                                <div class="flex flex-wrap items-center gap-2">
                                    <label class="inline-flex items-center gap-2 rounded-md border border-zinc-200 bg-white px-3 py-2 text-sm font-semibold text-zinc-700 dark:border-white/10 dark:bg-zinc-950 dark:text-zinc-200"><input type="hidden" :name="`payment_accounts[${index}][is_active]`" value="0"><input class="rounded border-zinc-200 dark:border-white/10 bg-white dark:bg-zinc-950" type="checkbox" :name="`payment_accounts[${index}][is_active]`" value="1" x-model="account.is_active"> <x-t en="Active" th="เปิด" /></label>
                                    <button class="inline-flex h-9 w-9 items-center justify-center rounded-md border border-zinc-200 text-zinc-700 disabled:opacity-40 dark:border-white/10 dark:text-zinc-200" type="button" @click="movePaymentAccount(index, -1)" :disabled="index === 0" title="Move up"><x-icon name="arrow-up" /></button>
                                    <button class="inline-flex h-9 w-9 items-center justify-center rounded-md border border-zinc-200 text-zinc-700 disabled:opacity-40 dark:border-white/10 dark:text-zinc-200" type="button" @click="movePaymentAccount(index, 1)" :disabled="index === paymentAccounts.length - 1" title="Move down"><x-icon name="arrow-down" /></button>
                                    <button class="inline-flex h-9 w-9 items-center justify-center rounded-md border border-rose-300 text-rose-700 dark:border-rose-400/40 dark:text-rose-200" type="button" @click="removePaymentAccount(index)" title="Remove"><x-icon name="trash-2" /></button>
                                </div>
                            </div>
                            <div class="mt-3 grid gap-3 sm:grid-cols-2">
                                <label class="text-sm text-zinc-700 dark:text-zinc-300"><x-t en="Method" th="วิธีชำระเงิน" />
                                    <select class="mt-1 w-full rounded-md border border-zinc-200 dark:border-white/10 bg-white dark:bg-zinc-950 px-3 py-2 text-zinc-950 dark:text-white" :name="`payment_accounts[${index}][method]`" x-model="account.method">
                                        <option value="qr_payment" data-i18n-en="QR payment" data-i18n-th="QR">QR payment</option>
                                        <option value="bank_transfer" data-i18n-en="Bank transfer" data-i18n-th="โอนธนาคาร">Bank transfer</option>
                                        <option value="cash" data-i18n-en="Cash sale" data-i18n-th="เงินสด">Cash sale</option>
                                    </select>
                                </label>
                                <label class="text-sm text-zinc-700 dark:text-zinc-300"><x-t en="Display name" th="ชื่อที่แสดง" /><input class="mt-1 w-full rounded-md border border-zinc-200 dark:border-white/10 bg-white dark:bg-zinc-950 px-3 py-2 text-zinc-950 dark:text-white" :name="`payment_accounts[${index}][label]`" x-model="account.label"></label>
                                <label class="text-sm text-zinc-700 dark:text-zinc-300" x-show="account.method === 'bank_transfer'"><x-t en="Bank name" th="ชื่อธนาคาร" />
                                    <select class="mt-1 w-full rounded-md border border-zinc-200 dark:border-white/10 bg-white dark:bg-zinc-950 px-3 py-2 text-zinc-950 dark:text-white" :name="`payment_accounts[${index}][bank_name]`" x-model="account.bank_name">
                                        <option value="" data-i18n-en="Select bank" data-i18n-th="เลือกธนาคาร">Select bank</option>
                                        <template x-for="bank in banks" :key="bank.name">
                                            <option :value="bank.name" x-text="TicketFlowLanguage.format({ en: bank.name, th: bank.thai_name })"></option>
                                        </template>
                                    </select>
                                </label>
                                <label class="text-sm text-zinc-700 dark:text-zinc-300"><x-t en="Account name" th="ชื่อบัญชี" /><input class="mt-1 w-full rounded-md border border-zinc-200 dark:border-white/10 bg-white dark:bg-zinc-950 px-3 py-2 text-zinc-950 dark:text-white" :name="`payment_accounts[${index}][account_name]`" x-model="account.account_name"></label>
                                <label class="text-sm text-zinc-700 dark:text-zinc-300" x-show="account.method !== 'cash'"><x-t en="Account or PromptPay" th="เลขบัญชีหรือพร้อมเพย์" /><input class="mt-1 w-full rounded-md border border-zinc-200 dark:border-white/10 bg-white dark:bg-zinc-950 px-3 py-2 text-zinc-950 dark:text-white" :name="`payment_accounts[${index}][account_number]`" x-model="account.account_number"></label>
                                <label class="text-sm text-zinc-700 dark:text-zinc-300 sm:col-span-2"><x-t en="Instructions" th="คำแนะนำ" /><textarea class="mt-1 w-full rounded-md border border-zinc-200 dark:border-white/10 bg-white dark:bg-zinc-950 px-3 py-2 text-zinc-950 dark:text-white" :name="`payment_accounts[${index}][instructions]`" rows="2" x-model="account.instructions"></textarea></label>
                            </div>
                        </div>
                    </template>
                </div>
                <button class="mt-4 inline-flex w-full items-center justify-center gap-2 rounded-md border border-zinc-200 px-4 py-3 font-semibold text-zinc-800 hover:border-emerald-300 dark:border-white/10 dark:text-zinc-100" type="button" @click="addPaymentAccount()"><x-icon name="plus" /><x-t en="Add payment account" th="เพิ่มบัญชีรับชำระเงิน" /></button>
                <label class="mt-4 block text-sm text-zinc-700 dark:text-zinc-300"><x-t en="QR payment image" th="รูป QR ชำระเงิน" /><input class="mt-1 w-full rounded-md border border-zinc-200 dark:border-white/10 bg-white dark:bg-zinc-950 px-3 py-2 text-zinc-950 dark:text-white" type="file" name="qr_payment_image" accept="image/*"></label>
            </div>
            <div class="mt-6 border-t border-zinc-200 dark:border-white/10 pt-6">
                <h2 class="text-xl font-semibold text-zinc-950 dark:text-white"><x-t en="Beam Checkout" th="ชำระด้วย Beam" /></h2>
                <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400"><x-t en="Accept payments via Beam Checkout (PromptPay QR, credit card, mobile banking)." th="รับชำระเงินผ่าน Beam Checkout (QR พร้อมเพย์, บัตรเครดิต, โมบายแบงก์กิ้ง)" /></p>
                <div class="mt-4 grid gap-4 sm:grid-cols-2">
                    <label class="flex items-center gap-2 text-sm text-zinc-700 dark:text-zinc-300"><input class="rounded border-zinc-200 dark:border-white/10 bg-white dark:bg-zinc-950" type="checkbox" name="beam_enabled" value="1" @checked(old('beam_enabled', $event->beam_enabled ?? false))> <x-t en="Enable Beam Checkout" th="เปิดใช้งาน Beam Checkout" /></label>
                    <label class="text-sm text-zinc-700 dark:text-zinc-300"><x-t en="Fee behavior" th="การจัดการค่าธรรมเนียม" />
                        <select class="mt-1 w-full rounded-md border border-zinc-200 dark:border-white/10 bg-white dark:bg-zinc-950 px-3 py-2 text-zinc-950 dark:text-white" name="beam_fee_behavior">
                            <option value="merchant_absorb" @selected(old('beam_fee_behavior', $event->beam_fee_behavior ?? 'merchant_absorb') === 'merchant_absorb') data-i18n-en="Merchant absorbs fee" data-i18n-th="ผู้จัดรับค่าธรรมเนียม">Merchant absorbs fee</option>
                            <option value="customer_pay" @selected(old('beam_fee_behavior', $event->beam_fee_behavior ?? 'merchant_absorb') === 'customer_pay') data-i18n-en="Customer pays fee" data-i18n-th="ผู้ซื้อจ่ายค่าธรรมเนียม">Customer pays fee</option>
                        </select>
                    </label>
                    <label class="text-sm text-zinc-700 dark:text-zinc-300"><x-t en="Fee percent (override)" th="เปอร์เซ็นต์ค่าธรรมเนียม" /> <span class="text-xs text-zinc-500"><x-t en="leave empty for default" th="เว้นว่างไว้ใช้ค่าเริ่มต้น" /></span><input class="mt-1 w-full rounded-md border border-zinc-200 dark:border-white/10 bg-white dark:bg-zinc-950 px-3 py-2 text-zinc-950 dark:text-white" type="number" step="0.01" min="0" max="100" name="beam_fee_percent" value="{{ old('beam_fee_percent', $event->beam_fee_percent) }}" placeholder="{{ config('services.beam.default_fee_percent', 3.0) }}"></label>
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
            <h2 class="text-xl font-semibold text-zinc-950 dark:text-white"><x-t en="Ticket types" th="ประเภทตั๋ว" /></h2>
            <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400"><x-t en="Add rows for new ticket types. Remove existing rows to hide them from sale without deleting old orders." th="เพิ่มแถวสำหรับประเภทตั๋วใหม่ ลบแถวเดิมเพื่อปิดขายโดยไม่ลบออเดอร์เก่า" /></p>
            <template x-for="id in inactiveIds" :key="`inactive-${id}`">
                <input type="hidden" name="inactive_ticket_type_ids[]" :value="id">
            </template>
            <div class="mt-4 grid gap-4">
                <template x-for="(ticket, index) in rows" :key="ticket.id || `new-${index}`">
                    <div class="rounded-md border border-zinc-200 dark:border-white/10 bg-zinc-50 dark:bg-zinc-900 p-4">
                        <input type="hidden" :name="`tickets[${index}][id]`" x-model="ticket.id">
                        <input type="hidden" :name="`tickets[${index}][status]`" value="active">
                        <div class="flex items-start justify-between gap-3">
                            <label class="flex-1 text-sm text-zinc-700 dark:text-zinc-300"><x-t en="Type" th="ประเภท" /><input class="mt-1 w-full rounded-md border border-zinc-200 dark:border-white/10 bg-white dark:bg-zinc-950 px-3 py-2 text-zinc-950 dark:text-white" :name="`tickets[${index}][name]`" x-model="ticket.name"></label>
                            <button class="mt-6 inline-flex items-center gap-2 rounded-md border border-rose-300 px-3 py-2 text-sm text-rose-700 dark:border-rose-400/40 dark:text-rose-200" type="button" @click="removeRow(index)"><x-icon name="trash-2" /><x-t en="Remove" th="ลบ" /></button>
                        </div>
                        <div class="mt-3 grid gap-3 sm:grid-cols-2">
                            <label class="text-sm text-zinc-700 dark:text-zinc-300"><x-t en="Sale price THB" th="ราคาขาย" /><input class="mt-1 w-full rounded-md border border-zinc-200 dark:border-white/10 bg-white dark:bg-zinc-950 px-3 py-2 text-zinc-950 dark:text-white" type="number" :name="`tickets[${index}][price_thb]`" x-model="ticket.price_thb"></label>
                            <label class="text-sm text-zinc-700 dark:text-zinc-300"><x-t en="Full price THB" th="ราคาเต็ม" /> <span class="text-xs text-zinc-500"><x-t en="optional" th="ไม่บังคับ" /></span><input class="mt-1 w-full rounded-md border border-zinc-200 dark:border-white/10 bg-white dark:bg-zinc-950 px-3 py-2 text-zinc-950 dark:text-white" type="number" :name="`tickets[${index}][full_price_thb]`" x-model="ticket.full_price_thb"></label>
                            <label class="text-sm text-zinc-700 dark:text-zinc-300"><x-t en="Capacity" th="จำนวนจำกัด" /><input class="mt-1 w-full rounded-md border border-zinc-200 dark:border-white/10 bg-white dark:bg-zinc-950 px-3 py-2 text-zinc-950 dark:text-white" type="number" :name="`tickets[${index}][capacity]`" x-model="ticket.capacity"></label>
                            <label class="text-sm text-zinc-700 dark:text-zinc-300"><x-t en="Sale starts" th="เริ่มขาย" /><input class="mt-1 w-full rounded-md border border-zinc-200 dark:border-white/10 bg-white dark:bg-zinc-950 px-3 py-2 text-zinc-950 dark:text-white" type="datetime-local" :name="`tickets[${index}][sale_starts_at]`" x-model="ticket.sale_starts_at" @change="reflectTicketEnd(ticket)"></label>
                            <label class="text-sm text-zinc-700 dark:text-zinc-300"><x-t en="Sale ends" th="สิ้นสุดขาย" /><input class="mt-1 w-full rounded-md border border-zinc-200 dark:border-white/10 bg-white dark:bg-zinc-950 px-3 py-2 text-zinc-950 dark:text-white" type="datetime-local" :name="`tickets[${index}][sale_ends_at]`" x-model="ticket.sale_ends_at"></label>
                        </div>
                        <label class="mt-3 block text-sm text-zinc-700 dark:text-zinc-300"><x-t en="Description" th="รายละเอียด" /><textarea class="mt-1 w-full rounded-md border border-zinc-200 dark:border-white/10 bg-white dark:bg-zinc-950 px-3 py-2 text-zinc-950 dark:text-white" :name="`tickets[${index}][description]`" rows="2" x-model="ticket.description"></textarea></label>
                    </div>
                </template>
            </div>
            <button class="mt-5 inline-flex w-full items-center justify-center gap-2 rounded-md border border-zinc-200 dark:border-white/10 px-4 py-3 font-semibold text-zinc-800 dark:text-zinc-100 hover:border-emerald-300" type="button" @click="addRow()"><x-icon name="plus" /><x-t en="Add ticket type" th="เพิ่มประเภทตั๋ว" /></button>
            <button class="mt-5 inline-flex w-full items-center justify-center gap-2 rounded-md bg-emerald-400 px-4 py-3 font-semibold text-zinc-950"><x-icon name="save" /><x-t en="Save event" th="บันทึกอีเวนต์" /></button>
        </section>
    </form>
</x-layouts.app>
