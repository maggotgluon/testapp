<x-layouts.app :title="$coupon->exists ? 'Edit coupon' : 'New coupon'">
    <form method="POST" action="{{ $coupon->exists ? route('admin.coupons.update', $coupon) : route('admin.coupons.store') }}" class="mx-auto max-w-3xl rounded-lg border border-white/10 bg-white/[0.04] p-6">
        @csrf
        @if($coupon->exists) @method('PUT') @endif
        <h1 class="text-2xl font-semibold text-white">{{ $coupon->exists ? 'Edit coupon' : 'New coupon' }}</h1>
        <div class="mt-5 grid gap-4 sm:grid-cols-2">
            <label class="text-sm text-zinc-300">Code<input class="mt-1 w-full rounded-md border border-white/10 bg-zinc-950 px-3 py-2 uppercase text-white" name="code" value="{{ old('code', $coupon->code) }}" required></label>
            <label class="text-sm text-zinc-300">Name<input class="mt-1 w-full rounded-md border border-white/10 bg-zinc-950 px-3 py-2 text-white" name="name" value="{{ old('name', $coupon->name) }}"></label>
            <label class="text-sm text-zinc-300 sm:col-span-2">Event<select class="mt-1 w-full rounded-md border border-white/10 bg-zinc-950 px-3 py-2 text-white" name="event_id">
                <option value="">All events</option>
                @foreach($events as $event)
                    <option value="{{ $event->id }}" @selected((string) old('event_id', $coupon->event_id) === (string) $event->id)>{{ $event->name }}</option>
                @endforeach
            </select></label>
            <label class="text-sm text-zinc-300">Discount type<select class="mt-1 w-full rounded-md border border-white/10 bg-zinc-950 px-3 py-2 text-white" name="discount_type">
                <option value="fixed" @selected(old('discount_type', $coupon->discount_type ?: 'fixed') === 'fixed')>Fixed THB</option>
                <option value="percent" @selected(old('discount_type', $coupon->discount_type) === 'percent')>Percent</option>
            </select></label>
            <label class="text-sm text-zinc-300">Discount value<input class="mt-1 w-full rounded-md border border-white/10 bg-zinc-950 px-3 py-2 text-white" type="number" name="discount_value" value="{{ old('discount_value', $coupon->discount_value) }}" required></label>
            <label class="text-sm text-zinc-300">Usage limit<input class="mt-1 w-full rounded-md border border-white/10 bg-zinc-950 px-3 py-2 text-white" type="number" name="usage_limit" value="{{ old('usage_limit', $coupon->usage_limit) }}"></label>
            <label class="text-sm text-zinc-300">Starts at<input class="mt-1 w-full rounded-md border border-white/10 bg-zinc-950 px-3 py-2 text-white" type="datetime-local" name="starts_at" value="{{ old('starts_at', $coupon->starts_at?->format('Y-m-d\TH:i')) }}"></label>
            <label class="text-sm text-zinc-300">Expires at<input class="mt-1 w-full rounded-md border border-white/10 bg-zinc-950 px-3 py-2 text-white" type="datetime-local" name="expires_at" value="{{ old('expires_at', $coupon->expires_at?->format('Y-m-d\TH:i')) }}"></label>
            <label class="flex items-center gap-2 text-sm text-zinc-300"><input class="rounded border-white/10 bg-zinc-950" type="checkbox" name="is_active" value="1" @checked(old('is_active', $coupon->is_active ?? true))> Active</label>
        </div>
        <button class="mt-6 rounded-md bg-emerald-400 px-4 py-3 font-semibold text-zinc-950">Save coupon</button>
    </form>
</x-layouts.app>
