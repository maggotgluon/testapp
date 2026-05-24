@php
    $socialImagePath = $event->social_image_path ?: $event->poster_path;
    $socialDescription = $event->social_description ?: $event->description;
@endphp
<x-layouts.app
    :title="$event->name"
    :meta-description="$socialDescription"
    :meta-image="$socialImagePath ? asset('uploads/'.$socialImagePath) : null"
    :canonical-url="route('events.show', $event)"
>
    @php
        $bank = collect(config('thai_banks'))->firstWhere('name', $event->bank_name);
        $checkoutTickets = $event->ticketTypes->values()->map(fn ($ticket, $index) => [
            'id' => $ticket->id,
            'itemIndex' => $index,
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
            <div class="overflow-hidden rounded-lg border border-zinc-200 dark:border-white/10 bg-white dark:bg-white/[0.04]">
                <div class="aspect-[4/5] bg-gradient-to-br from-emerald-500 via-sky-500 to-zinc-800">
                    @if($event->poster_path)
                        <img class="h-full w-full object-cover" src="{{ asset('uploads/'.$event->poster_path) }}" alt="{{ $event->name }}">
                    @endif
                </div>
                <div class="p-5">
                    <p class="text-sm text-emerald-600 dark:text-emerald-300">{{ $event->starts_at->format('D, M j, Y H:i') }} - {{ $event->ends_at->format('H:i') }}</p>
                    <h1 class="mt-2 text-3xl font-semibold text-zinc-950 dark:text-white">{{ $event->name }}</h1>
                    @if($event->show_countdown)
                        <div class="mt-4 rounded-md border border-emerald-400/30 bg-emerald-400/10 p-4" x-data="eventCountdown({ startsAt: @js($event->starts_at->toIso8601String()) })" x-init="init()">
                            <div class="text-sm font-medium text-emerald-800 dark:text-emerald-100" x-text="label"></div>
                            <div class="mt-3 grid grid-cols-4 gap-2 text-center">
                                <div class="rounded-md bg-white p-2 dark:bg-zinc-950"><div class="text-2xl font-semibold text-zinc-950 dark:text-white" x-text="days"></div><div class="text-xs text-zinc-500">Days / วัน</div></div>
                                <div class="rounded-md bg-white p-2 dark:bg-zinc-950"><div class="text-2xl font-semibold text-zinc-950 dark:text-white" x-text="hours"></div><div class="text-xs text-zinc-500">Hours / ชม.</div></div>
                                <div class="rounded-md bg-white p-2 dark:bg-zinc-950"><div class="text-2xl font-semibold text-zinc-950 dark:text-white" x-text="minutes"></div><div class="text-xs text-zinc-500">Min / นาที</div></div>
                                <div class="rounded-md bg-white p-2 dark:bg-zinc-950"><div class="text-2xl font-semibold text-zinc-950 dark:text-white" x-text="seconds"></div><div class="text-xs text-zinc-500">Sec / วิ</div></div>
                            </div>
                        </div>
                    @endif
                    <div class="mt-3 space-y-3 text-zinc-700 dark:text-zinc-300 [&_a]:text-emerald-700 [&_a]:underline dark:[&_a]:text-emerald-200 [&_blockquote]:border-l-4 [&_blockquote]:border-emerald-300 [&_blockquote]:pl-4 [&_li]:ml-5 [&_li]:list-disc">
                        {!! $event->description !!}
                    </div>
                    <dl class="mt-5 grid gap-3 text-sm text-zinc-700 dark:text-zinc-300">
                        <div><dt class="text-zinc-500">Venue / สถานที่</dt><dd>{{ $event->venue }}</dd></div>
                        <div><dt class="text-zinc-500">Location / ที่ตั้ง</dt><dd>
                            @if($event->location_url)
                                <a class="text-emerald-700 underline dark:text-emerald-200" href="{{ $event->location_url }}" target="_blank" rel="noopener">{{ $event->location ?: 'Open map / เปิดแผนที่' }}</a>
                            @else
                                {{ $event->location }}
                            @endif
                        </dd></div>
                        <div><dt class="text-zinc-500">Hosted by / ผู้จัด</dt><dd>
                            @if($event->hosted_by_url)
                                <a class="text-emerald-700 underline dark:text-emerald-200" href="{{ $event->hosted_by_url }}" target="_blank" rel="noopener">{{ $event->hosted_by ?: 'Host / ผู้จัด' }}</a>
                            @else
                                {{ $event->hosted_by }}
                            @endif
                        </dd></div>
                    </dl>
                </div>
            </div>
        </section>

        <form method="POST" action="{{ route('orders.store') }}" enctype="multipart/form-data" class="rounded-lg border border-zinc-200 dark:border-white/10 bg-white dark:bg-white/[0.04] p-5" @submit="prepareSubmit($event)" x-data="checkout({
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
                'bank_logo' => $bank ? asset($bank['logo']) : null,
                'bank_display_name' => $bank ? $bank['name'].' / '.$bank['thai_name'] : $event->bank_name,
            ]),
            customerName: @js(auth()->user()->name ?? old('customer_name', '')),
        })">
            @csrf
            @guest
                @if(config('services.line.client_id') && config('services.line.client_secret'))
                    <div class="mb-5 rounded-md border border-emerald-400/30 bg-emerald-400/10 p-4">
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <div>
                                <div class="font-semibold text-emerald-950 dark:text-emerald-50">Get Ticket faster with LINE <br> ซื้อง่ายขึ้นด้วย LINE</div>
                                <p class="mt-1 text-sm text-emerald-800 dark:text-emerald-100">Login with LINE to save tickets to your profile automatically. <br> เข้าสู่ระบบด้วย LINE เพื่อบันทึกตั๋วไว้ในโปรไฟล์</p>
                            </div>
                            <a class="rounded-md bg-[#06c755] px-4 py-2 text-sm font-semibold text-zinc-950" href="{{ route('auth.social', ['provider' => 'line', 'redirect' => request()->getRequestUri()]) }}">Login with LINE / เข้าสู่ระบบด้วย LINE</a>
                        </div>
                    </div>
                @endif
            @endguest

            <h2 class="text-xl font-semibold text-zinc-950 dark:text-white">Reserve Your Spot / เลือกตั๋ว</h2>
            <div class="mt-4 grid gap-3">
                @forelse($event->ticketTypes as $ticket)
                    <div class="rounded-md border border-zinc-200 dark:border-white/10 bg-zinc-50 dark:bg-zinc-900 p-4">
                        <div class="flex items-center justify-between gap-4">
                            <div>
                                <h3 class="font-semibold text-zinc-950 dark:text-white">{{ $ticket->name }}</h3>
                                <div class="mt-1 flex flex-wrap items-baseline gap-2">
                                    <p class="text-sm font-semibold text-emerald-600 dark:text-emerald-300">THB {{ number_format($ticket->price_thb) }}</p>
                                    @if($ticket->full_price_thb && $ticket->full_price_thb > $ticket->price_thb)
                                        <p class="text-xs text-zinc-500 line-through">THB {{ number_format($ticket->full_price_thb) }}</p>
                                    @endif
                                </div>
                            </div>
                            <input type="hidden" name="items[{{ $loop->index }}][ticket_type_id]" value="{{ $ticket->id }}">
                            <div class="grid grid-cols-[40px_40px_40px] overflow-hidden rounded-md border border-zinc-200 dark:border-white/10">
                                <button class="bg-white dark:bg-zinc-950 px-3 py-2 text-lg text-zinc-800 dark:text-zinc-100 hover:bg-zinc-100 dark:hover:bg-white/10" type="button" @click="decrement({{ $ticket->id }})">-</button>
                                <input class="border-x border-zinc-200 dark:border-white/10 bg-white dark:bg-zinc-950 px-2 py-2 text-center text-zinc-950 dark:text-white" name="items[{{ $loop->index }}][quantity]" 
                                    type="text" inputmode="numeric" pattern="\d*" x-model.number="quantities[{{ $ticket->id }}]" @input="syncHolderNames({{ $ticket->id }})">
                                <button class="bg-white dark:bg-zinc-950 px-3 py-2 text-lg text-zinc-800 dark:text-zinc-100 hover:bg-zinc-100 dark:hover:bg-white/10" type="button" @click="increment({{ $ticket->id }})">+</button>
                            </div>
                        </div>
                        <div>
                        <p class="text-sm text-zinc-600 dark:text-zinc-400">{{ $ticket->description }}</p>
                        </div>
                    </div>
                @empty
                    <div class="rounded-md border border-amber-400/30 bg-amber-400/10 p-4 text-sm text-amber-800 dark:text-amber-100">No ticket types are on sale right now. / ตอนนี้ยังไม่มีประเภทตั๋วที่เปิดขาย</div>
                @endforelse
            </div>

            <div class="mt-5 rounded-md border border-zinc-200 dark:border-white/10 bg-zinc-50 dark:bg-zinc-900 p-4 text-sm text-zinc-700 dark:text-zinc-300" x-cloak x-show="subtotal() === 0">
                Select at least one ticket to continue. <br> กรุณาเลือกตั๋วอย่างน้อย 1 ใบเพื่อดำเนินการต่อ
            </div>
            <div class="mt-4 rounded-md border border-rose-400/30 bg-rose-400/10 px-4 py-3 text-sm text-rose-800 dark:text-rose-100" x-cloak x-show="errorMessage" x-text="errorMessage"></div>

            <div x-cloak x-show="subtotal() > 0" x-transition>
                <div class="mt-5 grid gap-4 sm:grid-cols-2">
                    <label class="text-sm font-medium text-zinc-700 dark:text-zinc-200">Name / ชื่อ <span class="rounded bg-rose-400/20 px-1.5 py-0.5 text-xs text-rose-700 dark:text-rose-200">required / จำเป็น</span><input class="mt-1 w-full rounded-md border border-emerald-400/40 bg-white dark:bg-zinc-950 px-3 py-2 text-zinc-950 dark:text-white focus:border-emerald-300 focus:outline-none" name="customer_name" x-model="customerName" @input="syncDefaultHolderNames()" required></label>
                    <label class="text-sm font-medium text-zinc-700 dark:text-zinc-200">Phone / เบอร์โทร <span class="rounded bg-rose-400/20 px-1.5 py-0.5 text-xs text-rose-700 dark:text-rose-200">required / จำเป็น</span><input class="mt-1 w-full rounded-md border border-emerald-400/40 bg-white dark:bg-zinc-950 px-3 py-2 text-zinc-950 dark:text-white focus:border-emerald-300 focus:outline-none" name="customer_phone" value="{{ auth()->user()->phone ?? old('customer_phone') }}" required></label>
                    <label class="text-sm text-zinc-700 dark:text-zinc-300">Email / อีเมล<input class="mt-1 w-full rounded-md border border-zinc-200 dark:border-white/10 bg-white dark:bg-zinc-950 px-3 py-2 text-zinc-950 dark:text-white" name="customer_email" value="{{ auth()->user()->email ?? old('customer_email') }}"></label>
                    @if($event->coupons->isNotEmpty())
                        <label class="text-sm text-zinc-700 dark:text-zinc-300">Coupon / คูปอง
                            <input class="mt-1 w-full rounded-md border border-zinc-200 dark:border-white/10 bg-white dark:bg-zinc-950 px-3 py-2 uppercase text-zinc-950 dark:text-white" name="coupon_code" placeholder="EARLYBIRD" x-model="couponCode">
                        </label>
                    @endif
                </div>
                
                @if($event->coupons->isNotEmpty())
                    <div class="mt-4 rounded-md border border-zinc-200 dark:border-white/10 bg-zinc-50 dark:bg-zinc-900 p-4 text-sm text-zinc-700 dark:text-zinc-300" x-cloak x-show="applicableCoupons().length > 0">
                        <div class="font-medium text-zinc-950 dark:text-white">Available coupons / คูปองที่ใช้ได้</div>
                        <div class="mt-2 flex flex-wrap gap-2">
                            <template x-for="coupon in applicableCoupons()" :key="coupon.code">
                                <button class="rounded bg-zinc-100 dark:bg-white/10 px-2 py-1 font-mono text-xs font-semibold text-emerald-700 dark:text-emerald-200 hover:bg-emerald-400/20" type="button" @click="applyCoupon(coupon.code)" x-text="coupon.code"></button>
                            </template>
                        </div>
                    </div>
                @endif


                <div class="mt-5 rounded-md border border-emerald-400/20 bg-emerald-400/10 p-4 text-sm text-emerald-950 dark:text-emerald-50">
                    <div class="flex items-center justify-between gap-3">
                        <strong x-text="paymentMethod === 'qr_payment' ? 'QR payment / ชำระด้วย QR' : 'Bank transfer / โอนผ่านธนาคาร'"></strong>
                        <span class="rounded bg-emerald-300 px-2 py-1 font-semibold text-zinc-950">THB <span x-text="total().toLocaleString()"></span></span>
                    </div>
                    <dl class="mt-3 grid gap-1 text-sm">
                        <div class="flex justify-between gap-3"><dt class="text-emerald-700 dark:text-emerald-200">Subtotal / ยอดรวม</dt><dd>THB <span x-text="subtotal().toLocaleString()"></span></dd></div>
                        @if($event->coupons->isNotEmpty())
                        <div class="flex justify-between gap-3"><dt class="text-emerald-700 dark:text-emerald-200">Coupon discount / ส่วนลดคูปอง</dt><dd>- THB <span x-text="discount().toLocaleString()"></span></dd></div>
                        @endif
                    </dl>
                    <template x-if="paymentMethod === 'bank_transfer'">
                        <dl class="mt-3 grid gap-2">
                            <div><dt class="text-emerald-700 dark:text-emerald-200">Bank / ธนาคาร</dt><dd class="mt-1 flex items-center gap-2">
                                <template x-if="payment.bank_logo">
                                    <img class="h-9 w-9 rounded-md bg-white object-contain p-1" :src="payment.bank_logo" :alt="payment.bank_display_name || payment.bank_name">
                                </template>
                                <span x-text="payment.bank_display_name || payment.bank_name || 'Set bank name in admin event settings / ตั้งค่าชื่อธนาคารในหน้าแอดมิน'"></span>
                            </dd></div>
                            <div><dt class="text-emerald-700 dark:text-emerald-200">Account name / ชื่อบัญชี</dt><dd x-text="payment.bank_account_name || '-'"></dd></div>
                            <div><dt class="text-emerald-700 dark:text-emerald-200">Account number / เลขบัญชี</dt><dd class="font-mono" x-text="payment.bank_account_number || '-'"></dd></div>
                        </dl>
                    </template>
                    <template x-if="paymentMethod === 'qr_payment'">
                        <div class="mt-3 grid gap-4 sm:grid-cols-[160px_1fr]">
                            <div class="grid place-items-center rounded-md bg-white p-3">
                                <img class="h-32 w-32 object-contain" :src="paymentQrUrl()" alt="QR payment code / QR สำหรับชำระเงิน">
                            </div>
                            <dl class="grid gap-2">
                                <div><dt class="text-emerald-700 dark:text-emerald-200">QR account / บัญชี QR</dt><dd x-text="payment.qr_payment_account_name || '-'"></dd></div>
                                <div><dt class="text-emerald-700 dark:text-emerald-200">PromptPay / account / พร้อมเพย์หรือบัญชี</dt><dd class="font-mono" x-text="payment.qr_payment_account || '-'"></dd></div>
                                <div><dt class="text-emerald-700 dark:text-emerald-200">Amount / จำนวนเงิน</dt><dd>THB <span x-text="total().toLocaleString()"></span></dd></div>
                                <template x-if="payment.qr_payment_image">
                                    <div><dt class="text-emerald-700 dark:text-emerald-200">Reference QR image / รูป QR อ้างอิง</dt><dd><a class="underline" :href="payment.qr_payment_image" target="_blank">Open uploaded account QR / เปิดรูป QR ที่อัปโหลด</a></dd></div>
                                </template>
                            </dl>
                        </div>
                    </template>
                    <p class="mt-3 text-emerald-800 dark:text-emerald-100" x-text="payment.instructions || 'Upload your payment slip after transfer. Admin approval will activate tickets. / อัปโหลดสลิปหลังชำระเงิน แอดมินจะตรวจสอบและอนุมัติตั๋ว'"></p>
                </div>

                <div class="mt-4 grid gap-4 sm:grid-cols-2">
                    <label class="text-sm font-medium text-zinc-700 dark:text-zinc-200">Payment method / วิธีชำระเงิน <span class="rounded bg-rose-400/20 px-1.5 py-0.5 text-xs text-rose-700 dark:text-rose-200">required / จำเป็น</span>
                    <select class="mt-1 w-full rounded-md border border-emerald-400/40 bg-white dark:bg-zinc-950 px-3 py-2 text-zinc-950 dark:text-white" name="payment_method" x-model="paymentMethod" required>
                        <option value="qr_payment">QR payment / ชำระด้วย QR</option>
                        <option value="bank_transfer">Direct bank transfer / โอนผ่านธนาคาร</option>
                    </select></label>
                    <div class="text-sm text-zinc-700 dark:text-zinc-300">
                        Payment slip / สลิปชำระเงิน <span class="rounded bg-rose-400/20 px-1.5 py-0.5 text-xs text-rose-700 dark:text-rose-200">required / จำเป็น</span>
                        <label class="mt-1 flex cursor-pointer items-center justify-center rounded-md border border-dashed border-emerald-400/50 bg-white dark:bg-zinc-950 px-3 py-2 font-semibold text-emerald-700 dark:text-emerald-200 hover:bg-emerald-400/10">
                            <input class="sr-only" name="slip" type="file" accept="image/*" @change="slipName = $event.target.files[0]?.name || ''" required>
                            Attach payment slip / แนบสลิป
                        </label>
                        <p class="mt-1 truncate text-xs text-zinc-500" x-text="slipName || 'No file attached yet / ยังไม่ได้แนบไฟล์'"></p>
                    </div>
                </div>
                <label class="mt-4 block text-sm text-zinc-700 dark:text-zinc-300">Payment note / หมายเหตุการชำระเงิน<textarea class="mt-1 w-full rounded-md border border-zinc-200 dark:border-white/10 bg-white dark:bg-zinc-950 px-3 py-2 text-zinc-950 dark:text-white" name="payment_note" rows="3"></textarea></label>
                <div class="mt-5 rounded-md border border-zinc-200 dark:border-white/10 bg-zinc-50 dark:bg-zinc-900 p-4">
                    <h3 class="font-semibold text-zinc-950 dark:text-white">Ticket holders / ชื่อผู้ถือบัตร</h3>
                    <!-- <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">Leave blank to use the buyer name. / เว้นว่างไว้เพื่อใช้ชื่อผู้ซื้อ</p> -->
                    <div class="mt-4 grid gap-4">
                        <template x-for="ticket in tickets" :key="ticket.id">
                            <div class="grid gap-2" x-show="Number(quantities[ticket.id] || 0) > 0">
                                <div class="text-sm font-medium text-zinc-700 dark:text-zinc-200" x-text="ticket.name"></div>
                                <template x-for="index in holderSlots(ticket.id)" :key="`${ticket.id}-${index}`">
                                    <label class="text-sm text-zinc-700 dark:text-zinc-300">
                                        <span x-text="`Holder ${index + 1} / ผู้ถือบัตร ${index + 1}`"></span>
                                        <input class="mt-1 w-full rounded-md border border-zinc-200 dark:border-white/10 bg-white dark:bg-zinc-950 px-3 py-2 text-zinc-950 dark:text-white" :name="`items[${ticket.itemIndex}][holders][${index}]`" x-model="holderNames[ticket.id][index]" @input="markHolderTouched(ticket.id, index)" placeholder="Ticket holder name / ใช้ชื่อผู้ถือบัตร" required>
                                    </label>
                                </template>
                            </div>
                        </template>
                    </div>
                </div>

                <button class="mt-5 w-full rounded-md bg-emerald-400 px-4 py-3 font-semibold text-zinc-950 hover:bg-emerald-300">Submit order / ส่งคำสั่งซื้อ</button>
            </div>
        </form>
    </div>
</x-layouts.app>
