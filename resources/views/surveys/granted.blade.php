<x-layouts.app :title="'Free Ticket Granted'">
    <div class="mx-auto mt-16 max-w-lg px-4 text-center">
        <div class="rounded-lg border border-emerald-400/30 bg-emerald-400/10 p-8">
            <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-emerald-400/20">
                <x-icon name="check-circle" class="h-8 w-8 text-emerald-600 dark:text-emerald-300" />
            </div>
            <h1 class="mt-4 text-2xl font-bold text-zinc-950 dark:text-white"><x-t en="Free Ticket Granted!" th="รับตั๋วฟรีเรียบร้อย!" /></h1>
            <p class="mt-2 text-zinc-700 dark:text-zinc-300">{{ $message }}</p>
            <div class="mt-6 text-sm text-zinc-500">
                <x-t en="Redirecting in" th="กำลังเปลี่ยนเส้นทางใน" />
                <span x-data="{ countdown: {{ $delay }} }" x-init="setInterval(() => { if (countdown > 0) countdown--; }, 1000)">
                    <span x-text="countdown" class="font-semibold text-zinc-950 dark:text-white"></span>
                </span>
                <x-t en="seconds..." th="วินาที..." />
            </div>
            <div class="mt-4 h-2 overflow-hidden rounded-full bg-zinc-200 dark:bg-zinc-800">
                <div x-data="{ width: 100 }" x-init="const el = $el; const start = Date.now(); const duration = {{ $delay }} * 1000; function frame() { const elapsed = Date.now() - start; const pct = Math.max(0, 100 - (elapsed / duration) * 100); el.style.width = pct + '%'; if (pct > 0) requestAnimationFrame(frame); } requestAnimationFrame(frame);" class="h-full rounded-full bg-emerald-400 transition-all"></div>
            </div>
            <a class="mt-6 inline-flex items-center gap-2 rounded-md bg-emerald-400 px-6 py-3 font-semibold text-zinc-950 hover:bg-emerald-300" href="{{ $redirectUrl }}">
                <x-icon name="ticket" /><x-t en="Go to my ticket" th="ไปที่ตั๋วของฉัน" />
            </a>
        </div>
    </div>

    <script>
        setTimeout(function () {
            window.location.href = {{ Js::from($redirectUrl) }};
        }, {{ $delay }} * 1000);
    </script>
</x-layouts.app>
