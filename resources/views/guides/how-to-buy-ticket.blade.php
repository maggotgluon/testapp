@php
    $description = 'A friendly Thai and English guide for buying event tickets, paying by QR or bank transfer, uploading a payment slip, and finding the ticket later.';
    $structuredData = [
        '@context' => 'https://schema.org',
        '@type' => 'HowTo',
        'name' => 'How to buy an event ticket',
        'description' => $description,
        'step' => [
            ['@type' => 'HowToStep', 'name' => 'Login or continue', 'text' => 'Use LINE to save tickets automatically, or continue as a guest and keep the order number.'],
            ['@type' => 'HowToStep', 'name' => 'Choose tickets', 'text' => 'Choose ticket type, quantity, holder names, and coupon code when available.'],
            ['@type' => 'HowToStep', 'name' => 'Pay and upload slip', 'text' => 'Pay by QR payment or bank transfer, then upload the payment slip.'],
            ['@type' => 'HowToStep', 'name' => 'Wait for approval', 'text' => 'After admin approval, the digital ticket QR code becomes active.'],
            ['@type' => 'HowToStep', 'name' => 'Show ticket at gate', 'text' => 'Show the ticket QR code at the event gate for check-in.'],
        ],
    ];
@endphp
<x-layouts.app
    title="How to buy tickets / วิธีซื้อตั๋ว"
    :meta-description="$description"
    :canonical-url="route('guides.buy-ticket')"
    :structured-data="$structuredData"
