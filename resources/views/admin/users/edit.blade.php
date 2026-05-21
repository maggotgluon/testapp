<x-layouts.app :title="'Edit '.$user->name">
    <form method="POST" action="{{ route('admin.users.update', $user) }}" class="mx-auto max-w-2xl rounded-lg border border-white/10 bg-white/[0.04] p-6">
        @csrf
        @method('PUT')
        <h1 class="text-2xl font-semibold text-white">Edit user</h1>
        <div class="mt-5 grid gap-4 sm:grid-cols-2">
            <label class="text-sm text-zinc-300">Name<input class="mt-1 w-full rounded-md border border-white/10 bg-zinc-950 px-3 py-2 text-white" name="name" value="{{ old('name', $user->name) }}" required></label>
            <label class="text-sm text-zinc-300">Username<input class="mt-1 w-full rounded-md border border-white/10 bg-zinc-950 px-3 py-2 text-white" name="username" value="{{ old('username', $user->username) }}"></label>
            <label class="text-sm text-zinc-300">Phone<input class="mt-1 w-full rounded-md border border-white/10 bg-zinc-950 px-3 py-2 text-white" name="phone" value="{{ old('phone', $user->phone) }}"></label>
            <label class="text-sm text-zinc-300">Email<input class="mt-1 w-full rounded-md border border-white/10 bg-zinc-950 px-3 py-2 text-white" name="email" value="{{ old('email', $user->email) }}"></label>
            <label class="text-sm text-zinc-300 sm:col-span-2">Role<select class="mt-1 w-full rounded-md border border-white/10 bg-zinc-950 px-3 py-2 text-white" name="role">
                @foreach(['customer' => 'Customer', 'super_admin' => 'Super admin', 'event_admin' => 'Event admin', 'gate_scanner' => 'Gate scanner'] as $value => $label)
                    <option value="{{ $value }}" @selected(old('role', $user->role) === $value)>{{ $label }}</option>
                @endforeach
            </select></label>
        </div>
        <button class="mt-6 rounded-md bg-emerald-400 px-4 py-3 font-semibold text-zinc-950">Save user</button>
    </form>
</x-layouts.app>
