@php
    $webPushEnabled = filled(config('webpush.vapid.public_key')) && filled(config('webpush.vapid.private_key'));
    $lineNotificationsEnabled = filled(config('services.line.messaging_channel_access_token')) && filled(config('services.line.messaging_channel_secret')) && filled(config('services.line.official_account_url'));
    $profileUser = $profileUser ?? auth()->user();
    $isViewingAsUser = $isViewingAsUser ?? false;
@endphp
<x-layouts.app title="Profile">
    @if($isViewingAsUser)
        <div class="mb-4 rounded-md border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-900 dark:border-amber-400/40 dark:bg-amber-400/10 dark:text-amber-100">
            <x-t en="Super admin profile view. You are viewing this customer profile for support." th="มุมมองโปรไฟล์สำหรับผู้ดูแลสูงสุด คุณกำลังดูโปรไฟล์ลูกค้าเพื่อช่วยตรวจสอบปัญหา" />
        </div>
    @endif
    <section class="flex flex-wrap items-center justify-between gap-4 rounded-lg border border-zinc-200 bg-white p-5 dark:border-white/10 dark:bg-white/[0.04]">
        <div class="flex flex-wrap items-center gap-4">
            @if($profileUser->avatar)
                <img class="h-16 w-16 rounded-full object-cover ring-2 ring-emerald-400/40" src="{{ $profileUser->avatar }}" alt="{{ $profileUser->name }}">
            @else
                <div class="grid h-16 w-16 place-items-center rounded-full bg-emerald-400 text-xl font-semibold text-zinc-950">{{ strtoupper(substr($profileUser->name, 0, 1)) }}</div>
            @endif
            <div>
                <h1 class="text-3xl font-semibold text-zinc-950 dark:text-white">{{ $profileUser->name }}</h1>
                <div class="mt-1 flex flex-wrap gap-x-3 gap-y-1 text-sm text-zinc-600 dark:text-zinc-400">
                    <span>{{ $profileUser->phone ?: 'No phone yet / ยังไม่มีเบอร์โทร' }}</span>
                    <span>{{ $profileUser->email ?: 'No email yet / ยังไม่มีอีเมล' }}</span>
                    <span>{{ strtoupper($profileUser->provider ?: 'guest') }}</span>
                </div>
            </div>
        </div>
        @unless($isViewingAsUser)
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button class="inline-flex items-center gap-2 rounded-md border border-zinc-200 px-4 py-2 text-sm font-semibold text-zinc-800 hover:bg-zinc-50 dark:border-white/10 dark:text-zinc-100 dark:hover:bg-white/10"><x-icon name="log-out" />Logout / ออกจากระบบ</button>
        </form>
        @else
            <a class="inline-flex items-center gap-2 rounded-md border border-zinc-200 px-4 py-2 text-sm font-semibold text-zinc-800 hover:bg-zinc-50 dark:border-white/10 dark:text-zinc-100 dark:hover:bg-white/10" href="{{ route('admin.users.edit', $profileUser) }}"><x-icon name="arrow-left" /><x-t en="Back to user" th="กลับไปหน้าผู้ใช้" /></a>
        @endunless
    </section>

    @unless($isViewingAsUser)
    <section class="mt-6 grid gap-3" x-data="{ openProfile: false, openNotifications: false }">
        <div class="rounded-lg border border-zinc-200 bg-white p-5 dark:border-white/10 dark:bg-white/[0.04]">
            <button class="flex w-full items-center justify-between gap-3 text-left" type="button" @click="openProfile = !openProfile">
                <span class="inline-flex items-center gap-2 text-lg font-semibold text-zinc-950 dark:text-white"><x-icon name="user" class="h-5 w-5 text-emerald-500" />Edit profile / แก้ไขโปรไฟล์</span>
                <span class="text-sm font-semibold text-emerald-700 dark:text-emerald-200" x-text="openProfile ? 'Hide / ซ่อน' : 'Show / แสดง'"></span>
            </button>
            <form method="POST" action="{{ route('profile.update') }}" class="mt-4 grid gap-4 sm:grid-cols-3" x-cloak x-show="openProfile" x-transition>
                @csrf
                @method('PATCH')
                <label class="text-sm font-medium text-zinc-700 dark:text-zinc-200">Name / ชื่อ
                    <input class="mt-1 w-full rounded-md border border-zinc-200 bg-white px-3 py-2 text-zinc-950 dark:border-white/10 dark:bg-zinc-950 dark:text-white" name="name" value="{{ old('name', auth()->user()->name) }}" required>
                </label>
                <label class="text-sm font-medium text-zinc-700 dark:text-zinc-200">Email / อีเมล
                    <input class="mt-1 w-full rounded-md border border-zinc-200 bg-white px-3 py-2 text-zinc-950 dark:border-white/10 dark:bg-zinc-950 dark:text-white" name="email" type="email" value="{{ old('email', auth()->user()->email) }}">
                </label>
                <label class="text-sm font-medium text-zinc-700 dark:text-zinc-200">Phone / เบอร์โทร
                    <input class="mt-1 w-full rounded-md border border-zinc-200 bg-white px-3 py-2 text-zinc-950 dark:border-white/10 dark:bg-zinc-950 dark:text-white" name="phone" value="{{ old('phone', auth()->user()->phone) }}">
                </label>
                <button class="inline-flex items-center justify-center gap-2 rounded-md bg-emerald-400 px-4 py-2 text-sm font-semibold text-zinc-950 sm:justify-self-start"><x-icon name="save" />Save profile / บันทึกโปรไฟล์</button>
            </form>
        </div>

        @if($webPushEnabled || $lineNotificationsEnabled)
        <div class="rounded-lg border border-zinc-200 bg-white p-5 dark:border-white/10 dark:bg-white/[0.04]">
            <button class="flex w-full items-center justify-between gap-3 text-left" type="button" @click="openNotifications = !openNotifications">
                <span class="inline-flex items-center gap-2 text-lg font-semibold text-zinc-950 dark:text-white"><x-icon name="bell" class="h-5 w-5 text-emerald-500" />Notification settings / ตั้งค่าการแจ้งเตือน</span>
                <span class="text-sm font-semibold text-emerald-700 dark:text-emerald-200" x-text="openNotifications ? 'Hide / ซ่อน' : 'Show / แสดง'"></span>
            </button>
            <div class="mt-4 grid gap-4 {{ $webPushEnabled && $lineNotificationsEnabled ? 'md:grid-cols-2' : '' }}" x-cloak x-show="openNotifications" x-transition>
                @if($webPushEnabled)
                <div>
                    <h2 class="inline-flex items-center gap-2 font-semibold text-zinc-950 dark:text-white"><x-icon name="bell" class="h-5 w-5 text-emerald-500" />Web Push / การแจ้งเตือนบนอุปกรณ์</h2>
                    <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">Enable reminders and ticket updates on this device. / เปิดรับการแจ้งเตือนและอัปเดตตั๋วบนอุปกรณ์นี้</p>
                    <div class="mt-4" x-data="webPushSettings()" x-init="init()">
                        <button class="inline-flex items-center gap-2 rounded-md bg-emerald-400 px-4 py-2 text-sm font-semibold text-zinc-950 disabled:cursor-not-allowed disabled:opacity-60" type="button" @click="subscribe()" :disabled="!supported || subscribed">
                            <x-icon name="bell" />Enable Web Push / เปิด Web Push
                        </button>
                        <button class="ml-2 inline-flex items-center gap-2 rounded-md border border-zinc-200 px-4 py-2 text-sm font-semibold text-zinc-800 disabled:cursor-not-allowed disabled:opacity-60 dark:border-white/10 dark:text-zinc-100" type="button" @click="unsubscribe()" :disabled="!subscribed">
                            <x-icon name="x" />Turn off / ปิด
                        </button>
                        <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400" x-text="message"></p>
                    </div>
                </div>
                @endif
                @if($lineNotificationsEnabled)
                <div>
                    <h2 class="inline-flex items-center gap-2 font-semibold text-zinc-950 dark:text-white"><x-icon name="heart-handshake" class="h-5 w-5 text-emerald-500" />LINE updates / แจ้งเตือนผ่าน LINE</h2>
                    <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">
                        @if(auth()->user()->provider === 'line')
                            LINE account connected. / เชื่อมต่อบัญชี LINE แล้ว
                        @else
                            Login with LINE to receive LINE ticket updates. / เข้าสู่ระบบด้วย LINE เพื่อรับอัปเดตตั๋วผ่าน LINE
                        @endif
                    </p>
                    <a class="mt-4 inline-flex items-center gap-2 rounded-md border border-zinc-200 px-4 py-2 text-sm font-semibold text-zinc-800 hover:border-emerald-300 dark:border-white/10 dark:text-zinc-100" href="{{ config('services.line.official_account_url') }}" target="_blank" rel="noopener"><x-icon name="heart-handshake" />Add LINE Official Account / เพิ่มเพื่อน LINE OA</a>
                </div>
                @endif
            </div>
        </div>
        @endif
    </section>
    @endunless

    <div class="mt-8 flex flex-wrap items-center gap-2">
        @php
            $profileRoute = $isViewingAsUser ? 'admin.users.profile' : 'profile';
            $profileRouteParams = $isViewingAsUser ? ['user' => $profileUser] : [];
        @endphp
        <a class="inline-flex items-center gap-2 rounded-md px-4 py-2 text-sm font-semibold {{ $activeView === 'orders' ? 'bg-emerald-400 text-zinc-950' : 'border border-zinc-200 text-zinc-700 hover:bg-zinc-50 dark:border-white/10 dark:text-zinc-200 dark:hover:bg-white/10' }}" href="{{ route($profileRoute, $profileRouteParams + ['view' => 'orders']) }}"><x-icon name="receipt" />Orders / ออเดอร์</a>
        <a class="inline-flex items-center gap-2 rounded-md px-4 py-2 text-sm font-semibold {{ $activeView === 'tickets' ? 'bg-emerald-400 text-zinc-950' : 'border border-zinc-200 text-zinc-700 hover:bg-zinc-50 dark:border-white/10 dark:text-zinc-200 dark:hover:bg-white/10' }}" href="{{ route($profileRoute, $profileRouteParams + ['view' => 'tickets']) }}"><x-icon name="ticket" />Tickets / ตั๋ว</a>
    </div>

    @if($activeView === 'orders')
        <div class="mt-6 grid gap-6">
            @forelse($orderEvents as $event)
                <section class="overflow-hidden rounded-lg border border-zinc-200 bg-white dark:border-white/10 dark:bg-white/[0.04]">
                    <div class="grid gap-4 border-b border-zinc-200 p-4 dark:border-white/10 sm:grid-cols-[96px_1fr]">
                        <div class="aspect-[4/5] overflow-hidden rounded-md bg-zinc-100 dark:bg-zinc-900">
                            @if($event->poster_path)
                                <img class="h-full w-full object-cover" src="{{ asset('uploads/'.$event->poster_path) }}" alt="{{ $event->name }}">
                            @endif
                        </div>
                        <div class="self-center">
                            <h2 class="text-xl font-semibold text-zinc-950 dark:text-white">{{ $event->name }}</h2>
                            <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">{{ $event->starts_at->format('M j, Y H:i') }} · {{ $event->venue }}</p>
                        </div>
                    </div>
                    <div class="grid gap-4 p-4">
                        @foreach($orders->filter(fn ($order) => $order->items->contains('event_id', $event->id)) as $order)
                            <div class="interactive-card group rounded-lg border border-zinc-200 bg-zinc-50 p-5 dark:border-white/10 dark:bg-zinc-900">
                                <a class="click-area-link" href="{{ route('orders.show', $order) }}" aria-label="View order {{ $order->order_number }}"></a>
                                <div class="flex flex-wrap items-center justify-between gap-3">
                                    <div class="click-area-content">
                                        <h3 class="font-semibold text-zinc-950 group-hover:text-emerald-700 dark:text-white">{{ $order->order_number }}</h3>
                                        <p class="text-sm text-zinc-600 dark:text-zinc-400">{{ $order->created_at->format('M j, Y H:i') }} · THB {{ number_format($order->total_thb) }}</p>
                                    </div>
                                    <span class="click-area-content rounded bg-zinc-100 px-3 py-1 text-sm text-emerald-700 dark:bg-white/10 dark:text-emerald-200">{{ str_replace('_', ' ', $order->status) }}</span>
                                </div>
                                <div class="click-area-content mt-4 flex flex-wrap gap-2">
                                    <a class="interactive-action inline-flex items-center gap-2 rounded-md border border-zinc-200 px-3 py-2 text-sm text-zinc-700 dark:border-white/10 dark:text-zinc-200" href="{{ route('orders.show', $order) }}"><x-icon name="receipt" />View order / ดูออเดอร์</a>
                                    @foreach($order->tickets->where('event_id', $event->id) as $ticket)
                                        <a class="interactive-action inline-flex items-center gap-2 rounded-md border border-zinc-200 px-3 py-2 text-sm text-zinc-700 dark:border-white/10 dark:text-zinc-200" href="{{ route('tickets.show', $ticket->uuid) }}"><x-icon name="ticket" />{{ $ticket->ticketType->name }} · {{ $ticket->holder_name }}</a>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                </section>
            @empty
                <div class="rounded-lg border border-zinc-200 bg-white p-8 text-zinc-700 dark:border-white/10 dark:bg-white/[0.04] dark:text-zinc-300">No orders yet. / ยังไม่มีออเดอร์</div>
            @endforelse
        </div>
    @else
        <div class="mt-6 grid gap-6">
            @forelse($ticketEvents as $event)
                <section class="overflow-hidden rounded-lg border border-zinc-200 bg-white dark:border-white/10 dark:bg-white/[0.04]">
                    <div class="grid gap-4 border-b border-zinc-200 p-4 dark:border-white/10 sm:grid-cols-[96px_1fr]">
                        <div class="aspect-[4/5] overflow-hidden rounded-md bg-zinc-100 dark:bg-zinc-900">
                            @if($event->poster_path)
                                <img class="h-full w-full object-cover" src="{{ asset('uploads/'.$event->poster_path) }}" alt="{{ $event->name }}">
                            @endif
                        </div>
                        <div class="self-center">
                            <h2 class="text-xl font-semibold text-zinc-950 dark:text-white">{{ $event->name }}</h2>
                            <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">{{ $event->starts_at->format('M j, Y H:i') }} · {{ $event->venue }}</p>
                        </div>
                    </div>
                    <div class="grid gap-3 p-4 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach($tickets->where('event_id', $event->id) as $ticket)
                            <div class="interactive-card group rounded-lg border border-zinc-200 bg-zinc-50 p-4 dark:border-white/10 dark:bg-zinc-900" x-data="{ editHolder: false }">
                                <a class="click-area-link" href="{{ route('tickets.show', $ticket->uuid) }}" aria-label="Open ticket {{ $ticket->ticketType->name }}"></a>
                                <div class="click-area-content">
                                    <div class="font-semibold text-zinc-950 group-hover:text-emerald-700 dark:text-white">{{ $ticket->ticketType->name }}</div>
                                    <div class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">{{ $ticket->holder_name }} · {{ str_replace('_', ' ', $ticket->status) }}</div>
                                </div>
                                @if($ticket->order)
                                    <a class="click-area-content interactive-action mt-3 inline-flex items-center gap-2 rounded-md border border-zinc-200 px-3 py-2 text-xs font-semibold text-zinc-700 dark:border-white/10 dark:text-zinc-200" href="{{ route('orders.show', $ticket->order) }}"><x-icon name="receipt" class="h-3.5 w-3.5" />{{ $ticket->order->order_number }} · View order / ดูออเดอร์</a>
                                @endif
                                @if($ticket->order?->status === 'approved')
                                    <div class="click-area-content mt-3 border-t border-zinc-200 pt-3 dark:border-white/10">
                                        <button class="interactive-action inline-flex items-center gap-2 rounded-md border border-zinc-200 px-3 py-2 text-xs font-semibold text-zinc-800 dark:border-white/10 dark:text-zinc-100" type="button" @click="editHolder = !editHolder">
                                            <x-icon name="edit" class="h-3.5 w-3.5" />
                                            <span x-text="TicketFlowLanguage.format(editHolder ? { en: 'Cancel', th: 'ยกเลิก' } : { en: 'Edit holder name', th: 'แก้ไขชื่อผู้ถือบัตร' })"></span>
                                        </button>
                                        <form class="mt-3 grid gap-3 sm:grid-cols-[1fr_auto]" method="POST" action="{{ route('orders.tickets.holder', ['order' => $ticket->order, 'ticket' => $ticket]) }}" x-cloak x-show="editHolder" x-transition>
                                            @csrf
                                            @method('PATCH')
                                            <label class="text-sm text-zinc-700 dark:text-zinc-300"><x-t en="Holder name" th="ชื่อผู้ถือบัตร" />
                                                <input class="mt-1 w-full rounded-md border border-zinc-200 bg-white px-3 py-2 text-zinc-950 dark:border-white/10 dark:bg-zinc-950 dark:text-white" name="holder_name" value="{{ old('holder_name', $ticket->holder_name) }}" required>
                                            </label>
                                            <button class="self-end inline-flex items-center justify-center gap-2 rounded-md bg-emerald-400 px-3 py-2 text-sm font-semibold text-zinc-950"><x-icon name="save" /><x-t en="Save" th="บันทึก" /></button>
                                        </form>
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </section>
            @empty
                <div class="rounded-lg border border-zinc-200 bg-white p-8 text-zinc-700 dark:border-white/10 dark:bg-white/[0.04] dark:text-zinc-300">No tickets yet. / ยังไม่มีตั๋ว</div>
            @endforelse
        </div>
    @endif
</x-layouts.app>
