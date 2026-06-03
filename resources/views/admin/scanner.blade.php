<x-layouts.app title="Scanner">
    <div class="mx-auto max-w-5xl" x-data="scanner({
        events: @js($events->map(fn ($event) => ['id' => $event->id, 'name' => $event->name])->values()),
        recentScans: @js($recentScans),
        recentLimit: @js($perPage),
    })" x-init="init()">
        <div class="pointer-events-none fixed inset-0 z-50 transition-opacity duration-300" :class="flash === 'success' ? 'bg-emerald-400/30 opacity-100' : (flash === 'error' ? 'bg-rose-500/30 opacity-100' : 'opacity-0')"></div>

        <section class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-white/10 dark:bg-white/[0.04]">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <h1 class="inline-flex items-center gap-2 text-3xl font-semibold text-zinc-950 dark:text-white"><x-icon name="scan-line" class="h-7 w-7 text-emerald-500" />Gate scanner / สแกนตั๋วหน้างาน</h1>
                    <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">Scan or paste a ticket UUID. Manual mode shows ticket details first; quick mode applies the selected action immediately. / สแกนหรือวาง UUID ตั๋ว โหมดปกติจะแสดงรายละเอียดก่อน ส่วนโหมดเร็วจะทำรายการทันที</p>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <button class="inline-flex items-center gap-2 rounded-md border border-zinc-200 px-3 py-2 text-sm font-semibold text-zinc-800 dark:border-white/10 dark:text-zinc-100" type="button" @click="guideOpen = true"><x-icon name="sparkles" />Guide / คู่มือ</button>
                    <button class="inline-flex items-center gap-2 rounded-md border border-zinc-200 px-3 py-2 text-sm font-semibold text-zinc-800 dark:border-white/10 dark:text-zinc-100" type="button" @click="quickSettingsOpen = !quickSettingsOpen"><x-icon name="sliders-horizontal" /><span x-text="TicketFlowLanguage.format(quickSettingsOpen ? { en: 'Hide settings', th: 'ซ่อนตั้งค่า' } : { en: 'Show settings', th: 'แสดงตั้งค่า' })"></span></button>
                    <label class="inline-flex items-center gap-2 rounded-md border border-zinc-200 px-3 py-2 text-sm font-semibold text-zinc-800 dark:border-white/10 dark:text-zinc-100">
                        <input class="rounded border-zinc-300" type="checkbox" x-model="quickMode">
                        Quick mode / โหมดเร็ว
                    </label>
                </div>
            </div>

            <div class="mt-5 grid gap-4 lg:grid-cols-[1fr_.75fr]">
                <div class="grid gap-3">
                    <div class="overflow-hidden rounded-lg border border-zinc-200 bg-zinc-950 dark:border-white/10">
                        <video x-ref="video" class="hidden min-h-[360px] max-h-[72vh] w-full bg-black object-contain" autoplay muted playsinline></video>
                        <div class="grid min-h-[260px] place-items-center p-6 text-center text-zinc-300" x-show="$refs.video?.classList?.contains('hidden')">
                            <div>
                                <x-icon name="camera" class="mx-auto h-10 w-10 text-emerald-300" />
                                <p class="mt-3 text-sm"><x-t en="Camera preview will appear here." th="ตัวอย่างกล้องจะแสดงตรงนี้" /></p>
                                <button @click="startCamera()" class="mt-4 inline-flex items-center gap-2 rounded-md bg-emerald-400 px-4 py-2 font-semibold text-zinc-950" type="button"><x-icon name="camera" /><x-t en="Start camera" th="เปิดกล้อง" /></button>
                            </div>
                        </div>
                    </div>

                    <div class="grid gap-3 rounded-md border border-zinc-200 bg-zinc-50 p-4 dark:border-white/10 dark:bg-zinc-900 sm:grid-cols-2" x-show="quickSettingsOpen" x-cloak x-transition>
                        <label class="text-sm font-medium text-zinc-700 dark:text-zinc-200">Event / อีเวนต์
                            <select class="mt-1 w-full rounded-md border border-zinc-200 bg-white px-3 py-3 text-zinc-950 dark:border-white/10 dark:bg-zinc-950 dark:text-white" x-model="selectedEventId">
                                <option value="">Select event / เลือกอีเวนต์</option>
                                <template x-for="event in events" :key="event.id">
                                    <option :value="event.id" x-text="event.name"></option>
                                </template>
                            </select>
                        </label>
                        <label class="text-sm font-medium text-zinc-700 dark:text-zinc-200">Action / สถานะ
                            <select class="mt-1 w-full rounded-md border border-zinc-200 bg-white px-3 py-3 text-zinc-950 dark:border-white/10 dark:bg-zinc-950 dark:text-white" x-model="selectedAction">
                                <option value="view">View / ดูข้อมูล</option>
                                <option value="check_in">Check in / เช็กอิน</option>
                                <option value="check_out">Check out / เช็กเอาต์</option>
                            </select>
                        </label>
                    </div>

                    <label class="text-sm font-medium text-zinc-700 dark:text-zinc-200">Ticket UUID / UUID ตั๋ว
                        <input x-ref="codeInput" x-model="code" @keydown.enter.prevent="handleScan()" class="mt-1 w-full rounded-md border border-zinc-200 bg-white px-3 py-3 font-mono text-zinc-950 dark:border-white/10 dark:bg-zinc-950 dark:text-white" placeholder="Ticket UUID or scanned URL / UUID หรือ URL ตั๋ว">
                    </label>

                    <div class="flex flex-wrap gap-2">
                        <button @click="handleScan()" class="inline-flex items-center gap-2 rounded-md bg-emerald-400 px-4 py-2 font-semibold text-zinc-950" type="button"><x-icon name="search" />Scan / ค้นตั๋ว</button>
                        <button @click="submit('check_in')" class="inline-flex items-center gap-2 rounded-md border border-emerald-300 px-4 py-2 font-semibold text-emerald-700 disabled:cursor-not-allowed disabled:opacity-50 dark:border-emerald-400/40 dark:text-emerald-200" type="button" x-show="!quickMode" :disabled="!canCheckIn()"><x-icon name="check" />Check in / เช็กอิน</button>
                        <button @click="submit('check_out')" class="inline-flex items-center gap-2 rounded-md border border-zinc-200 px-4 py-2 font-semibold text-zinc-800 disabled:cursor-not-allowed disabled:opacity-50 dark:border-white/10 dark:text-zinc-100" type="button" x-show="!quickMode" :disabled="!canCheckOut()"><x-icon name="log-out" />Check out / เช็กเอาต์</button>
                        <button @click="startCamera()" class="inline-flex items-center gap-2 rounded-md border border-zinc-200 px-4 py-2 text-zinc-800 dark:border-white/10 dark:text-zinc-100" type="button"><x-icon name="camera" />Camera / กล้อง</button>
                    </div>

                    <template x-if="message">
                        <div class="rounded-md border p-4 text-sm" :class="ok ? 'border-emerald-400/30 bg-emerald-400/10 text-emerald-800 dark:text-emerald-100' : 'border-rose-400/30 bg-rose-400/10 text-rose-800 dark:text-rose-100'" x-text="message"></div>
                    </template>
                </div>

                <aside class="rounded-lg border border-zinc-200 bg-zinc-50 p-5 dark:border-white/10 dark:bg-zinc-900">
                    <h2 class="inline-flex items-center gap-2 text-lg font-semibold text-zinc-950 dark:text-white"><x-icon name="ticket" class="h-5 w-5 text-emerald-500" />Ticket detail / รายละเอียดตั๋ว</h2>
                    <template x-if="currentTicket">
                        <dl class="mt-4 grid gap-3 text-sm">
                            <div><dt class="text-zinc-500">Holder / ผู้ถือบัตร</dt><dd class="font-semibold text-zinc-950 dark:text-white" x-text="currentTicket.holder"></dd></div>
                            <div><dt class="text-zinc-500">Event / อีเวนต์</dt><dd class="text-zinc-800 dark:text-zinc-100" x-text="currentTicket.event"></dd></div>
                            <div><dt class="text-zinc-500">Ticket type / ประเภทตั๋ว</dt><dd class="text-zinc-800 dark:text-zinc-100" x-text="currentTicket.type"></dd></div>
                            <div><dt class="text-zinc-500">Order / ออเดอร์</dt><dd class="font-mono text-zinc-800 dark:text-zinc-100" x-text="currentTicket.order_number || '-'"></dd></div>
                            <div><dt class="text-zinc-500">Current status / สถานะปัจจุบัน</dt><dd><span class="rounded bg-white px-2 py-1 font-semibold text-emerald-700 dark:bg-white/10 dark:text-emerald-200" x-text="statusLabel(currentTicket.status)"></span></dd></div>
                            <div class="grid grid-cols-2 gap-3">
                                <div><dt class="text-zinc-500">Checked in / เข้า</dt><dd class="text-zinc-800 dark:text-zinc-100" x-text="currentTicket.checked_in_at || '-'"></dd></div>
                                <div><dt class="text-zinc-500">Checked out / ออก</dt><dd class="text-zinc-800 dark:text-zinc-100" x-text="currentTicket.checked_out_at || '-'"></dd></div>
                            </div>
                        </dl>
                    </template>
                    <div class="mt-4 rounded-md border border-dashed border-zinc-300 p-4 text-sm text-zinc-600 dark:border-white/10 dark:text-zinc-400" x-show="!currentTicket">No ticket scanned yet. / ยังไม่ได้สแกนตั๋ว</div>
                </aside>
            </div>
        </section>

        <div class="fixed inset-0 z-50 grid place-items-center bg-zinc-950/70 p-4" x-cloak x-show="ticketModalOpen" x-transition>
            <div class="w-full max-w-lg rounded-lg border border-zinc-200 bg-white p-5 shadow-xl dark:border-white/10 dark:bg-zinc-950">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h2 class="inline-flex items-center gap-2 text-xl font-semibold text-zinc-950 dark:text-white"><x-icon name="ticket" class="text-emerald-500" />Scan result / ผลการสแกน</h2>
                        <p class="mt-1 text-sm" :class="modalOk ? 'text-emerald-700 dark:text-emerald-200' : 'text-rose-700 dark:text-rose-200'" x-text="modalMessage"></p>
                    </div>
                    <button class="rounded-md p-2 text-zinc-500 hover:bg-zinc-100 dark:hover:bg-white/10" type="button" @click="ticketModalOpen = false" aria-label="Close / ปิด"><x-icon name="x" /></button>
                </div>
                <template x-if="modalTicket">
                    <dl class="mt-5 grid gap-3 text-sm">
                        <div><dt class="text-zinc-500">Holder / ผู้ถือบัตร</dt><dd class="font-semibold text-zinc-950 dark:text-white" x-text="modalTicket.holder"></dd></div>
                        <div><dt class="text-zinc-500">Event / อีเวนต์</dt><dd class="text-zinc-800 dark:text-zinc-100" x-text="modalTicket.event"></dd></div>
                        <div><dt class="text-zinc-500">Ticket type / ประเภทตั๋ว</dt><dd class="text-zinc-800 dark:text-zinc-100" x-text="modalTicket.type"></dd></div>
                        <div><dt class="text-zinc-500">Order / ออเดอร์</dt><dd class="font-mono text-zinc-800 dark:text-zinc-100" x-text="modalTicket.order_number || '-'"></dd></div>
                        <div><dt class="text-zinc-500">Current status / สถานะปัจจุบัน</dt><dd><span class="rounded bg-zinc-100 px-2 py-1 font-semibold text-emerald-700 dark:bg-white/10 dark:text-emerald-200" x-text="statusLabel(modalTicket.status)"></span></dd></div>
                    </dl>
                </template>
                <div class="mt-5 flex flex-wrap gap-2">
                    <button class="inline-flex items-center gap-2 rounded-md bg-emerald-400 px-4 py-2 font-semibold text-zinc-950 disabled:cursor-not-allowed disabled:opacity-50" type="button" :disabled="!canModalCheckIn()" @click="modalSubmit('check_in')"><x-icon name="check" />Check in / เช็กอิน</button>
                    <button class="inline-flex items-center gap-2 rounded-md border border-zinc-200 px-4 py-2 font-semibold text-zinc-800 disabled:cursor-not-allowed disabled:opacity-50 dark:border-white/10 dark:text-zinc-100" type="button" :disabled="!canModalCheckOut()" @click="modalSubmit('check_out')"><x-icon name="log-out" />Check out / เช็กเอาต์</button>
                    <button class="inline-flex items-center gap-2 rounded-md border border-zinc-200 px-4 py-2 font-semibold text-zinc-800 dark:border-white/10 dark:text-zinc-100" type="button" @click="ticketModalOpen = false"><x-icon name="x" />Close / ปิด</button>
                </div>
            </div>
        </div>

        <div class="fixed inset-0 z-50 grid place-items-center bg-zinc-950/70 p-4" x-cloak x-show="guideOpen" x-transition>
            <div class="max-h-[90vh] w-full max-w-2xl overflow-y-auto rounded-lg border border-zinc-200 bg-white p-5 shadow-xl dark:border-white/10 dark:bg-zinc-950">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h2 class="inline-flex items-center gap-2 text-xl font-semibold text-zinc-950 dark:text-white"><x-icon name="sparkles" class="text-emerald-500" />Check-in guide / คู่มือเช็กอิน</h2>
                        <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">Use this flow at the gate for fast, accurate ticket handling. / ใช้ขั้นตอนนี้ที่หน้างานเพื่อสแกนตั๋วได้เร็วและแม่นยำ</p>
                    </div>
                    <button class="rounded-md p-2 text-zinc-500 hover:bg-zinc-100 dark:hover:bg-white/10" type="button" @click="guideOpen = false" aria-label="Close / ปิด"><x-icon name="x" /></button>
                </div>
                <div class="mt-5 grid gap-3 text-sm text-zinc-700 dark:text-zinc-300">
                    <div class="rounded-md bg-zinc-50 p-4 dark:bg-white/5"><strong>1. Open camera / เปิดกล้อง</strong><p class="mt-1">Tap Camera and point at the QR or barcode until the scanner fills the ticket code. / กดกล้องแล้วเล็งไปที่ QR หรือบาร์โค้ด</p></div>
                    <div class="rounded-md bg-zinc-50 p-4 dark:bg-white/5"><strong>2. View first / ดูข้อมูลก่อน</strong><p class="mt-1">Use View in quick mode to open the ticket popup without changing status. / ใช้ View ในโหมดเร็วเพื่อดูข้อมูลก่อนเปลี่ยนสถานะ</p></div>
                    <div class="rounded-md bg-zinc-50 p-4 dark:bg-white/5"><strong>3. Check in or out / เช็กอินหรือเช็กเอาต์</strong><p class="mt-1">Use the popup action buttons after confirming holder, event, ticket type, and current status. / กดยืนยันจากปุ่มใน popup หลังตรวจสอบข้อมูลแล้ว</p></div>
                </div>
            </div>
        </div>

        <section class="mt-6 rounded-lg border border-zinc-200 bg-white dark:border-white/10 dark:bg-white/[0.04]">
            <div class="grid gap-4 border-b border-zinc-200 p-4 dark:border-white/10 lg:grid-cols-[1fr_auto]">
                <div>
                    <h2 class="inline-flex items-center gap-2 text-xl font-semibold text-zinc-950 dark:text-white"><x-icon name="clock" class="h-5 w-5 text-emerald-500" />Latest {{ $perPage }} scans / รายการสแกนล่าสุด {{ $perPage }} รายการ</h2>
                    <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">Click a scan row to open the ticket details. / คลิกแถวรายการสแกนเพื่อเปิดรายละเอียดตั๋ว</p>
                </div>
                <div class="flex items-start justify-end">
                    <button class="inline-flex items-center gap-2 rounded-md border border-zinc-200 px-3 py-2 text-sm font-semibold text-zinc-800 dark:border-white/10 dark:text-zinc-100" type="button" @click="scanFiltersOpen = !scanFiltersOpen"><x-icon name="filter" /><span x-text="TicketFlowLanguage.format(scanFiltersOpen ? { en: 'Hide filters', th: 'ซ่อนตัวกรอง' } : { en: 'Show filters', th: 'แสดงตัวกรอง' })"></span></button>
                </div>
                <form class="grid gap-2 sm:grid-cols-2 lg:col-span-2 lg:grid-cols-4" x-show="scanFiltersOpen" x-cloak x-transition>
                    <label class="text-xs font-semibold text-zinc-600 dark:text-zinc-300">Event / อีเวนต์
                        <select class="mt-1 w-full rounded-md border border-zinc-200 bg-white px-3 py-2 text-sm text-zinc-950 dark:border-white/10 dark:bg-zinc-950 dark:text-white" name="event_id" onchange="this.form.submit()">
                            <option value="">All events / ทุกอีเวนต์</option>
                            @foreach($events as $event)
                                <option value="{{ $event->id }}" @selected(request('event_id') == $event->id)>{{ $event->name }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="text-xs font-semibold text-zinc-600 dark:text-zinc-300">Action / รายการ
                        <select class="mt-1 w-full rounded-md border border-zinc-200 bg-white px-3 py-2 text-sm text-zinc-950 dark:border-white/10 dark:bg-zinc-950 dark:text-white" name="action" onchange="this.form.submit()">
                            <option value="">All actions / ทุกการทำรายการ</option>
                            <option value="check_in" @selected(request('action') === 'check_in')>Check in / เช็กอิน</option>
                            <option value="check_out" @selected(request('action') === 'check_out')>Check out / เช็กเอาต์</option>
                        </select>
                    </label>
                    <label class="text-xs font-semibold text-zinc-600 dark:text-zinc-300">Status / สถานะ
                        <select class="mt-1 w-full rounded-md border border-zinc-200 bg-white px-3 py-2 text-sm text-zinc-950 dark:border-white/10 dark:bg-zinc-950 dark:text-white" name="ticket_status" onchange="this.form.submit()">
                            <option value="">All statuses / ทุกสถานะ</option>
                            @foreach(['approved', 'checked_in', 'checked_out', 'expired', 'rejected', 'cancelled', 'refunded'] as $status)
                                <option value="{{ $status }}" @selected(request('ticket_status') === $status)>{{ str_replace('_', ' ', $status) }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="text-xs font-semibold text-zinc-600 dark:text-zinc-300">Show / แสดง
                        <select class="mt-1 w-full rounded-md border border-zinc-200 bg-white px-3 py-2 text-sm text-zinc-950 dark:border-white/10 dark:bg-zinc-950 dark:text-white" name="per_page" onchange="this.form.submit()">
                            @foreach([10, 20, 50, 100] as $count)
                                <option value="{{ $count }}" @selected($perPage === $count)>{{ $count }}</option>
                            @endforeach
                        </select>
                    </label>
                </form>
            </div>
            <div class="divide-y divide-zinc-200 dark:divide-white/10">
                <template x-for="scan in recentScans" :key="scan.clientId">
                    <div class="interactive-row group grid gap-2 p-4 sm:grid-cols-[1fr_auto]">
                        <template x-if="scan.ticket?.url">
                            <a class="click-area-link" :href="scan.ticket.url" :aria-label="`Open ticket for ${scan.ticket.holder}`"></a>
                        </template>
                        <div class="click-area-content">
                            <div class="font-semibold text-zinc-950 group-hover:text-emerald-700 dark:text-white" x-text="scan.ticket?.holder || 'Unknown ticket / ไม่ทราบตั๋ว'"></div>
                            <div class="mt-1 text-sm text-zinc-600 dark:text-zinc-400" x-text="scan.ticket ? `${scan.ticket.event} · ${scan.ticket.type} · ${scan.ticket.order_number || '-'}` : scan.message"></div>
                        </div>
                        <div class="click-area-content flex flex-wrap items-center gap-2 sm:justify-end">
                            <span class="rounded px-3 py-1 text-sm font-semibold" :class="scan.ok ? 'bg-emerald-400/10 text-emerald-700 dark:text-emerald-200' : 'bg-rose-400/10 text-rose-700 dark:text-rose-200'" x-text="scan.ticket ? statusLabel(scan.ticket.status) : 'invalid / ไม่ถูกต้อง'"></span>
                            <span class="font-mono text-xs text-zinc-500" x-text="scan.scanned_at"></span>
                        </div>
                    </div>
                </template>
                <div class="p-6 text-sm text-zinc-600 dark:text-zinc-400" x-show="recentScans.length === 0">No scans yet. / ยังไม่มีรายการสแกน</div>
            </div>
            <div class="p-4">{{ $recentScanLogs->links() }}</div>
        </section>
    </div>
</x-layouts.app>
