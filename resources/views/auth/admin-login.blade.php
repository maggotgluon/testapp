<x-layouts.app title="Admin login">
    <div class="mx-auto max-w-xl rounded-lg border border-white/10 bg-white/[0.04] p-6">
        <h1 class="text-2xl font-semibold text-white">Admin login</h1>
        <p class="mt-2 text-sm text-zinc-400">Use your admin username and phone number. Local development can use a role shortcut for faster testing.</p>

        @if($localRoles)
            <form method="POST" action="{{ route('admin.login.store') }}" class="mt-6 rounded-md border border-emerald-400/20 bg-emerald-400/10 p-4">
                @csrf
                <label class="text-sm font-medium text-emerald-100">Local test role
                    <select class="mt-1 w-full rounded-md border border-emerald-300/20 bg-zinc-950 px-3 py-2 text-white" name="role" required>
                        @foreach($localRoles as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </label>
                <button class="mt-3 w-full rounded-md bg-emerald-400 px-4 py-3 font-semibold text-zinc-950">Login as role</button>
            </form>
        @endif

        <form method="POST" action="{{ route('admin.login.store') }}" class="mt-6 grid gap-4">
            @csrf
            <label class="text-sm text-zinc-300">Username<input class="mt-1 w-full rounded-md border border-white/10 bg-zinc-950 px-3 py-2 text-white" name="username" placeholder="admin" required></label>
            <label class="text-sm text-zinc-300">Phone<input class="mt-1 w-full rounded-md border border-white/10 bg-zinc-950 px-3 py-2 text-white" name="phone" placeholder="0900000000" required></label>
            <button class="rounded-md bg-emerald-400 px-4 py-3 font-semibold text-zinc-950">Login to admin</button>
        </form>
    </div>
</x-layouts.app>
