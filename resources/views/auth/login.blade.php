<x-layouts.app title="Login">
    <div class="mx-auto max-w-xl rounded-lg border border-white/10 bg-white/[0.04] p-6">
        <h1 class="text-2xl font-semibold text-white">Client login</h1>
        <p class="mt-2 text-sm text-zinc-400">Use social login or phone quick login for customers. Admin users have a separate admin login page.</p>
        @if($socialProviders->isNotEmpty())
            <div class="mt-5 grid gap-3 sm:grid-cols-3" style="grid-template-columns: repeat({{ $socialProviders->count() }}, minmax(0, 1fr));">
                @foreach($socialProviders as $provider => $label)
                    <a class="rounded-md px-3 py-2 text-center text-sm font-semibold {{ $provider === 'line' ? 'bg-[#06c755] text-zinc-950' : ($provider === 'facebook' ? 'bg-[#1877f2] text-white' : 'bg-pink-500 text-white') }}" href="{{ route('auth.social', $provider) }}">{{ $label }}</a>
                @endforeach
            </div>
        @endif
        <form method="POST" action="{{ route('login.store') }}" class="mt-6 grid gap-4">
            @csrf
            <input type="hidden" name="provider" value="guest">
            <label class="text-sm text-zinc-300">Name<input class="mt-1 w-full rounded-md border border-white/10 bg-zinc-950 px-3 py-2 text-white" name="name" required></label>
            <label class="text-sm text-zinc-300">Phone<input class="mt-1 w-full rounded-md border border-white/10 bg-zinc-950 px-3 py-2 text-white" name="phone" required></label>
            <label class="text-sm text-zinc-300">Email <span class="text-zinc-500">(optional)</span><input class="mt-1 w-full rounded-md border border-white/10 bg-zinc-950 px-3 py-2 text-white" name="email"></label>
            <button class="rounded-md bg-emerald-400 px-4 py-3 font-semibold text-zinc-950">Continue</button>
        </form>
    </div>
</x-layouts.app>
