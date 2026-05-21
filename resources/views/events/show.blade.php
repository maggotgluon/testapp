<x-layouts.app :title="$event->name">
    @php
        $checkoutTickets = $event->ticketTypes->map(fn ($ticket) => [
            'id' => $ticket->id,
            'name' => $ticket->name,
            'price' => $ticket->price_thb,
        ])->values();
        $checkoutCoupons = $event->coupons->map(fn ($coupon) => [
            'code' => $coupon->code,
            'type' => $coupon->discount_type,
            'scope' => $coupon->discount_scope,
            'value' => $coupon->discount_value,
            'ticket_type_id' => $coupon->ticket_type_id,
        ])->values();
    @endphp
    <div class="grid gap-8 lg:grid-cols-[.8fr_1.2fr]">
        <section>
            <div class="overflow-hidden rounded-lg border border-white/10 bg-white/[0.04]">
                <div class="aspect-[4/5] bg-gradient-to-br from-emerald-500 via-sky-500 to-zinc-800">
                    @if($event->poster_path)
                        <img class="h-full w-full object-cover" src="{{ asset('uploads/'.$event->poster_path) }}" alt="{{ $event->name }}">
                    @endif
                </div>
                <div class="p-5">
                    <p class="text-sm text-emerald-300">{{ $event->starts_at->format('D, M j, Y H:i') }} - {{ $event->ends_at->format('H:i') }}</p>
                    <h1 class="mt-2 text-3xl font-semibold text-white">{{ $event->name }}</h1>
                    <p class="mt-3 text-zinc-300">{{ $event->description }}</p>
                    <dl class="mt-5 grid gap-3 text-sm text-zinc-300">
                        <div><dt class="text-zinc-500">Venue</dt><dd>{{ $event->venue }}</dd></div>
                        <div><dt class="text-zinc-500">Location</dt><dd>{{ $event->location }}</dd></div>
                        <div><dt class="text-zinc-500">Hosted by</dt><dd>{{ $event->hosted_by }}</dd></div>
                    </dl>
                </div>
            </div>
        </section>

        <form method="POST" action="{{ route('orders.store') }}" enctype="multipart/form-data" class="rounded-lg border border-white/10 bg-white/[0.04] p-5" x-data="checkout({
            eventId: {{ $event->id }},
            tickets: @js($checkoutTickets),
            coupons: @js($checkoutCoupons),
            payment: @js([
                'bank_name' => $event->bank_name,
                'bank_account_name' => $event->bank_account_name,
                'bank_account_number' => $event->bank_account_number,
                'qr_payment_account_name' => $event->qr_payment_account_name,
                'qr_payment_account' => $event->qr_payment_account,
                'qr_payment_image' => $event->qr_payment_image_path ? asset('uploads/'.$event->qr_payment_image_path) : null,
                'instructions' => $event->payment_instructions,
            ]),
        })">
            @csrf
            <h2 class="text-xl font-semibold text-white">Choose tickets</h2>
            <div class="mt-4 grid gap-3">
                @forelse($event->ticketTypes as $ticket)
                    <div class="rounded-md border border-white/10 bg-zinc-900 p-4">
                        <div class="flex items-center justify-between gap-4">
                            <div>
                                <h3 class="font-semibold text-white">{{ $ticket->name }}</h3>
                                <p class="text-sm text-zinc-400">{{ $ticket->description }}</p>
                                <p class="mt-1 text-sm text-emerald-300">THB {{ number_format($ticket->price_thb) }}</p>
                            </div>
                            <input type="hidden" name="items[{{ $loop->index }}][ticket_type_id]" value="{{ $ticket->id }}">
                            <div class="grid grid-cols-[40px_64px_40px] overflow-hidden rounded-md border border-white/10">
                                <button class="bg-zinc-950 px-3 py-2 text-lg text-zinc-100 hover:bg-white/10" type="button" @click="decrement({{ $ticket->id }})">-</button>
                                <input class="w-16 border-x border-white/10 bg-zinc-950 px-2 py-2 text-center text-white" name="items[{{ $loop->index }}][quantity]" type="number" min="0" max="20" value="0" x-model.number="quantities[{{ $ticket->id }}]">
                                <button class="bg-zinc-950 px-3 py-2 text-lg text-zinc-100 hover:bg-white/10" type="button" @click="increment({{ $ticket->id }})">+</button>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="rounded-md border border-amber-400/30 bg-amber-400/10 p-4 text-sm text-amber-100">No ticket types are on sale right now.</div>
                @endforelse
            </div>

            <div class="mt-5 grid gap-4 sm:grid-cols-2">
                <label class="text-sm font-medium text-zinc-200">Name <span class="rounded bg-rose-400/20 px-1.5 py-0.5 text-xs text-rose-200">required</span><input class="mt-1 w-full rounded-md border border-emerald-400/40 bg-zinc-950 px-3 py-2 text-white focus:border-emerald-300 focus:outline-none" name="customer_name" value="{{ auth()->user()->name ?? old('customer_name') }}" required></label>
                <label class="text-sm font-medium text-zinc-200">Phone <span class="rounded bg-rose-400/20 px-1.5 py-0.5 text-xs text-rose-200">required</span><input class="mt-1 w-full rounded-md border border-emerald-400/40 bg-zinc-950 px-3 py-2 text-white focus:border-emerald-300 focus:outline-none" name="customer_phone" value="{{ auth()->user()->phone ?? old('customer_phone') }}" required></label>
                <label class="text-sm text-zinc-300">Email<input class="mt-1 w-full rounded-md border border-white/10 bg-zinc-950 px-3 py-2 text-white" name="customer_email" value="{{ auth()->user()->email ?? old('customer_email') }}"></label>
                @if($event->coupons->isNotEmpty())
                    <label class="text-sm text-zinc-300">Coupon<input class="mt-1 w-full rounded-md border border-white/10 bg-zinc-950 px-3 py-2 uppercase text-white" name="coupon_code" placeholder="EARLYBIRD" x-model="couponCode"></label>
                @endif
            </div>

            @if($event->coupons->isNotEmpty())
                <div class="mt-4 rounded-md border border-white/10 bg-zinc-900 p-4 text-sm text-zinc-300">
                    <div class="font-medium text-white">Available coupons</div>
                    <div class="mt-2 flex flex-wrap gap-2">
                        @foreach($event->coupons as $coupon)
                            <span class="rounded bg-white/10 px-2 py-1 font-mono text-xs text-emerald-200">{{ $coupon->code }}</span>
                        @endforeach
                    </div>
                </div>
            @endif

            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                <label class="text-sm font-medium text-zinc-200">Payment method <span class="rounded bg-rose-400/20 px-1.5 py-0.5 text-xs text-rose-200">required</span><select class="mt-1 w-full rounded-md border border-emerald-400/40 bg-zinc-950 px-3 py-2 text-white" name="payment_method" x-model="paymentMethod"><option value="bank_transfer">Direct bank transfer</option><option value="qr_payment">QR payment</option></select></label>
                <div class="text-sm text-zinc-300">
                    Payment slip
                    <label class="mt-1 flex cursor-pointer items-center justify-center rounded-md border border-dashed border-emerald-400/50 bg-zinc-950 px-3 py-2 font-semibold text-emerald-200 hover:bg-emerald-400/10">
                        <input class="sr-only" name="slip" type="file" accept="image/*" @change="slipName = $event.target.files[0]?.name || ''">
                        Attach payment slip
                    </label>
                    <p class="mt-1 truncate text-xs text-zinc-500" x-text="slipName || 'No file attached yet'"></p>
                </div>
            </div>
            <div class="mt-5 rounded-md border border-emerald-400/20 bg-emerald-400/10 p-4 text-sm text-emerald-50">
                <div class="flex items-center justify-between gap-3">
                    <strong x-text="paymentMethod === 'qr_payment' ? 'QR payment' : 'Bank transfer'"></strong>
                    <span class="rounded bg-emerald-300 px-2 py-1 font-semibold text-zinc-950">THB <span x-text="total().toLocaleString()"></span></span>
                </div>
                <dl class="mt-3 grid gap-1 text-sm">
                    <div class="flex justify-between gap-3"><dt class="text-emerald-200">Subtotal</dt><dd>THB <span x-text="subtotal().toLocaleString()"></span></dd></div>
                    <div class="flex justify-between gap-3"><dt class="text-emerald-200">Coupon discount</dt><dd>- THB <span x-text="discount().toLocaleString()"></span></dd></div>
                </dl>
                <template x-if="paymentMethod === 'bank_transfer'">
                    <dl class="mt-3 grid gap-2">
                        <div><dt class="text-emerald-200">Bank</dt><dd x-text="payment.bank_name || 'Set bank name in admin event settings'"></dd></div>
                        <div><dt class="text-emerald-200">Account name</dt><dd x-text="payment.bank_account_name || '-'"></dd></div>
                        <div><dt class="text-emerald-200">Account number</dt><dd class="font-mono" x-text="payment.bank_account_number || '-'"></dd></div>
                    </dl>
                </template>
                <template x-if="paymentMethod === 'qr_payment'">
                    <div class="mt-3 grid gap-4 sm:grid-cols-[160px_1fr]">
                        <div class="grid place-items-center rounded-md bg-white p-3">
                            <img class="h-32 w-32 object-contain" :src="paymentQrUrl()" alt="QR payment code">
                        </div>
                        <dl class="grid gap-2">
                            <div><dt class="text-emerald-200">QR account</dt><dd x-text="payment.qr_payment_account_name || '-'"></dd></div>
                            <div><dt class="text-emerald-200">PromptPay / account</dt><dd class="font-mono" x-text="payment.qr_payment_account || '-'"></dd></div>
                            <div><dt class="text-emerald-200">Amount</dt><dd>THB <span x-text="total().toLocaleString()"></span></dd></div>
                            <template x-if="payment.qr_payment_image">
                                <div><dt class="text-emerald-200">Reference QR image</dt><dd><a class="underline" :href="payment.qr_payment_image" target="_blank">Open uploaded account QR</a></dd></div>
                            </template>
                        </dl>
                    </div>
                </template>
                <p class="mt-3 text-emerald-100" x-text="payment.instructions || 'Upload your payment slip after transfer. Admin approval will activate tickets.'"></p>
            </div>
            <label class="mt-4 block text-sm text-zinc-300">Payment note<textarea class="mt-1 w-full rounded-md border border-white/10 bg-zinc-950 px-3 py-2 text-white" name="payment_note" rows="3"></textarea></label>
            <button class="mt-5 w-full rounded-md bg-emerald-400 px-4 py-3 font-semibold text-zinc-950 hover:bg-emerald-300">Submit order</button>
        </form>
    </div>
</x-layouts.app>
