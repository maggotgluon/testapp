<x-layouts.app title="Login">
    <div class="mx-auto max-w-xl rounded-lg border border-white/10 bg-white/[0.04] p-6">
        <h1 class="text-2xl font-semibold text-white">Client login</h1>
        <p class="mt-2 text-sm text-zinc-400">Use social login or phone quick login for customers. Admin users have a separate admin login page.</p>
        <div class="mt-5 grid gap-3 sm:grid-cols-3">
            <a class="rounded-md bg-[#06c755] px-3 py-2 text-center text-sm font-semibold text-zinc-950" href="{{ route('auth.social', 'line') }}">LINE</a>
            <a class="rounded-md bg-[#1877f2] px-3 py-2 text-center text-sm font-semibold text-white" href="{{ route('auth.social', 'facebook') }}">Facebook</a>
            <a class="rounded-md bg-pink-500 px-3 py-2 text-center text-sm font-semibold text-white" href="{{ route('auth.social', 'instagram') }}">Instagram</a>
        </div>
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
