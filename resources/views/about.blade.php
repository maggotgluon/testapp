<x-layouts.app
    title="About TicketFlow / เกี่ยวกับ TicketFlow"
    meta-description="TicketFlow is a thoughtful ticketing and check-in platform for community events, fitness classes, workshops, and small organizers."
    :canonical-url="route('about')"
>
    <section class="mx-auto max-w-4xl">
        <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-white/10 dark:bg-white/[0.04] sm:p-8">
            <p class="text-sm font-semibold uppercase text-emerald-600 dark:text-emerald-300">About us / เกี่ยวกับเรา</p>
            <h1 class="mt-3 text-4xl font-semibold tracking-tight text-zinc-950 dark:text-white">Built for event teams who care about every attendee / สร้างมาเพื่อทีมจัดงานที่ใส่ใจผู้เข้าร่วมทุกคน</h1>
            <p class="mt-4 text-lg text-zinc-700 dark:text-zinc-300">
                TicketFlow helps organizers sell tickets, approve payments, manage coupons and promotions, scan check-ins, and keep attendee records in one calm, practical workspace. / TicketFlow ช่วยผู้จัดงานขายตั๋ว อนุมัติการชำระเงิน จัดการคูปองและโปรโมชัน สแกนเข้างาน และดูข้อมูลผู้เข้าร่วมในพื้นที่ทำงานเดียวที่ใช้งานง่าย
            </p>
        </div>

        <div class="mt-6 grid gap-4 sm:grid-cols-2">
            <div class="rounded-lg border border-zinc-200 bg-zinc-50 p-5 dark:border-white/10 dark:bg-white/[0.03]">
                <h2 class="text-xl font-semibold text-zinc-950 dark:text-white">Why this app exists / แนวคิดของแอปนี้</h2>
                <p class="mt-3 text-zinc-700 dark:text-zinc-300">
                    Many community events run on trust, chat messages, bank transfers, and small teams doing a lot of careful manual work. This application was designed to make that flow clearer without making it feel cold or complicated. / งานอีเวนต์ชุมชนจำนวนมากเติบโตจากความไว้ใจ การคุยผ่านแชต การโอนเงิน และทีมเล็กๆ ที่ต้องดูแลหลายอย่าง แอปนี้จึงถูกออกแบบให้ขั้นตอนชัดเจนขึ้น โดยยังคงความเป็นกันเองและไม่ซับซ้อน
                </p>
            </div>

            <div class="rounded-lg border border-zinc-200 bg-zinc-50 p-5 dark:border-white/10 dark:bg-white/[0.03]">
                <h2 class="text-xl font-semibold text-zinc-950 dark:text-white">Made for real event days / พร้อมสำหรับวันงานจริง</h2>
                <p class="mt-3 text-zinc-700 dark:text-zinc-300">
                    The check-in flow, ticket status, payment slips, QR tickets, and attendee views are built for the pressure of a real event day, when staff need fast answers and guests need reassurance. / ระบบเช็กอิน สถานะตั๋ว สลิปชำระเงิน QR ตั๋ว และหน้าข้อมูลผู้เข้าร่วม ถูกออกแบบมาเพื่อวันงานจริงที่ทีมงานต้องการคำตอบรวดเร็ว และผู้ร่วมงานต้องการความมั่นใจ
                </p>
            </div>
        </div>

        <div class="mt-6 rounded-lg border border-emerald-400/30 bg-emerald-400/10 p-6">
            <h2 class="text-2xl font-semibold text-zinc-950 dark:text-white">Developed by Magnamm Studio / พัฒนาโดย Magnamm Studio</h2>
            <p class="mt-3 text-zinc-700 dark:text-zinc-300">
                This application was crafted by Magnamm Studio with a focus on useful software for small teams, creative communities, and organizers who want technology to feel supportive rather than heavy. / แอปนี้พัฒนาโดย Magnamm Studio โดยตั้งใจสร้างซอฟต์แวร์ที่เป็นประโยชน์กับทีมเล็กๆ คอมมูนิตี้สร้างสรรค์ และผู้จัดงานที่อยากให้เทคโนโลยีช่วยงานมากกว่าสร้างภาระ
            </p>
            <a class="mt-5 inline-flex items-center gap-2 rounded-md bg-emerald-400 px-4 py-2 font-semibold text-zinc-950 hover:bg-emerald-300" href="https://www.mag.codes" target="_blank" rel="noopener">
                <x-icon name="external-link" />
                Visit www.mag.codes / ไปที่ www.mag.codes
            </a>
        </div>
    </section>
</x-layouts.app>
