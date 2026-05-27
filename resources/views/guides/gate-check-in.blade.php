@php
    $description = 'A friendly Thai and English guide for event staff using the ticket scanner to check attendees in and out quickly and accurately.';
    $structuredData = [
        '@context' => 'https://schema.org',
        '@type' => 'HowTo',
        'name' => 'How to check in tickets at the gate',
        'description' => $description,
        'step' => [
            ['@type' => 'HowToStep', 'name' => 'Open scanner', 'text' => 'Login with a staff account, open Admin, and choose Scanner.'],
            ['@type' => 'HowToStep', 'name' => 'Scan ticket QR', 'text' => 'Use the camera, scan a printed QR, or paste the ticket URL or UUID.'],
            ['@type' => 'HowToStep', 'name' => 'Confirm details', 'text' => 'Check holder name, event, ticket type, order number, and current status.'],
            ['@type' => 'HowToStep', 'name' => 'Tap check in', 'text' => 'If the ticket is approved, tap Check in and wait for success feedback.'],
        ],
    ];
@endphp
<x-layouts.app
    title="Gate check-in guide / วิธีเช็กอินหน้างาน"
    :meta-description="$description"
    :canonical-url="route('guides.gate-check-in')"
    :structured-data="$structuredData"
>
    <section class="grid gap-6 lg:grid-cols-[1fr_.85fr]">
        <div>
            <p class="inline-flex items-center gap-2 text-sm font-semibold uppercase text-emerald-600 dark:text-emerald-300"><x-icon name="scan-line" />Gate staff guide / คู่มือทีมหน้างาน</p>
            <h1 class="mt-3 max-w-4xl text-4xl font-semibold tracking-tight text-zinc-950 dark:text-white sm:text-5xl">Check in tickets at the gate / วิธีเช็กอินตั๋วหน้างาน</h1>
            <p class="mt-4 max-w-3xl text-zinc-600 dark:text-zinc-400">Use the scanner page to scan a guest ticket QR, confirm the ticket details, and check the guest in smoothly. / ใช้หน้าสแกนเพื่อสแกน QR ตั๋ว ตรวจสอบรายละเอียด และเช็กอินผู้เข้าร่วมงานได้อย่างราบรื่น</p>
            <div class="mt-5 flex flex-wrap gap-2">
                <a class="inline-flex items-center gap-2 rounded-md bg-emerald-400 px-4 py-2 font-semibold text-zinc-950 hover:bg-emerald-300" href="{{ route('admin.scanner') }}"><x-icon name="scan-line" />Open scanner / เปิดหน้าสแกน</a>
                <a class="inline-flex items-center gap-2 rounded-md border border-zinc-200 px-4 py-2 font-semibold text-zinc-800 hover:border-emerald-300 dark:border-white/10 dark:text-zinc-100" href="{{ route('guides.buy-ticket') }}"><x-icon name="ticket" />Customer guide / วิธีซื้อตั๋ว</a>
            </div>
        </div>

        <div class="rounded-lg border border-zinc-200 bg-white p-4 dark:border-white/10 dark:bg-white/[0.04]">
            <div class="rounded-md border border-zinc-200 bg-zinc-50 p-4 dark:border-white/10 dark:bg-zinc-900">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div class="inline-flex items-center gap-2 font-semibold text-zinc-950 dark:text-white"><x-icon name="scan-line" class="text-emerald-500" />Gate scanner / สแกนตั๋ว</div>
                    <span class="rounded bg-emerald-400 px-2 py-1 text-xs font-semibold text-zinc-950">Ready</span>
                </div>
                <label class="mt-4 block text-sm font-medium text-zinc-700 dark:text-zinc-200">Ticket UUID / UUID ตั๋ว</label>
                <div class="mt-1 rounded-md border border-zinc-200 bg-white px-3 py-3 font-mono text-sm text-zinc-500 dark:border-white/10 dark:bg-zinc-950">Ticket UUID or scanned URL</div>
                <div class="mt-3 flex flex-wrap gap-2">
                    <span class="inline-flex items-center gap-2 rounded-md bg-emerald-400 px-3 py-2 text-sm font-semibold text-zinc-950"><x-icon name="search" />Scan</span>
                    <span class="inline-flex items-center gap-2 rounded-md border border-emerald-300 px-3 py-2 text-sm font-semibold text-emerald-700 dark:border-emerald-400/40 dark:text-emerald-200"><x-icon name="check" />Check in</span>
                    <span class="inline-flex items-center gap-2 rounded-md border border-zinc-200 px-3 py-2 text-sm text-zinc-800 dark:border-white/10 dark:text-zinc-100"><x-icon name="camera" />Camera</span>
                </div>
            </div>
        </div>
    </section>

    <section class="mt-8 grid gap-3 md:grid-cols-2 xl:grid-cols-4">
        <div class="rounded-lg border border-zinc-200 bg-white p-4 dark:border-white/10 dark:bg-white/[0.04]">
            <div class="flex items-center gap-3">
                <span class="grid h-10 w-10 shrink-0 place-items-center rounded-md bg-emerald-400 text-zinc-950"><x-icon name="shield" /></span>
                <span class="font-mono text-sm font-semibold text-emerald-700 dark:text-emerald-200">01</span>
            </div>
            <h2 class="mt-3 font-semibold text-zinc-950 dark:text-white">Open scanner / เปิดหน้าสแกน</h2>
            <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">Login with a staff account, then open Admin and choose Scanner. / เข้าสู่ระบบด้วยบัญชีทีมงาน จากนั้นเปิด Admin และเลือก Scanner</p>
        </div>

        <div class="rounded-lg border border-zinc-200 bg-white p-4 dark:border-white/10 dark:bg-white/[0.04]">
            <div class="flex items-center gap-3">
                <span class="grid h-10 w-10 shrink-0 place-items-center rounded-md bg-sky-400 text-zinc-950"><x-icon name="camera" /></span>
                <span class="font-mono text-sm font-semibold text-sky-700 dark:text-sky-200">02</span>
            </div>
            <h2 class="mt-3 font-semibold text-zinc-950 dark:text-white">Scan ticket QR / สแกน QR ตั๋ว</h2>
            <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">Use the camera button, scan a printed QR, or paste the ticket URL/UUID into the field. / กดปุ่มกล้อง สแกน QR จากหน้าจอหรือบัตรพิมพ์ หรือวาง URL/UUID ตั๋วในช่อง</p>
        </div>

        <div class="rounded-lg border border-zinc-200 bg-white p-4 dark:border-white/10 dark:bg-white/[0.04]">
            <div class="flex items-center gap-3">
                <span class="grid h-10 w-10 shrink-0 place-items-center rounded-md bg-amber-300 text-zinc-950"><x-icon name="ticket" /></span>
                <span class="font-mono text-sm font-semibold text-amber-700 dark:text-amber-200">03</span>
            </div>
            <h2 class="mt-3 font-semibold text-zinc-950 dark:text-white">Confirm details / ตรวจสอบข้อมูล</h2>
            <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">Check holder name, event, ticket type, order number, and current status before check-in. / ตรวจชื่อผู้ถือบัตร อีเวนต์ ประเภทตั๋ว เลขออเดอร์ และสถานะก่อนเช็กอิน</p>
        </div>

        <div class="rounded-lg border border-zinc-200 bg-white p-4 dark:border-white/10 dark:bg-white/[0.04]">
            <div class="flex items-center gap-3">
                <span class="grid h-10 w-10 shrink-0 place-items-center rounded-md bg-lime-300 text-zinc-950"><x-icon name="check" /></span>
                <span class="font-mono text-sm font-semibold text-lime-700 dark:text-lime-200">04</span>
            </div>
            <h2 class="mt-3 font-semibold text-zinc-950 dark:text-white">Tap check in / กดเช็กอิน</h2>
            <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">If the ticket is approved, tap Check in. The screen flashes green when it succeeds. / ถ้าตั๋วอนุมัติแล้ว ให้กด Check in หน้าจอจะขึ้นสีเขียวเมื่อสำเร็จ</p>
        </div>
    </section>

    <section class="mt-6 grid gap-3 lg:grid-cols-3">
        <div class="rounded-lg border border-zinc-200 bg-zinc-50 p-4 dark:border-white/10 dark:bg-zinc-900">
            <div class="inline-flex items-center gap-2 font-semibold text-zinc-950 dark:text-white"><x-icon name="x" class="text-rose-600 dark:text-rose-300" />Invalid or wrong event / ตั๋วไม่ถูกต้องหรือผิดงาน</div>
            <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">Ask the guest to open the correct ticket or contact the event admin. / ขอให้ลูกค้าเปิดตั๋วที่ถูกต้อง หรือติดต่อแอดมินอีเวนต์</p>
        </div>
        <div class="rounded-lg border border-zinc-200 bg-zinc-50 p-4 dark:border-white/10 dark:bg-zinc-900">
            <div class="inline-flex items-center gap-2 font-semibold text-zinc-950 dark:text-white"><x-icon name="clock" class="text-amber-600 dark:text-amber-200" />Not approved yet / ยังไม่อนุมัติ</div>
            <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">Check the order payment status before allowing entry. / ตรวจสถานะการชำระเงินของออเดอร์ก่อนอนุญาตให้เข้างาน</p>
        </div>
        <div class="rounded-lg border border-zinc-200 bg-zinc-50 p-4 dark:border-white/10 dark:bg-zinc-900">
            <div class="inline-flex items-center gap-2 font-semibold text-zinc-950 dark:text-white"><x-icon name="undo" class="text-sky-600 dark:text-sky-300" />Need to reverse / ต้องแก้สถานะ</div>
            <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">Use Check out only when the event flow needs it, such as exit/re-entry control. / ใช้ Check out เฉพาะกรณีที่งานต้องควบคุมการออกและกลับเข้าใหม่</p>
        </div>
    </section>
</x-layouts.app>
