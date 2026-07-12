@php
    $socialImagePath = $event->social_image_path ?: $event->poster_path;
    $plainDescription = trim(preg_replace('/\s+/', ' ', strip_tags($eventDescriptionHtml ?? $event->description ?? '')));
    $socialDescription = str($event->social_description ?: $plainDescription)->limit(220, '');
    $eventImageUrl = $socialImagePath ? asset('uploads/'.$socialImagePath) : null;
    $lowestPrice = $event->ticketTypes->min('price_thb');
    $structuredData = array_filter([
        '@context' => 'https://schema.org',
        '@type' => 'Event',
        'name' => $event->name,
        'description' => (string) $socialDescription,
        'startDate' => $event->starts_at->toIso8601String(),
        'endDate' => $event->ends_at->toIso8601String(),
        'eventStatus' => 'https://schema.org/EventScheduled',
        'eventAttendanceMode' => 'https://schema.org/OfflineEventAttendanceMode',
        'image' => $eventImageUrl ? [$eventImageUrl] : null,
        'url' => route('events.show', $event),
        'location' => [
            '@type' => 'Place',
            'name' => $event->venue,
            'address' => $event->location ?: $event->venue,
            'url' => $event->location_url,
        ],
        'organizer' => $event->hosted_by ? array_filter([
            '@type' => 'Organization',
            'name' => $event->hosted_by,
            'url' => $event->hosted_by_url,
        ]) : null,
        'offers' => $lowestPrice !== null ? [
            '@type' => 'Offer',
            'url' => route('events.show', $event).'#checkout',
            'price' => (string) $lowestPrice,
            'priceCurrency' => 'THB',
            'availability' => $event->ticketTypes->isNotEmpty() ? 'https://schema.org/InStock' : 'https://schema.org/SoldOut',
            'validFrom' => optional($event->ticketTypes->pluck('sale_starts_at')->filter()->sort()->first())->toIso8601String(),
        ] : null,
    ]);
@endphp
<x-layouts.app
    :title="$event->name"
    :meta-description="$socialDescription"
    :meta-image="$eventImageUrl"
    :canonical-url="route('events.show', $event)"
    :structured-data="$structuredData"
