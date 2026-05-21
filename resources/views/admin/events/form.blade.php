<x-layouts.app :title="$event->exists ? 'Edit event' : 'New event'">
    <form method="POST" enctype="multipart/form-data" action="{{ $event->exists ? route('admin.events.update', $event) : route('admin.events.store') }}" class="grid gap-6 lg:grid-cols-[1fr_.8fr]">
        @csrf
        @if($event->exists) @method('PUT') @endif
        <section class="rounded-lg border border-white/10 bg-white/[0.04] p-6">
            <h1 class="text-2xl font-semibold text-white">{{ $event->exists ? 'Edit event' : 'New event' }}</h1>
            <div class="mt-5 grid gap-4 sm:grid-cols-2">
                <label class="text-sm text-zinc-300 sm:col-span-2">Event name<input class="mt-1 w-full rounded-md border border-white/10 bg-zinc-950 px-3 py-2 text-white" name="name" value="{{ old('name', $event->name) }}" required></label>
                <label class="text-sm text-zinc-300 sm:col-span-2">Description<textarea class="mt-1 w-full rounded-md border border-white/10 bg-zinc-950 px-3 py-2 text-white" name="description" rows="4">{{ old('description', $event->description) }}</textarea></label>
                <label class="text-sm text-zinc-300">Venue<input class="mt-1 w-full rounded-md border border-white/10 bg-zinc-950 px-3 py-2 text-white" name="venue" value="{{ old('venue', $event->venue) }}" required></label>
                <label class="text-sm text-zinc-300">Location<input class="mt-1 w-full rounded-md border border-white/10 bg-zinc-950 px-3 py-2 text-white" name="location" value="{{ old('location', $event->location) }}"></label>
                <label class="text-sm text-zinc-300">Hosted by<input class="mt-1 w-full rounded-md border border-white/10 bg-zinc-950 px-3 py-2 text-white" name="hosted_by" value="{{ old('hosted_by', $event->hosted_by) }}"></label>
                <label class="text-sm text-zinc-300">Event poster 4:5<input class="mt-1 w-full rounded-md border border-white/10 bg-zinc-950 px-3 py-2 text-white" type="file" name="poster" accept="image/*"></label>
                <label class="text-sm text-zinc-300">Ticket image 4:5<input class="mt-1 w-full rounded-md border border-white/10 bg-zinc-950 px-3 py-2 text-white" type="file" name="ticket_image" accept="image/*"></label>
                <label class="text-sm text-zinc-300">Starts at<input class="mt-1 w-full rounded-md border border-white/10 bg-zinc-950 px-3 py-2 text-white" type="datetime-local" name="starts_at" value="{{ old('starts_at', $event->starts_at?->format('Y-m-d\TH:i')) }}" required></label>
                <label class="text-sm text-zinc-300">Ends at<input class="mt-1 w-full rounded-md border border-white/10 bg-zinc-950 px-3 py-2 text-white" type="datetime-local" name="ends_at" value="{{ old('ends_at', $event->ends_at?->format('Y-m-d\TH:i')) }}" required></label>
                <label class="flex items-center gap-2 text-sm text-zinc-300"><input class="rounded border-white/10 bg-zinc-950" type="checkbox" name="is_published" value="1" @checked(old('is_published', $event->is_published ?? true))> Published</label>
            </div>
            <div class="mt-6 border-t border-white/10 pt-6">
                <h2 class="text-xl font-semibold text-white">Payment accounts</h2>
                <div class="mt-4 grid gap-4 sm:grid-cols-2">
                    <label class="text-sm text-zinc-300">Bank name<input class="mt-1 w-full rounded-md border border-white/10 bg-zinc-950 px-3 py-2 text-white" name="bank_name" value="{{ old('bank_name', $event->bank_name) }}"></label>
                    <label class="text-sm text-zinc-300">Bank account name<input class="mt-1 w-full rounded-md border border-white/10 bg-zinc-950 px-3 py-2 text-white" name="bank_account_name" value="{{ old('bank_account_name', $event->bank_account_name) }}"></label>
                    <label class="text-sm text-zinc-300">Bank account number<input class="mt-1 w-full rounded-md border border-white/10 bg-zinc-950 px-3 py-2 text-white" name="bank_account_number" value="{{ old('bank_account_number', $event->bank_account_number) }}"></label>
                    <label class="text-sm text-zinc-300">QR payment account name<input class="mt-1 w-full rounded-md border border-white/10 bg-zinc-950 px-3 py-2 text-white" name="qr_payment_account_name" value="{{ old('qr_payment_account_name', $event->qr_payment_account_name) }}"></label>
                    <label class="text-sm text-zinc-300">QR payment account / PromptPay<input class="mt-1 w-full rounded-md border border-white/10 bg-zinc-950 px-3 py-2 text-white" name="qr_payment_account" value="{{ old('qr_payment_account', $event->qr_payment_account) }}"></label>
                    <label class="text-sm text-zinc-300">QR payment image<input class="mt-1 w-full rounded-md border border-white/10 bg-zinc-950 px-3 py-2 text-white" type="file" name="qr_payment_image" accept="image/*"></label>
                    <label class="text-sm text-zinc-300 sm:col-span-2">Payment instructions<textarea class="mt-1 w-full rounded-md border border-white/10 bg-zinc-950 px-3 py-2 text-white" name="payment_instructions" rows="3">{{ old('payment_instructions', $event->payment_instructions) }}</textarea></label>
                </div>
            </div>
        </section>
        <section class="rounded-lg border border-white/10 bg-white/[0.04] p-6">
            <h2 class="text-xl font-semibold text-white">Ticket types</h2>
            @php($rows = $ticketTypes->count() ? $ticketTypes : collect([null, null]))
            <div class="mt-4 grid gap-4">
                @foreach($rows as $i => $ticket)
                    <div class="rounded-md border border-white/10 bg-zinc-900 p-4">
                        <input type="hidden" name="tickets[{{ $i }}][id]" value="{{ $ticket?->id }}">
                        <label class="text-sm text-zinc-300">Type<input class="mt-1 w-full rounded-md border border-white/10 bg-zinc-950 px-3 py-2 text-white" name="tickets[{{ $i }}][name]" value="{{ $ticket?->name }}"></label>
                        <div class="mt-3 grid gap-3 sm:grid-cols-2">
                            <label class="text-sm text-zinc-300">Price THB<input class="mt-1 w-full rounded-md border border-white/10 bg-zinc-950 px-3 py-2 text-white" type="number" name="tickets[{{ $i }}][price_thb]" value="{{ $ticket?->price_thb ?? 0 }}"></label>
                            <label class="text-sm text-zinc-300">Capacity<input class="mt-1 w-full rounded-md border border-white/10 bg-zinc-950 px-3 py-2 text-white" type="number" name="tickets[{{ $i }}][capacity]" value="{{ $ticket?->capacity ?? 0 }}"></label>
                            <label class="text-sm text-zinc-300">Sale starts<input class="mt-1 w-full rounded-md border border-white/10 bg-zinc-950 px-3 py-2 text-white" type="datetime-local" name="tickets[{{ $i }}][sale_starts_at]" value="{{ $ticket?->sale_starts_at?->format('Y-m-d\TH:i') }}"></label>
                            <label class="text-sm text-zinc-300">Sale ends<input class="mt-1 w-full rounded-md border border-white/10 bg-zinc-950 px-3 py-2 text-white" type="datetime-local" name="tickets[{{ $i }}][sale_ends_at]" value="{{ $ticket?->sale_ends_at?->format('Y-m-d\TH:i') }}"></label>
                        </div>
                        <label class="mt-3 block text-sm text-zinc-300">Description<textarea class="mt-1 w-full rounded-md border border-white/10 bg-zinc-950 px-3 py-2 text-white" name="tickets[{{ $i }}][description]" rows="2">{{ $ticket?->description }}</textarea></label>
                    </div>
                @endforeach
            </div>
            <button class="mt-5 w-full rounded-md bg-emerald-400 px-4 py-3 font-semibold text-zinc-950">Save event</button>
        </section>
    </form>
</x-layouts.app>
