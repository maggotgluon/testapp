<x-layouts.app title="Scanner">
    <div class="mx-auto max-w-5xl" x-data="scanner({
        events: @js($events->map(fn ($event) => ['id' => $event->id, 'name' => $event->name])->values()),
        recentScans: @js($recentScans),
    })" x-init="init()">
        <div class="pointer-events-none fixed inset-0 z-50 transition-opacity duration-300" :class="flash === 'success' ? 'bg-emerald-400/30 opacity-100' : (flash === 'error' ? 'bg-rose-500/30 opacity-100' : 'opacity-0')"></div>

        <section class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-white/10 dark:bg-white/[0.04]">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <h1 class="inline-flex items-center gap-2 text-3xl font-semibold text-zinc-950 dark:text-white"><x-icon name="scan-line" class="h-7 w-7 text-emerald-500" />Gate scanner / สแกนตั๋วหน้างาน</h1>
                    <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">Scan or paste a ticket UUID. Manual mode shows ticket details first; quick mode applies the selected action immediately. / สแกนหรือวาง UUID ตั๋ว โหมดปกติจะแสดงรายละเอียดก่อน ส่วนโหมดเร็วจะทำรายการทันที</p>
                </div>
                <label class="inline-flex items-center gap-2 rounded-md border border-zinc-200 px-3 py-2 text-sm font-semibold text-zinc-800 dark:border-white/10 dark:text-zinc-100">
                    <input class="rounded border-zinc-300" type="checkbox" x-model="quickMode">
                    Quick mode / โหมดเร็ว
                </label>
            </div>

            <div class="mt-5 grid gap-4 lg:grid-cols-[1fr_.75fr]">
                <div class="grid gap-3">
                    <div class="grid gap-3 sm:grid-cols-2" x-show="quickMode" x-cloak>
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

                    <video x-ref="video" class="hidden aspect-video w-full rounded-lg bg-black" autoplay muted playsinline></video>

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

        <section class="mt-6 rounded-lg border border-zinc-200 bg-white dark:border-white/10 dark:bg-white/[0.04]">
            <div class="border-b border-zinc-200 p-4 dark:border-white/10">
                <h2 class="inline-flex items-center gap-2 text-xl font-semibold text-zinc-950 dark:text-white"><x-icon name="clock" class="h-5 w-5 text-emerald-500" />Latest 20 scans / รายการสแกนล่าสุด 20 รายการ</h2>
            </div>
            <div class="divide-y divide-zinc-200 dark:divide-white/10">
                <template x-for="scan in recentScans" :key="scan.clientId">
                    <div class="grid gap-2 p-4 sm:grid-cols-[1fr_auto]">
                        <div>
                            <div class="font-semibold text-zinc-950 dark:text-white" x-text="scan.ticket?.holder || 'Unknown ticket / ไม่ทราบตั๋ว'"></div>
                            <div class="mt-1 text-sm text-zinc-600 dark:text-zinc-400" x-text="scan.ticket ? `${scan.ticket.event} · ${scan.ticket.type} · ${scan.ticket.order_number || '-'}` : scan.message"></div>
                        </div>
                        <div class="flex flex-wrap items-center gap-2 sm:justify-end">
                            <span class="rounded px-3 py-1 text-sm font-semibold" :class="scan.ok ? 'bg-emerald-400/10 text-emerald-700 dark:text-emerald-200' : 'bg-rose-400/10 text-rose-700 dark:text-rose-200'" x-text="scan.ticket ? statusLabel(scan.ticket.status) : 'invalid / ไม่ถูกต้อง'"></span>
                            <span class="font-mono text-xs text-zinc-500" x-text="scan.scanned_at"></span>
                        </div>
                    </div>
                </template>
                <div class="p-6 text-sm text-zinc-600 dark:text-zinc-400" x-show="recentScans.length === 0">No scans yet. / ยังไม่มีรายการสแกน</div>
            </div>
        </section>
    </div>
</x-layouts.app>
