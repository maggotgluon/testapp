<x-layouts.app :title="'Edit '.$user->name">
    <form method="POST" action="{{ route('admin.users.update', $user) }}" class="mx-auto max-w-2xl rounded-lg border border-zinc-200 dark:border-white/10 bg-white dark:bg-white/[0.04] p-6">
        @csrf
        @method('PUT')
        <h1 class="text-2xl font-semibold text-zinc-950 dark:text-white">Edit user / แก้ไขผู้ใช้</h1>
        <div class="mt-5 grid gap-4 sm:grid-cols-2">
            <label class="text-sm text-zinc-700 dark:text-zinc-300">Name / ชื่อ<input class="mt-1 w-full rounded-md border border-zinc-200 dark:border-white/10 bg-white dark:bg-zinc-950 px-3 py-2 text-zinc-950 dark:text-white" name="name" value="{{ old('name', $user->name) }}" required></label>
            <label class="text-sm text-zinc-700 dark:text-zinc-300">Username / ชื่อผู้ใช้<input class="mt-1 w-full rounded-md border border-zinc-200 dark:border-white/10 bg-white dark:bg-zinc-950 px-3 py-2 text-zinc-950 dark:text-white" name="username" value="{{ old('username', $user->username) }}"></label>
            <label class="text-sm text-zinc-700 dark:text-zinc-300">Phone / เบอร์โทร<input class="mt-1 w-full rounded-md border border-zinc-200 dark:border-white/10 bg-white dark:bg-zinc-950 px-3 py-2 text-zinc-950 dark:text-white" name="phone" value="{{ old('phone', $user->phone) }}"></label>
            <label class="text-sm text-zinc-700 dark:text-zinc-300">Email / อีเมล<input class="mt-1 w-full rounded-md border border-zinc-200 dark:border-white/10 bg-white dark:bg-zinc-950 px-3 py-2 text-zinc-950 dark:text-white" name="email" value="{{ old('email', $user->email) }}"></label>
            <label class="text-sm text-zinc-700 dark:text-zinc-300 sm:col-span-2">Role / บทบาท<select class="mt-1 w-full rounded-md border border-zinc-200 dark:border-white/10 bg-white dark:bg-zinc-950 px-3 py-2 text-zinc-950 dark:text-white" name="role">
                @foreach(['customer' => 'Customer / ลูกค้า', 'super_admin' => 'Super admin / ผู้ดูแลสูงสุด', 'event_admin' => 'Event admin / ผู้ดูแลอีเวนต์', 'gate_scanner' => 'Gate scanner / เจ้าหน้าที่สแกน'] as $value => $label)
                    <option value="{{ $value }}" @selected(old('role', $user->role) === $value)>{{ $label }}</option>
                @endforeach
            </select></label>
        </div>
        <button class="mt-6 rounded-md bg-emerald-400 px-4 py-3 font-semibold text-zinc-950">Save user / บันทึกผู้ใช้</button>
    </form>
</x-layouts.app>
