<x-layouts.app title="Users">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <h1 class="text-3xl font-semibold text-zinc-950 dark:text-white">Users / ผู้ใช้</h1>
        <form class="flex gap-2">
            <select class="rounded-md border border-zinc-200 dark:border-white/10 bg-white dark:bg-zinc-950 px-3 py-2 text-sm text-zinc-950 dark:text-white" name="role">
                <option value="">All roles / ทุกบทบาท</option>
                @foreach(['customer' => 'Customer / ลูกค้า', 'super_admin' => 'Super admin / ผู้ดูแลสูงสุด', 'event_admin' => 'Event admin / ผู้ดูแลอีเวนต์', 'gate_scanner' => 'Gate scanner / เจ้าหน้าที่สแกน'] as $value => $label)
                    <option value="{{ $value }}" @selected(request('role') === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <button class="rounded-md border border-zinc-200 dark:border-white/10 px-3 py-2 text-sm text-zinc-800 dark:text-zinc-100">Filter / กรอง</button>
        </form>
    </div>
    <div class="mt-6 rounded-lg border border-zinc-200 dark:border-white/10 bg-white dark:bg-white/[0.04]">
        <div class="divide-y divide-white/10">
            @foreach($users as $user)
                <a class="grid gap-2 p-4 hover:bg-zinc-50 dark:bg-white/[0.03] sm:grid-cols-[1fr_auto]" href="{{ route('admin.users.edit', $user) }}">
                    <div>
                        <div class="font-semibold text-zinc-950 dark:text-white">{{ $user->name }}</div>
                        <div class="text-sm text-zinc-600 dark:text-zinc-400">{{ $user->username ?: 'No username / ไม่มีชื่อผู้ใช้' }} · {{ $user->phone ?: 'No phone / ไม่มีเบอร์' }} · {{ $user->email ?: 'No email / ไม่มีอีเมล' }}</div>
                    </div>
                    <span class="self-start rounded bg-zinc-100 dark:bg-white/10 px-3 py-1 text-sm text-emerald-700 dark:text-emerald-200">{{ str_replace('_', ' ', $user->role) }}</span>
                </a>
            @endforeach
        </div>
        <div class="p-4">{{ $users->links() }}</div>
    </div>
</x-layouts.app>