>
    <section class="grid gap-6 lg:grid-cols-[1fr_.85fr]">
        <div>
            <p class="inline-flex items-center gap-2 text-sm font-semibold uppercase text-emerald-600 dark:text-emerald-300"><x-icon name="sparkles" />Customer guide / คู่มือลูกค้า</p>
            <h1 class="mt-3 max-w-4xl text-4xl font-semibold tracking-tight text-zinc-950 dark:text-white sm:text-5xl">Buy your event ticket step by step / ขั้นตอนการซื้อตั๋วอีเวนต์</h1>
            <p class="mt-4 max-w-3xl text-zinc-600 dark:text-zinc-400">Follow these simple steps from choosing an event to showing your ticket QR at the gate. / ทำตามขั้นตอนง่าย ๆ ตั้งแต่เลือกอีเวนต์จนถึงการแสดง QR ตั๋วหน้างาน</p>
            <div class="mt-5 flex flex-wrap gap-2">
                <a class="inline-flex items-center gap-2 rounded-md bg-emerald-400 px-4 py-2 font-semibold text-zinc-950 hover:bg-emerald-300" href="{{ route('events.index') }}"><x-icon name="ticket" />Browse events / เลือกอีเวนต์</a>
                <a class="inline-flex items-center gap-2 rounded-md border border-zinc-200 px-4 py-2 font-semibold text-zinc-800 hover:border-emerald-300 dark:border-white/10 dark:text-zinc-100" href="{{ route('orders.lookup') }}"><x-icon name="search" />Find order / ค้นหาออเดอร์</a>
            </div>
        </div>

        <div class="rounded-lg border border-zinc-200 bg-white p-4 dark:border-white/10 dark:bg-white/[0.04]">
            <div class="rounded-md border border-zinc-200 bg-zinc-50 p-4 dark:border-white/10 dark:bg-zinc-900">
                <div class="flex items-center justify-between gap-3">
                    <div class="inline-flex items-center gap-2 font-semibold text-zinc-950 dark:text-white"><x-icon name="ticket" class="text-emerald-500" />TicketFlow</div>
                    <span class="rounded bg-emerald-400 px-2 py-1 text-xs font-semibold text-zinc-950">Guide</span>
                </div>
                <div class="mt-4 grid gap-3">
                    <div class="rounded-md border border-zinc-200 bg-white p-3 dark:border-white/10 dark:bg-zinc-950">
                        <div class="text-sm font-semibold text-zinc-950 dark:text-white">Reserve Your Spot / เลือกตั๋ว</div>
                        <div class="mt-3 grid grid-cols-[1fr_auto] items-center gap-3 text-sm text-zinc-600 dark:text-zinc-400">
                            <span>Early Bird Ticket</span>
                            <span class="font-semibold text-emerald-600 dark:text-emerald-300">THB 690</span>
                        </div>
                    </div>
                    <div class="rounded-md border border-emerald-400/30 bg-emerald-400/10 p-3 text-sm text-emerald-950 dark:text-emerald-50">
                        QR payment / ชำระด้วย QR
                        <div class="mt-2 font-semibold">Upload slip / แนบสลิป</div>
                    </div>
                    <div class="rounded-md border border-zinc-200 bg-white p-3 text-sm dark:border-white/10 dark:bg-zinc-950">
                        <div class="font-semibold text-zinc-950 dark:text-white">Ticket QR ready after approval / QR พร้อมหลังอนุมัติ</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="mt-8 grid gap-3 md:grid-cols-2 xl:grid-cols-4">
        <div class="rounded-lg border border-zinc-200 bg-white p-4 dark:border-white/10 dark:bg-white/[0.04]">
            <div class="flex items-center gap-3">
                <span class="grid h-10 w-10 shrink-0 place-items-center rounded-md bg-emerald-400 text-zinc-950"><x-icon name="log-in" /></span>
                <span class="font-mono text-sm font-semibold text-emerald-700 dark:text-emerald-200">01</span>
            </div>
            <h2 class="mt-3 font-semibold text-zinc-950 dark:text-white">Login or continue / เข้าสู่ระบบหรือซื้อเลย</h2>
            <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">Use LINE to save tickets automatically, or continue as a guest and keep your order number. / ใช้ LINE เพื่อบันทึกตั๋วอัตโนมัติ หรือซื้อแบบผู้เยี่ยมชมและเก็บเลขออเดอร์ไว้</p>
        </div>

        <div class="rounded-lg border border-zinc-200 bg-white p-4 dark:border-white/10 dark:bg-white/[0.04]">
            <div class="flex items-center gap-3">
                <span class="grid h-10 w-10 shrink-0 place-items-center rounded-md bg-sky-400 text-zinc-950"><x-icon name="ticket" /></span>
                <span class="font-mono text-sm font-semibold text-sky-700 dark:text-sky-200">02</span>
            </div>
            <h2 class="mt-3 font-semibold text-zinc-950 dark:text-white">Choose tickets / เลือกตั๋ว</h2>
            <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">Pick ticket types, adjust quantity, add holder names, and use a coupon if one is available. / เลือกประเภทตั๋ว เพิ่มจำนวน ใส่ชื่อผู้ถือบัตร และใช้คูปองถ้ามี</p>
        </div>

        <div class="rounded-lg border border-zinc-200 bg-white p-4 dark:border-white/10 dark:bg-white/[0.04]">
            <div class="flex items-center gap-3">
                <span class="grid h-10 w-10 shrink-0 place-items-center rounded-md bg-amber-300 text-zinc-950"><x-icon name="wallet" /></span>
                <span class="font-mono text-sm font-semibold text-amber-700 dark:text-amber-200">03</span>
            </div>
            <h2 class="mt-3 font-semibold text-zinc-950 dark:text-white">Pay and upload slip / ชำระเงินและแนบสลิป</h2>
            <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">Pay by QR or bank transfer, then attach your payment slip before submitting the order. / ชำระด้วย QR หรือโอนธนาคาร แล้วแนบสลิปก่อนส่งคำสั่งซื้อ</p>
        </div>

        <div class="rounded-lg border border-zinc-200 bg-white p-4 dark:border-white/10 dark:bg-white/[0.04]">
            <div class="flex items-center gap-3">
                <span class="grid h-10 w-10 shrink-0 place-items-center rounded-md bg-lime-300 text-zinc-950"><x-icon name="check" /></span>
                <span class="font-mono text-sm font-semibold text-lime-700 dark:text-lime-200">04</span>
            </div>
            <h2 class="mt-3 font-semibold text-zinc-950 dark:text-white">Wait for approval / รออนุมัติ</h2>
            <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">The team checks your payment. Once approved, your ticket QR will be ready to use. / ทีมงานตรวจสอบการชำระเงิน เมื่ออนุมัติแล้ว QR ตั๋วจะพร้อมใช้งาน</p>
        </div>
    </section>

    <section class="mt-6 grid gap-3 lg:grid-cols-3">
        <div class="rounded-lg border border-zinc-200 bg-zinc-50 p-4 dark:border-white/10 dark:bg-zinc-900">
            <div class="inline-flex items-center gap-2 font-semibold text-zinc-950 dark:text-white"><x-icon name="receipt" class="text-emerald-600 dark:text-emerald-300" />Keep your order / เก็บออเดอร์ไว้</div>
            <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">Use your order number and phone to check status anytime. / ใช้เลขออเดอร์และเบอร์โทรเพื่อตรวจสอบสถานะได้ทุกเวลา</p>
        </div>
        <div class="rounded-lg border border-zinc-200 bg-zinc-50 p-4 dark:border-white/10 dark:bg-zinc-900">
            <div class="inline-flex items-center gap-2 font-semibold text-zinc-950 dark:text-white"><x-icon name="qr-code" class="text-sky-600 dark:text-sky-300" />Show ticket QR / แสดง QR ตั๋ว</div>
            <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">Open your approved ticket and show the QR at the gate. / เปิดตั๋วที่อนุมัติแล้วและแสดง QR ที่หน้างาน</p>
        </div>
        <div class="rounded-lg border border-zinc-200 bg-zinc-50 p-4 dark:border-white/10 dark:bg-zinc-900">
            <div class="inline-flex items-center gap-2 font-semibold text-zinc-950 dark:text-white"><x-icon name="calendar-plus" class="text-amber-600 dark:text-amber-200" />Get event-ready / พร้อมไปงาน</div>
            <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">Add the event to your calendar, open the map, and let staff scan your QR for check-in. / เพิ่มลงปฏิทิน เปิดแผนที่ และให้ทีมงานสแกน QR เพื่อเช็กอิน</p>
        </div>
    </section>
</x-layouts.app>
