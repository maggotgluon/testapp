<x-layouts.app title="Admin login">
    <div class="mx-auto max-w-xl rounded-lg border border-zinc-200 dark:border-white/10 bg-white dark:bg-white/[0.04] p-6">
        <h1 class="inline-flex items-center gap-2 text-2xl font-semibold text-zinc-950 dark:text-white"><x-icon name="shield" class="h-6 w-6 text-emerald-500" />Admin login / เข้าสู่ระบบผู้ดูแล</h1>
        <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">Use your admin username and phone number. / ใช้ชื่อผู้ใช้และเบอร์โทรของผู้ดูแล</p>

        @if($localRoles)
            <form method="POST" action="{{ route('admin.login.store') }}" class="mt-6 rounded-md border border-emerald-400/20 bg-emerald-400/10 p-4">
                @csrf
                <label class="text-sm font-medium text-emerald-800 dark:text-emerald-100">Local test role / บทบาททดสอบ
                    <select class="mt-1 w-full rounded-md border border-emerald-300/20 bg-white dark:bg-zinc-950 px-3 py-2 text-zinc-950 dark:text-white" name="role" required>
                        @foreach($localRoles as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </label>
                <button class="mt-3 inline-flex w-full items-center justify-center gap-2 rounded-md bg-emerald-400 px-4 py-3 font-semibold text-zinc-950"><x-icon name="shield" />Login as role / เข้าด้วยบทบาทนี้</button>
            </form>
        @endif

        <form method="POST" action="{{ route('admin.login.store') }}" class="mt-6 grid gap-4">
            @csrf
            <label class="text-sm text-zinc-700 dark:text-zinc-300">Username / ชื่อผู้ใช้<input class="mt-1 w-full rounded-md border border-zinc-200 dark:border-white/10 bg-white dark:bg-zinc-950 px-3 py-2 text-zinc-950 dark:text-white" name="username" placeholder="admin" required></label>
            <label class="text-sm text-zinc-700 dark:text-zinc-300">Phone / เบอร์โทร<input class="mt-1 w-full rounded-md border border-zinc-200 dark:border-white/10 bg-white dark:bg-zinc-950 px-3 py-2 text-zinc-950 dark:text-white" name="phone" placeholder="0900000000" required></label>
            <button class="inline-flex items-center justify-center gap-2 rounded-md bg-emerald-400 px-4 py-3 font-semibold text-zinc-950"><x-icon name="log-in" />Login to admin / เข้าสู่ระบบผู้ดูแล</button>
        </form>
    </div>
</x-layouts.app>