>
    @php
        $bank = collect(config('thai_banks'))->firstWhere('name', $event->bank_name);
        $checkoutTickets = $event->ticketTypes->values()->map(fn ($ticket, $index) => [
            'id' => $ticket->id,
            'itemIndex' => $index,
            'name' => $ticket->name,
            'price' => $ticket->price_thb,
            'maxQty' => $ticket->price_thb == 0 ? 1 : 20,
        ])->values();
        $visibleCheckoutCoupons = $event->coupons->filter->show_on_checkout;
        $visibleEventPromotions = $event->promotions->filter->show_on_event_page;
        $couponPayload = fn ($coupon) => [
            'code' => $coupon->code,
            'type' => $coupon->discount_type,
            'scope' => $coupon->discount_scope,
            'value' => $coupon->discount_value,
            'ticket_type_id' => $coupon->ticket_type_id,
            'show_on_checkout' => $coupon->show_on_checkout,
        ];
        $checkoutCoupons = $event->coupons->map($couponPayload)->values();
        $visibleCouponCodes = $visibleCheckoutCoupons->pluck('code')->values();
        $checkoutPromotions = $event->promotions->map(fn ($promotion) => [
            'id' => $promotion->id,
            'name' => $promotion->name,
            'description' => $promotion->description,
            'type' => $promotion->promotion_type,
            'scope' => $promotion->discount_scope,
            'value' => $promotion->discount_value,
            'ticket_type_id' => $promotion->ticket_type_id,
            'buy_quantity' => $promotion->buy_quantity,
            'get_quantity' => $promotion->get_quantity,
            'min_quantity' => $promotion->min_quantity,
            'max_discount_thb' => $promotion->max_discount_thb,
            'usage_limit' => $promotion->usage_limit,
            'used_count' => $promotion->used_count,
            'combines_with_coupons' => $promotion->combines_with_coupons,
            'show_on_event_page' => $promotion->show_on_event_page,
            'summary' => $promotion->displaySummary(),
        ])->values();
        $paymentMethods = $event->enabledPaymentMethods();
        $paymentOptions = collect($event->paymentOptions())->where('is_active', true)->map(function ($account) {
            $bank = collect(config('thai_banks'))->firstWhere('name', $account['bank_name'] ?? null);

            return $account + [
                'bank_logo' => $bank ? asset($bank['logo']) : null,
                'bank_display_name' => $bank ? $bank['name'].' / '.$bank['thai_name'] : ($account['bank_name'] ?? null),
            ];
        })->values();
        $checkoutLegalDocs = app(\App\Services\LegalDocumentService::class)->modal(['terms', 'privacy', 'refund']);
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
                    <p class="inline-flex items-center gap-2 text-sm text-emerald-600 dark:text-emerald-300"><x-icon name="calendar-days" />{{ $event->starts_at->format('D, M j, Y H:i') }} - {{ $event->ends_at->format('H:i') }}</p>
                    <h1 class="mt-2 text-3xl font-semibold text-zinc-950 dark:text-white">{{ $event->name }}</h1>
                    @if($event->show_countdown)
                        <div class="mt-4 rounded-md border border-emerald-400/30 bg-emerald-400/10 p-4" x-data="eventCountdown({ startsAt: @js($event->starts_at->toIso8601String()) })" x-init="init()">
                            <div class="text-sm font-medium text-emerald-800 dark:text-emerald-100" x-text="label"></div>
                            <div class="mt-3 grid grid-cols-4 gap-2 text-center">
                                <div class="rounded-md bg-white p-2 dark:bg-zinc-950"><div class="text-2xl font-semibold text-zinc-950 dark:text-white" x-text="days"></div><div class="text-xs text-zinc-500"><x-t en="Days" th="วัน" /></div></div>
                                <div class="rounded-md bg-white p-2 dark:bg-zinc-950"><div class="text-2xl font-semibold text-zinc-950 dark:text-white" x-text="hours"></div><div class="text-xs text-zinc-500"><x-t en="Hours" th="ชม." /></div></div>
                                <div class="rounded-md bg-white p-2 dark:bg-zinc-950"><div class="text-2xl font-semibold text-zinc-950 dark:text-white" x-text="minutes"></div><div class="text-xs text-zinc-500"><x-t en="Min" th="นาที" /></div></div>
                                <div class="rounded-md bg-white p-2 dark:bg-zinc-950"><div class="text-2xl font-semibold text-zinc-950 dark:text-white" x-text="seconds"></div><div class="text-xs text-zinc-500"><x-t en="Sec" th="วิ" /></div></div>
                            </div>
                        </div>
                    @endif
                    <div class="mt-3 space-y-3 text-zinc-700 dark:text-zinc-300 [&_a]:text-emerald-700 [&_a]:underline dark:[&_a]:text-emerald-200 [&_blockquote]:border-l-4 [&_blockquote]:border-emerald-300 [&_blockquote]:pl-4 [&_li]:ml-5 [&_li]:list-disc" data-i18n-skip>
                        {!! $eventDescriptionHtml !!}
                    </div>
                    <dl class="mt-5 grid gap-3 text-sm text-zinc-700 dark:text-zinc-300">
                        <div><dt class="inline-flex items-center gap-1.5 text-zinc-500"><x-icon name="building-2" class="h-3.5 w-3.5" /><x-t en="Venue" th="สถานที่" /></dt><dd>{{ $event->venue }}</dd></div>
                        <div><dt class="inline-flex items-center gap-1.5 text-zinc-500"><x-icon name="map-pin" class="h-3.5 w-3.5" /><x-t en="Location" th="ที่ตั้ง" /></dt><dd>
                            @if($event->location_url)
                                <a class="inline-flex items-center gap-1.5 text-emerald-700 underline dark:text-emerald-200" href="{{ $event->location_url }}" target="_blank" rel="noopener"><x-icon name="map-pin" class="h-3.5 w-3.5" />{{ $event->location ?: '' }}@unless($event->location)<x-t en="Open map" th="เปิดแผนที่" />@endunless</a>
                            @else
                                {{ $event->location }}
                            @endif
                        </dd></div>
                        <div><dt class="inline-flex items-center gap-1.5 text-zinc-500"><x-icon name="heart-handshake" class="h-3.5 w-3.5" /><x-t en="Hosted by" th="ผู้จัด" /></dt><dd>
                            @if($event->hosted_by_url)
                                <a class="inline-flex items-center gap-1.5 text-emerald-700 underline dark:text-emerald-200" href="{{ $event->hosted_by_url }}" target="_blank" rel="noopener"><x-icon name="heart-handshake" class="h-3.5 w-3.5" />{{ $event->hosted_by ?: '' }}@unless($event->hosted_by)<x-t en="Host" th="ผู้จัด" />@endunless</a>
                            @else
                                {{ $event->hosted_by }}
                            @endif
                        </dd></div>
                    </dl>
                </div>
            </div>
        </section>

        @if($freeTicketGateSurveyUrl)
            <div class="scroll-mt-6 rounded-lg border border-emerald-400/30 bg-emerald-400/10 p-8 text-center">
                <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-emerald-400/20">
                    <x-icon name="file-text" class="h-8 w-8 text-emerald-600 dark:text-emerald-300" />
                </div>
                <h2 class="mt-4 text-2xl font-bold text-zinc-950 dark:text-white"><x-t en="Get Your Free Ticket" th="รับตั๋วฟรี" /></h2>
                <p class="mt-2 text-zinc-700 dark:text-zinc-300"><x-t en="Complete a short survey to receive your free ticket instantly." th="ทำแบบสอบถามสั้นๆ เพื่อรับตั๋วฟรีทันที" /></p>
                <a class="mt-6 inline-flex items-center gap-2 rounded-md bg-emerald-400 px-6 py-3 text-lg font-semibold text-zinc-950 hover:bg-emerald-300" href="{{ $freeTicketGateSurveyUrl }}">
                    <x-icon name="file-text" /><x-t en="Take Survey & Get Ticket" th="ทำแบบสอบถามและรับตั๋ว" />
                </a>
            </div>
        @else
        <form id="checkout" method="POST" action="{{ route('orders.store') }}" enctype="multipart/form-data" class="scroll-mt-6 rounded-lg border border-zinc-200 dark:border-white/10 bg-white dark:bg-white/[0.04] p-5" @submit="prepareSubmit($event)" x-data="checkout({
            eventId: {{ $event->id }},
            tickets: @js($checkoutTickets),
            coupons: @js($checkoutCoupons),
            visibleCouponCodes: @js($visibleCouponCodes),
            promotions: @js($checkoutPromotions),
            paymentMethods: @js($paymentMethods),
            paymentOptions: @js($paymentOptions),
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
            customerName: @js(auth()->user()->name ?? old('customer_name', $surveyPrefill['name'] ?? '')),
            customerPhone: @js(auth()->user()->phone ?? old('customer_phone', $surveyPrefill['phone'] ?? '')),
            freeApprovalSurveyUrl: @js($freeApprovalSurveyUrl),
            beamEnabled: @js((bool) $event->beam_enabled),
            beamFeeBehavior: @js($event->beam_fee_behavior),
            beamFeePercent: @js($event->beamFeePercent()),
        })">
            @csrf
            @guest
                @if(config('services.line.client_id') && config('services.line.client_secret'))
                    <div class="mb-5 rounded-md border border-emerald-400/30 bg-emerald-400/10 p-4">
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <div>
                                <div class="font-semibold text-emerald-950 dark:text-emerald-50"><x-t en="Get tickets faster with LINE" th="ซื้อง่ายขึ้นด้วย LINE" /></div>
                                <p class="mt-1 text-sm text-emerald-800 dark:text-emerald-100"><x-t en="Login with LINE to save tickets to your profile automatically." th="เข้าสู่ระบบด้วย LINE เพื่อบันทึกตั๋วไว้ในโปรไฟล์" /></p>
                            </div>
                            <a class="inline-flex items-center gap-2 rounded-md bg-[#06c755] px-4 py-2 text-sm font-semibold text-zinc-950" href="{{ route('auth.social', ['provider' => 'line', 'redirect' => request()->getRequestUri()]) }}"><x-icon name="log-in" /><x-t en="Login with LINE" th="เข้าสู่ระบบด้วย LINE" /></a>
                        </div>
                    </div>
                @endif
            @endguest

            <h2 class="inline-flex items-center gap-2 text-xl font-semibold text-zinc-950 dark:text-white"><x-icon name="ticket" class="h-5 w-5 text-emerald-500" /><x-t en="Reserve Your Spot" th="เลือกตั๋ว" /></h2>
            @if($event->promotions->isNotEmpty() && 1==2)
                <div class="mt-4 rounded-md border border-emerald-400/30 bg-emerald-400/10 p-4 text-sm text-emerald-950 dark:text-emerald-50">
                    <div class="inline-flex items-center gap-2 font-semibold"><x-icon name="sparkles" /><x-t en="Today’s promotions" th="โปรโมชันวันนี้" /></div>
                    <div class="mt-3 grid gap-2">
                        @foreach($event->promotions as $promotion)
                            <div class="rounded-md bg-white/70 px-3 py-2 dark:bg-zinc-950/60">
                                <div class="flex flex-wrap items-center justify-between gap-2">
                                    <span class="font-semibold">{{ $promotion->name }}</span>
                                    <span class="text-xs font-medium text-emerald-700 dark:text-emerald-200">{{ $promotion->displaySummary() }}</span>
                                </div>
                                @if($promotion->description)
                                    <p class="mt-1 text-xs text-emerald-800 dark:text-emerald-100">{{ $promotion->description }}</p>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
            <div class="mt-4 grid gap-3 rounded-md transition" data-checkout-section="tickets" :class="validationAttempted && invalidSection === 'tickets' ? 'ring-2 ring-rose-400 ring-offset-2 ring-offset-white dark:ring-offset-zinc-950' : ''">
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
                            <div class="flex items-center gap-2">
                                @if($ticket->price_thb == 0)
                                    <span class="text-xs text-zinc-400"><x-t en="max 1" th="สูงสุด 1" /></span>
                                @endif
                                <div class="grid grid-cols-[40px_40px_40px] overflow-hidden rounded-md border border-zinc-200 dark:border-white/10">
                                    <button class="grid place-items-center bg-white dark:bg-zinc-950 px-3 py-2 text-zinc-800 dark:text-zinc-100 hover:bg-zinc-100 dark:hover:bg-white/10" type="button" @click="decrement({{ $ticket->id }})" aria-label="Remove ticket"><x-icon name="minus" /></button>
                                    <input class="border-x border-zinc-200 dark:border-white/10 bg-white dark:bg-zinc-950 px-2 py-2 text-center text-zinc-950 dark:text-white" name="items[{{ $loop->index }}][quantity]" 
                                        type="text" inputmode="numeric" pattern="\d*" x-model.number="quantities[{{ $ticket->id }}]" @input="syncHolderNames({{ $ticket->id }}); notifyCart({{ $ticket->id }})" @blur="if (Number(quantities[{{ $ticket->id }}]) > maxQty({{ $ticket->id }})) { quantities[{{ $ticket->id }}] = maxQty({{ $ticket->id }}); syncHolderNames({{ $ticket->id }}); notifyCart({{ $ticket->id }}); }">
                                    <button class="grid place-items-center bg-white dark:bg-zinc-950 px-3 py-2 text-zinc-800 dark:text-zinc-100 hover:bg-zinc-100 dark:hover:bg-white/10 disabled:opacity-40 disabled:cursor-not-allowed" type="button" @click="increment({{ $ticket->id }})" :disabled="atMax({{ $ticket->id }})" aria-label="Add ticket"><x-icon name="plus" /></button>
                                </div>
                            </div>
                        </div>
                        <div>
                        <p class="text-sm text-zinc-600 dark:text-zinc-400">{{ $ticket->description }}</p>
                        </div>
                    </div>
                @empty
                    <div class="rounded-md border border-amber-400/30 bg-amber-400/10 p-4 text-sm text-amber-800 dark:text-amber-100"><x-t en="No ticket types are on sale right now." th="ตอนนี้ยังไม่มีประเภทตั๋วที่เปิดขาย" /></div>
                @endforelse
            </div>

            <div class="mt-5 rounded-md border border-zinc-200 dark:border-white/10 bg-zinc-50 dark:bg-zinc-900 p-4 text-sm text-zinc-700 dark:text-zinc-300" x-cloak x-show="subtotal() === 0">
                <x-t en="Select at least one ticket to continue." th="กรุณาเลือกตั๋วอย่างน้อย 1 ใบเพื่อดำเนินการต่อ" />
            </div>
            <div class="mt-4 rounded-md border border-rose-400/30 bg-rose-400/10 px-4 py-3 text-sm text-rose-800 dark:text-rose-100" x-cloak x-show="errorMessage" x-text="errorMessage"></div>

            <div x-cloak x-show="cartQuantity() > 0" x-transition>
                <div class="mt-5 grid gap-4 rounded-md transition sm:grid-cols-2" data-checkout-section="customer" :class="validationAttempted && invalidSection === 'customer' ? 'ring-2 ring-rose-400 ring-offset-2 ring-offset-white dark:ring-offset-zinc-950' : ''">
                    <label class="text-sm font-medium text-zinc-700 dark:text-zinc-200"><x-t en="Name" th="ชื่อ" /> <span class="rounded bg-rose-400/20 px-1.5 py-0.5 text-xs text-rose-700 dark:text-rose-200"><x-t en="required" th="จำเป็น" /></span><input class="mt-1 w-full rounded-md border border-emerald-400/40 bg-white dark:bg-zinc-950 px-3 py-2 text-zinc-950 dark:text-white focus:border-emerald-300 focus:outline-none" name="customer_name" x-model="customerName" @input="syncDefaultHolderNames()" required></label>
                    <label class="text-sm font-medium text-zinc-700 dark:text-zinc-200"><x-t en="Phone" th="เบอร์โทร" /> <span class="rounded bg-rose-400/20 px-1.5 py-0.5 text-xs text-rose-700 dark:text-rose-200"><x-t en="required" th="จำเป็น" /></span><input class="mt-1 w-full rounded-md border border-emerald-400/40 bg-white dark:bg-zinc-950 px-3 py-2 text-zinc-950 dark:text-white focus:border-emerald-300 focus:outline-none" name="customer_phone" x-model="customerPhone" required></label>
                    <label class="text-sm text-zinc-700 dark:text-zinc-300"><x-t en="Email" th="อีเมล" /><input class="mt-1 w-full rounded-md border border-zinc-200 dark:border-white/10 bg-white dark:bg-zinc-950 px-3 py-2 text-zinc-950 dark:text-white" name="customer_email" value="{{ auth()->user()->email ?? old('customer_email', $surveyPrefill['email'] ?? '') }}"></label>
                    @if($event->coupons->isNotEmpty())
                        <label class="text-sm text-zinc-700 dark:text-zinc-300"><x-t en="Coupon" th="คูปอง" />
                            <div class="mt-1 grid gap-2 sm:grid-cols-[1fr_auto]">
                                <input class="w-full rounded-md border border-zinc-200 dark:border-white/10 bg-white dark:bg-zinc-950 px-3 py-2 uppercase text-zinc-950 dark:text-white" name="coupon_code" placeholder="EARLYBIRD" x-model="couponCode" @input="couponApplied = false; couponMessage = ''">
                                <button class="inline-flex items-center justify-center gap-2 rounded-md border border-emerald-300 px-3 py-2 font-semibold text-emerald-700 hover:bg-emerald-400/10 dark:border-emerald-400/40 dark:text-emerald-100" type="button" @click="applyTypedCoupon()"><x-icon name="tag" /><x-t en="Apply" th="ใช้" /></button>
                            </div>
                            <p class="mt-2 text-xs" x-show="couponMessage" x-text="couponMessage" :class="couponApplied ? 'text-emerald-700 dark:text-emerald-200' : 'text-rose-700 dark:text-rose-200'"></p>
                        </label>
                    @endif
                </div>
                
                @if($visibleCheckoutCoupons->isNotEmpty())
                    <div class="mt-4 rounded-md border border-zinc-200 dark:border-white/10 bg-zinc-50 dark:bg-zinc-900 p-4 text-sm text-zinc-700 dark:text-zinc-300" x-cloak x-show="applicableCoupons().length > 0">
                        <div class="inline-flex items-center gap-2 font-medium text-zinc-950 dark:text-white"><x-icon name="tag" /><x-t en="Available coupons" th="คูปองที่ใช้ได้" /></div>
                        <div class="mt-2 flex flex-wrap gap-2">
                            <template x-for="coupon in applicableCoupons()" :key="coupon.code">
                                <button class="inline-flex items-center gap-1.5 rounded bg-zinc-100 dark:bg-white/10 px-2 py-1 font-mono text-xs font-semibold text-emerald-700 dark:text-emerald-200 hover:bg-emerald-400/20" type="button" @click="applyCoupon(coupon.code)"><x-icon name="tag" class="h-3.5 w-3.5" /><span x-text="coupon.code"></span></button>
                            </template>
                        </div>
                    </div>
                @endif

                @if($visibleEventPromotions->isNotEmpty())
                    <div class="mt-4 rounded-md border border-emerald-400/30 bg-emerald-400/10 p-4 text-sm text-emerald-950 dark:text-emerald-50" x-cloak x-show="activePromotions().length > 0">
                        <div class="inline-flex items-center gap-2 font-medium"><x-icon name="sparkles" /><x-t en="Active promotions" th="โปรโมชันที่ใช้ได้" /></div>
                        <div class="mt-3 grid gap-2">
                            <template x-for="promotion in visibleActivePromotions()" :key="promotion.id">
                                <div class="rounded-md bg-white/70 px-3 py-2 dark:bg-zinc-950/60">
                                    <div class="flex flex-wrap items-center justify-between gap-2">
                                        <span class="font-semibold" x-text="promotion.name"></span>
                                        <span class="text-xs font-medium text-emerald-700 dark:text-emerald-200" x-text="promotion.summary"></span>
                                    </div>
                                    <p class="mt-1 text-xs text-emerald-800 dark:text-emerald-100" x-show="promotion.description" x-text="promotion.description"></p>
                                    <p class="mt-1 text-xs text-emerald-700 dark:text-emerald-200" x-show="promotionConditionText(promotion)" x-text="promotionConditionText(promotion)"></p>
                                </div>
                            </template>
                        </div>
                    </div>
                    <div class="mt-4 rounded-md border border-sky-300/40 bg-sky-100/70 p-4 text-sm text-sky-950 dark:border-sky-300/20 dark:bg-sky-400/10 dark:text-sky-100" x-cloak x-show="promotionHints().length > 0">
                        <div class="inline-flex items-center gap-2 font-medium"><x-icon name="sparkles" /><x-t en="Unlock more savings" th="ซื้อเพิ่มเพื่อรับส่วนลด" /></div>
                        <div class="mt-2 grid gap-2">
                            <template x-for="hint in promotionHints()" :key="hint.id">
                                <div>
                                    <p x-text="hint.text"></p>
                                    <p class="mt-1 text-xs text-sky-800 dark:text-sky-200" x-show="hint.condition" x-text="hint.condition"></p>
                                </div>
                            </template>
                        </div>
                    </div>
                @endif


                <div class="mt-5 rounded-md border border-emerald-400/20 bg-emerald-400/10 p-4 text-sm text-emerald-950 dark:text-emerald-50">
                    <div class="flex items-center justify-between gap-3">
                        <strong class="inline-flex items-center gap-2"><x-icon name="wallet" /><span x-text="total() === 0 ? TicketFlowLanguage.format({ en: 'Free checkout', th: 'รับตั๋วฟรี' }) : (TicketFlowLanguage.format(selectedPayment().label) || paymentMethodLabel(paymentMethod))"></span></strong>
                        <span class="rounded bg-emerald-300 px-2 py-1 font-semibold text-zinc-950">THB <span x-text="total().toLocaleString()"></span></span>
                    </div>
                    <dl class="mt-3 grid gap-1 text-sm">
                        <div class="flex justify-between gap-3"><dt class="text-emerald-700 dark:text-emerald-200"><x-t en="Subtotal" th="ยอดรวม" /></dt><dd>THB <span x-text="subtotal().toLocaleString()"></span></dd></div>
                        @if($event->coupons->isNotEmpty())
                        <div class="flex justify-between gap-3" x-show="discount() > 0" x-cloak>
                            <dt class="text-emerald-700 dark:text-emerald-200"><x-t en="Coupon discount" th="ส่วนลดคูปอง" /> <span class="font-mono text-xs" x-text="couponCode"></span></dt>
                            <dd>- THB <span x-text="discount().toLocaleString()"></span></dd>
                        </div>
                        @endif
                        @if($event->promotions->isNotEmpty())
                        <div class="flex justify-between gap-3" x-show="promotionDiscount() > 0" x-cloak>
                            <dt class="text-emerald-700 dark:text-emerald-200"><x-t en="Promotion discount" th="ส่วนลดโปรโมชัน" /> <span class="text-xs" x-text="activePromotionNames()"></span></dt>
                            <dd>- THB <span x-text="promotionDiscount().toLocaleString()"></span></dd>
                        </div>
                        @endif
                    </dl>
                    <template x-if="total() === 0">
                        <div class="mt-3 rounded-md border border-emerald-400/30 bg-white/70 p-3 dark:bg-zinc-950/60">
                            <div class="font-semibold"><x-t en="No payment required" th="ไม่ต้องชำระเงิน" /></div>
                            <p class="mt-1 text-sm text-emerald-800 dark:text-emerald-100"><x-t en="This order total is zero, so no payment method or slip is needed. Tickets will be generated automatically." th="ยอดรวมเป็นศูนย์ ไม่ต้องเลือกวิธีชำระเงินหรือแนบสลิป ระบบจะสร้างตั๋วให้อัตโนมัติ" /></p>
                        </div>
                    </template>
                    <template x-if="total() > 0 && paymentMethod === 'bank_transfer'">
                        <dl class="mt-3 grid gap-2">
                            <div><dt class="text-emerald-700 dark:text-emerald-200"><x-t en="Bank" th="ธนาคาร" /></dt><dd class="mt-1 flex items-center gap-2">
                                <template x-if="selectedPayment().bank_logo || payment.bank_logo">
                                    <img class="h-9 w-9 rounded-md bg-white object-contain p-1" :src="selectedPayment().bank_logo || payment.bank_logo" :alt="selectedPayment().bank_display_name || selectedPayment().bank_name || payment.bank_display_name || payment.bank_name">
                                </template>
                                <span x-text="selectedPayment().bank_display_name || selectedPayment().bank_name || payment.bank_display_name || payment.bank_name || TicketFlowLanguage.format({ en: 'Set bank name in admin event settings', th: 'ตั้งค่าชื่อธนาคารในหน้าแอดมิน' })"></span>
                            </dd></div>
                            <div><dt class="text-emerald-700 dark:text-emerald-200"><x-t en="Account name" th="ชื่อบัญชี" /></dt><dd x-text="selectedPayment().account_name || payment.bank_account_name || '-'"></dd></div>
                            <div><dt class="text-emerald-700 dark:text-emerald-200"><x-t en="Account number" th="เลขบัญชี" /></dt><dd class="font-mono" x-text="selectedPayment().account_number || payment.bank_account_number || '-'"></dd></div>
                        </dl>
                    </template>
                    <template x-if="total() > 0 && paymentMethod === 'beam'">
                        <div class="mt-3 rounded-md border border-emerald-400/30 bg-white/70 p-3 dark:bg-zinc-950/60">
                            <div class="font-semibold"><x-t en="Beam Checkout" th="ชำระด้วย Beam" /></div>
                            <p class="mt-1 text-sm text-emerald-800 dark:text-emerald-100"><x-t en="You'll complete payment via Beam Checkout. No slip is required. After payment, your tickets will be activated automatically." th="คุณจะชำระเงินผ่าน Beam Checkout ไม่ต้องแนบสลิป หลังชำระเงิน ตั๋วจะเปิดใช้งานอัตโนมัติ" /></p>
                            <template x-if="beamFeeBehavior === 'customer_pay' && total() > 0 && beamFeePercent > 0">
                                <p class="mt-2 text-xs text-emerald-700 dark:text-emerald-300">
                                    <x-t en="A Beam processing fee of" th="ค่าธรรมเนียม Beam" />
                                    <span x-text="beamFeePercent"></span>%
                                    <x-t en="will be added to the total." th="จะถูกเพิ่มในยอดรวม" />
                                </p>
                            </template>
                        </div>
                    </template>
                    <template x-if="total() > 0 && paymentMethod === 'cash'">
                        <div class="mt-3 rounded-md border border-emerald-400/30 bg-white/70 p-3 dark:bg-zinc-950/60">
                            <div class="font-semibold"><x-t en="Cash sale" th="ชำระเงินสด" /></div>
                            <p class="mt-1 text-sm text-emerald-800 dark:text-emerald-100"><x-t en="No slip is required. Admin approval is still required before tickets become active." th="ไม่ต้องแนบสลิป แต่ยังต้องรอแอดมินอนุมัติก่อนตั๋วใช้งานได้" /></p>
                        </div>
                    </template>
                    <template x-if="total() > 0 && paymentMethod === 'qr_payment'">
                        <div class="mt-3 grid gap-4 sm:grid-cols-[160px_1fr]">
                            <div class="grid place-items-center rounded-md bg-white p-3">
                                <img class="h-32 w-32 object-contain" :src="paymentQrUrl()" alt="QR payment code">
                            </div>
                            <dl class="grid gap-2">
                                <div><dt class="text-emerald-700 dark:text-emerald-200"><x-t en="QR account" th="บัญชี QR" /></dt><dd x-text="selectedPayment().account_name || payment.qr_payment_account_name || '-'"></dd></div>
                                <div><dt class="text-emerald-700 dark:text-emerald-200"><x-t en="PromptPay or account" th="พร้อมเพย์หรือบัญชี" /></dt><dd class="font-mono" x-text="selectedPayment().account_number || payment.qr_payment_account || '-'"></dd></div>
                                <div><dt class="text-emerald-700 dark:text-emerald-200"><x-t en="Amount" th="จำนวนเงิน" /></dt><dd>THB <span x-text="total().toLocaleString()"></span></dd></div>
                                <template x-if="payment.qr_payment_image">
                                    <div><dt class="text-emerald-700 dark:text-emerald-200"><x-t en="Reference QR image" th="รูป QR อ้างอิง" /></dt><dd><a class="inline-flex items-center gap-1.5 underline" :href="payment.qr_payment_image" target="_blank"><x-icon name="qr-code" class="h-3.5 w-3.5" /><x-t en="Open uploaded account QR" th="เปิดรูป QR ที่อัปโหลด" /></a></dd></div>
                                </template>
                                <div class="sm:col-span-2 text-sm text-emerald-800 dark:text-emerald-100"><x-t en="Scan this PromptPay QR to pay, then upload the bank payment slip image below." th="สแกน QR พร้อมเพย์นี้เพื่อชำระเงิน แล้วอัปโหลดรูปสลิปจากธนาคารด้านล่าง" /></div>
                            </dl>
                        </div>
                    </template>
                    <p class="mt-3 text-emerald-800 dark:text-emerald-100" x-text="paymentInstructions()"></p>
                </div>

                <div class="mt-4 grid gap-4 rounded-md transition sm:grid-cols-2" data-checkout-section="payment" x-show="total() > 0" x-cloak :class="validationAttempted && invalidSection === 'payment' ? 'ring-2 ring-rose-400 ring-offset-2 ring-offset-white dark:ring-offset-zinc-950' : ''">
                    <label class="text-sm font-medium text-zinc-700 dark:text-zinc-200"><x-t en="Payment method" th="วิธีชำระเงิน" /> <span class="rounded bg-rose-400/20 px-1.5 py-0.5 text-xs text-rose-700 dark:text-rose-200"><x-t en="required" th="จำเป็น" /></span>
                    <input type="hidden" name="payment_method" :value="paymentMethod">
                    <input type="hidden" name="payment_account_key" :value="paymentAccountKey">
                    <select class="mt-1 w-full rounded-md border border-emerald-400/40 bg-white dark:bg-zinc-950 px-3 py-2 text-zinc-950 dark:text-white" x-model="paymentAccountKey" @change="syncPaymentMethod()" required>
                        <template x-for="option in paymentOptions" :key="option.key">
                            <option :value="option.key" x-text="TicketFlowLanguage.format(option.label) || paymentMethodLabel(option.method)"></option>
                        </template>
                    </select></label>
                    <div class="text-sm text-zinc-700 dark:text-zinc-300" x-show="slipRequired()" x-cloak>
                        <x-t en="Bank payment slip image" th="รูปสลิปจากธนาคาร" /> <span class="rounded bg-rose-400/20 px-1.5 py-0.5 text-xs text-rose-700 dark:text-rose-200"><x-t en="required" th="จำเป็น" /></span>
                        <label class="mt-1 flex cursor-pointer items-center justify-center rounded-md border border-dashed border-emerald-400/50 bg-white dark:bg-zinc-950 px-3 py-2 font-semibold text-emerald-700 dark:text-emerald-200 hover:bg-emerald-400/10">
                        <input class="sr-only" name="slip" type="file" accept="image/*" @change="setSlipPreview($event)" :required="slipRequired()">
                            <span class="inline-flex items-center gap-2"><x-icon name="upload" /><x-t en="Attach bank slip" th="แนบสลิปธนาคาร" /></span>
                        </label>
                        <p class="mt-1 truncate text-xs text-zinc-500" x-text="slipName || TicketFlowLanguage.format({ en: 'No file attached yet', th: 'ยังไม่ได้แนบไฟล์' })"></p>
                        <template x-if="slipPreviewUrl">
                            <div class="mt-3 overflow-hidden rounded-md border border-zinc-200 bg-white dark:border-white/10 dark:bg-zinc-950">
                                <img class="max-h-72 w-full object-contain" :src="slipPreviewUrl" alt="Payment slip preview">
                            </div>
                        </template>
                    </div>
                    <div class="rounded-md border border-zinc-200 bg-zinc-50 p-3 text-sm text-zinc-600 dark:border-white/10 dark:bg-zinc-900 dark:text-zinc-300" x-show="!slipRequired()" x-cloak>
                        <x-t en="No slip is required for this order." th="ออเดอร์นี้ไม่ต้องแนบสลิป" />
                    </div>
                </div>
                <label class="mt-4 block text-sm text-zinc-700 dark:text-zinc-300" x-show="total() > 0" x-cloak><x-t en="Payment note" th="หมายเหตุการชำระเงิน" /><textarea class="mt-1 w-full rounded-md border border-zinc-200 dark:border-white/10 bg-white dark:bg-zinc-950 px-3 py-2 text-zinc-950 dark:text-white" name="payment_note" rows="3"></textarea></label>
                <div class="mt-5 rounded-md border border-zinc-200 dark:border-white/10 bg-zinc-50 dark:bg-zinc-900 p-4" x-data="{ holdersOpen: false }">
                    <button class="flex w-full items-center justify-between gap-3 text-left font-semibold text-zinc-950 dark:text-white" type="button" @click="holdersOpen = !holdersOpen" :aria-expanded="holdersOpen.toString()">
                        <span class="inline-flex items-center gap-2"><x-icon name="users" /><x-t en="Ticket holders" th="ชื่อผู้ถือบัตร" /></span>
                        <span class="text-sm text-emerald-700 dark:text-emerald-200" x-text="TicketFlowLanguage.format(holdersOpen ? { en: 'Hide', th: 'ซ่อน' } : { en: 'Edit', th: 'แก้ไข' })"></span>
                    </button>
                    <div class="mt-4 grid gap-4" x-cloak x-show="holdersOpen" x-transition>
                        <template x-for="ticket in tickets" :key="ticket.id">
                            <div class="grid gap-2" x-show="Number(quantities[ticket.id] || 0) > 0">
                                <div class="text-sm font-medium text-zinc-700 dark:text-zinc-200" x-text="ticket.name"></div>
                                <template x-for="index in holderSlots(ticket.id)" :key="`${ticket.id}-${index}`">
                                    <label class="text-sm text-zinc-700 dark:text-zinc-300">
                                        <span x-text="TicketFlowLanguage.format({ en: `Holder ${index + 1}`, th: `ผู้ถือบัตร ${index + 1}` })"></span>
                                        <input class="mt-1 w-full rounded-md border border-zinc-200 dark:border-white/10 bg-white dark:bg-zinc-950 px-3 py-2 text-zinc-950 dark:text-white" :name="`items[${ticket.itemIndex}][holders][${index}]`" x-model="holderNames[ticket.id][index]" @input="markHolderTouched(ticket.id, index)" placeholder="Ticket holder name">
                                    </label>
                                </template>
                            </div>
                        </template>
                    </div>
                </div>

                <div class="mt-5 rounded-md transition" data-checkout-section="legal" :class="validationAttempted && invalidSection === 'legal' ? 'ring-2 ring-rose-400 ring-offset-2 ring-offset-white dark:ring-offset-zinc-950' : ''">
                    <div x-data="{
                        openLegal: null,
                        activeLegalSection: 'terms',
                        lang: (navigator.language || '').toLowerCase().startsWith('th') ? 'th' : 'en',
                        legalDocs: @js($checkoutLegalDocs),
                    }">
                    <label class="flex items-start gap-3 rounded-md border border-zinc-200 bg-zinc-50 p-4 text-sm text-zinc-700 dark:border-white/10 dark:bg-zinc-900 dark:text-zinc-300">
                        <input class="mt-1 rounded border-zinc-300 text-emerald-500 focus:ring-emerald-400" name="terms_accepted" type="checkbox" value="1" x-model="termsAccepted" required>
                        <span>
                            <x-t en="I agree to the TicketFlow Terms and Conditions." th="ฉันยอมรับข้อกำหนดและเงื่อนไขของ TicketFlow" />
                            <span class="mt-2 flex flex-wrap gap-x-2 gap-y-1 text-xs text-zinc-500">
                                <button class="font-semibold text-emerald-700 underline dark:text-emerald-200" type="button" @click.prevent="openLegal = 'terms'; activeLegalSection = 'terms'"><x-t en="Terms and Conditions" th="ข้อกำหนดและเงื่อนไข" /></button>
                            </span>
                        </span>
                    </label>

                    <div class="fixed inset-0 z-50 grid place-items-center bg-zinc-950/70 px-4" x-cloak x-show="openLegal" x-transition @keydown.escape.window="openLegal = null">
                        <div class="max-h-[85vh] w-full max-w-lg overflow-y-auto rounded-lg border border-zinc-200 bg-white p-5 shadow-xl dark:border-white/10 dark:bg-zinc-950" @click.outside="openLegal = null">
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <h3 class="text-xl font-semibold text-zinc-950 dark:text-white" x-text="legalDocs.terms?.title[lang]"></h3>
                                    <a class="mt-1 inline-flex text-sm font-semibold text-emerald-700 underline dark:text-emerald-200" :href="legalDocs.terms?.url" target="_blank" rel="noopener"><x-t en="Open full page" th="เปิดหน้าเต็ม" /></a>
                                </div>
                                <button class="grid h-9 w-9 shrink-0 place-items-center rounded-md border border-zinc-200 text-zinc-700 hover:bg-zinc-100 dark:border-white/10 dark:text-zinc-100 dark:hover:bg-white/10" type="button" @click="openLegal = null" aria-label="Close"><x-icon name="x" /></button>
                            </div>
                            <div class="mt-4 inline-grid grid-cols-2 overflow-hidden rounded-md border border-zinc-200 text-sm font-semibold dark:border-white/10">
                                <button class="px-4 py-2" type="button" :class="lang === 'en' ? 'bg-emerald-400 text-zinc-950' : 'text-zinc-700 hover:bg-zinc-100 dark:text-zinc-200 dark:hover:bg-white/10'" @click="lang = 'en'">English</button>
                                <button class="px-4 py-2" type="button" :class="lang === 'th' ? 'bg-emerald-400 text-zinc-950' : 'text-zinc-700 hover:bg-zinc-100 dark:text-zinc-200 dark:hover:bg-white/10'" @click="lang = 'th'">ไทย</button>
                            </div>
                            <div class="mt-4 divide-y divide-zinc-200 overflow-hidden rounded-md border border-zinc-200 dark:divide-white/10 dark:border-white/10">
                                <template x-for="([key, doc]) in Object.entries(legalDocs)" :key="key">
                                    <section>
                                        <button class="flex w-full items-center justify-between gap-3 px-4 py-3 text-left font-semibold text-zinc-950 hover:bg-zinc-50 dark:text-white dark:hover:bg-white/10" type="button" @click="activeLegalSection = activeLegalSection === key ? null : key">
                                            <span x-text="doc.title[lang]"></span>
                                            <span class="text-lg leading-none" x-text="activeLegalSection === key ? '-' : '+'"></span>
                                        </button>
                                        <div class="px-4 pb-4" x-cloak x-show="activeLegalSection === key" x-transition>
                                            <div class="legal-document legal-document--compact max-w-none text-sm" x-html="doc.html[lang]"></div>
                                        </div>
                                    </section>
                                </template>
                            </div>
                            <p class="mt-4 text-xs text-zinc-500"><x-t en="This content comes from the same legal document source used by the public legal pages." th="เนื้อหานี้มาจากแหล่งเอกสารเดียวกับหน้าเอกสารทางกฎหมายสาธารณะ" /></p>
                            <button class="mt-5 inline-flex w-full items-center justify-center rounded-md bg-emerald-400 px-4 py-2 font-semibold text-zinc-950 hover:bg-emerald-300" type="button" @click="openLegal = null"><x-t en="I understand" th="เข้าใจแล้ว" /></button>
                        </div>
                    </div>
                </div>

                <button class="mt-5 inline-flex w-full items-center justify-center gap-2 rounded-md px-4 py-3 font-semibold transition" :class="canSubmitOrder() ? 'bg-emerald-400 text-zinc-950 hover:bg-emerald-300' : 'cursor-not-allowed bg-zinc-200 text-zinc-500 dark:bg-white/10 dark:text-zinc-400'" :data-disabled="(!canSubmitOrder()).toString()" @click="guardSubmit($event)"><x-icon name="send" /><x-t en="Submit order" th="ส่งคำสั่งซื้อ" /></button>
            </div>
        </form>
    </div>
    @endif

    @unless($freeTicketGateSurveyUrl)
    <a class="fixed bottom-4 left-1/2 z-40 inline-flex w-[calc(100%-2rem)] max-w-sm -translate-x-1/2 items-center justify-center gap-2 rounded-lg border border-emerald-300/70 bg-emerald-400 px-4 py-3 text-sm font-semibold text-zinc-950 shadow-lg shadow-emerald-950/20 transition hover:bg-emerald-300 focus:outline-none focus:ring-2 focus:ring-emerald-300 dark:border-emerald-300/30 sm:bottom-6 sm:right-6 sm:left-auto sm:w-auto sm:translate-x-0" href="#checkout" x-data="floatingReserve()" x-init="init()" x-show="visible()" x-transition.opacity>
        <x-icon name="ticket" class="h-5 w-5" />
        <span><x-t en="Reserve Your Spot" th="เลือกตั๋ว" /></span>
    </a>
    @endunless
</x-layouts.app>
