<x-layouts.app title="Login">
    <div class="mx-auto max-w-xl rounded-lg border border-zinc-200 dark:border-white/10 bg-white dark:bg-white/[0.04] p-6">
        <h1 class="inline-flex items-center gap-2 text-2xl font-semibold text-zinc-950 dark:text-white"><x-icon name="log-in" class="h-6 w-6 text-emerald-500" /><x-t en="Client login" th="เข้าสู่ระบบลูกค้า" /></h1>
        <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400"><x-t en="Use social login or phone quick login for customers." th="เข้าสู่ระบบด้วยโซเชียลหรือชื่อและเบอร์โทร" /></p>
        @if($socialProviders->isNotEmpty())
            <div class="mt-5 grid gap-3 sm:grid-cols-3" style="grid-template-columns: repeat({{ $socialProviders->count() }}, minmax(0, 1fr));">
                @foreach($socialProviders as $provider => $label)
                    <a class="inline-flex items-center justify-center gap-2 rounded-md px-3 py-2 text-center text-sm font-semibold {{ $provider === 'line' ? 'bg-[#06c755] text-zinc-950' : ($provider === 'facebook' ? 'bg-[#1877f2] text-white' : 'bg-pink-500 text-white') }}" href="{{ route('auth.social', $provider) }}"><x-icon name="log-in" />{{ $label }}</a>
                @endforeach
            </div>
        @endif
        <form method="POST" action="{{ route('login.store') }}" class="mt-6 grid gap-4">
            @csrf
            <input type="hidden" name="provider" value="guest">
            <label class="text-sm text-zinc-700 dark:text-zinc-300"><x-t en="Name" th="ชื่อ" /><input class="mt-1 w-full rounded-md border border-zinc-200 dark:border-white/10 bg-white dark:bg-zinc-950 px-3 py-2 text-zinc-950 dark:text-white" name="name" required></label>
            <label class="text-sm text-zinc-700 dark:text-zinc-300"><x-t en="Phone" th="เบอร์โทร" /><input class="mt-1 w-full rounded-md border border-zinc-200 dark:border-white/10 bg-white dark:bg-zinc-950 px-3 py-2 text-zinc-950 dark:text-white" name="phone" required></label>
            <label class="text-sm text-zinc-700 dark:text-zinc-300"><x-t en="Email" th="อีเมล" /> <span class="text-zinc-500"><x-t en="(optional)" th="(ไม่บังคับ)" /></span><input class="mt-1 w-full rounded-md border border-zinc-200 dark:border-white/10 bg-white dark:bg-zinc-950 px-3 py-2 text-zinc-950 dark:text-white" name="email"></label>
            <button class="inline-flex items-center justify-center gap-2 rounded-md bg-emerald-400 px-4 py-3 font-semibold text-zinc-950"><x-icon name="check" /><x-t en="Continue" th="ดำเนินการต่อ" /></button>
        </form>
    </div>
</x-layouts.app>
