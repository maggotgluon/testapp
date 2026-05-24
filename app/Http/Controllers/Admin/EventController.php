<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\EventAttendeeAnnouncement;
use App\Models\Event;
use App\Models\Ticket;
use App\Models\TicketOrder;
use App\Models\User;
use App\Services\CustomerNotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class EventController extends Controller
{
    public function index(): View
    {
        $events = Event::with('ticketTypes')->latest('starts_at');

        if (auth()->user()->role !== 'super_admin') {
            $events->whereHas('assignedUsers', fn ($query) => $query->whereKey(auth()->id()));
        }

        return view('admin.events.index', [
            'events' => $events->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.events.form', ['event' => new Event, 'ticketTypes' => collect()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $event = Event::create($this->validated($request) + ['created_by' => $request->user()->id]);
        $event->assignedUsers()->syncWithoutDetaching([$request->user()->id]);
        $this->syncTicketTypes($request, $event);

        return redirect()->route('admin.events.index')->with('status', 'Event created.');
    }

    public function edit(Event $event): View
    {
        abort_unless(request()->user()->canManageEvent($event), 403);

        return view('admin.events.form', [
            'event' => $event,
            'ticketTypes' => $event->ticketTypes()->where('status', '!=', 'inactive')->get(),
        ]);
    }

    public function overview(Event $event, Request $request): View
    {
        abort_unless($request->user()->canManageEvent($event), 403);

        $event->load('ticketTypes');

        $orders = TicketOrder::query()
            ->with(['items' => fn ($query) => $query->where('event_id', $event->id)->with('ticketType'), 'tickets' => fn ($query) => $query->where('event_id', $event->id)->with('ticketType')])
            ->whereHas('items', fn ($query) => $query->where('event_id', $event->id))
            ->when($request->filled('order_status'), fn ($query) => $query->where('status', $request->string('order_status')))
            ->latest()
            ->paginate(12, ['*'], 'orders_page');

        $tickets = Ticket::query()
            ->with(['ticketType', 'order'])
            ->where('event_id', $event->id)
            ->when($request->filled('ticket_status'), fn ($query) => $query->where('status', $request->string('ticket_status')))
            ->latest()
            ->paginate(20, ['*'], 'tickets_page');

        $orderStatusCounts = TicketOrder::query()
            ->whereHas('items', fn ($query) => $query->where('event_id', $event->id))
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $ticketStatusCounts = Ticket::query()
            ->where('event_id', $event->id)
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $ticketTypeStats = $event->ticketTypes->map(function ($ticketType) use ($event) {
            $quantity = \App\Models\OrderItem::where('event_id', $event->id)
                ->where('ticket_type_id', $ticketType->id)
                ->whereHas('order', fn ($query) => $query->where('status', 'approved'))
                ->sum('quantity');

            return [
                'name' => $ticketType->name,
                'quantity' => $quantity,
                'capacity' => $ticketType->capacity,
                'revenue' => $quantity * $ticketType->price_thb,
            ];
        });

        return view('admin.events.overview', [
            'event' => $event,
            'orders' => $orders,
            'tickets' => $tickets,
            'orderStatusCounts' => $orderStatusCounts,
            'ticketStatusCounts' => $ticketStatusCounts,
            'ticketTypeStats' => $ticketTypeStats,
            'grossRevenue' => $ticketTypeStats->sum('revenue'),
            'totalTickets' => Ticket::where('event_id', $event->id)->count(),
            'checkedInTickets' => Ticket::where('event_id', $event->id)->whereIn('status', ['checked_in', 'checked_out'])->count(),
            'emailRecipientCount' => $this->attendeeEmails($event, 'approved')->count(),
            'messageRecipientCount' => $this->attendeeUsers($event, 'approved')->count(),
        ]);
    }

    public function emailAttendees(Request $request, Event $event): RedirectResponse
    {
        abort_unless($request->user()->canManageEvent($event), 403);

        $data = $request->validate([
            'subject' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string', 'max:5000'],
            'audience' => ['required', 'in:approved,all'],
        ]);

        $emails = $this->attendeeEmails($event, $data['audience']);

        if ($emails->isEmpty()) {
            return back()->withErrors(['email' => 'No attendee emails found for this event. / ไม่พบอีเมลผู้เข้าร่วมสำหรับอีเวนต์นี้']);
        }

        foreach ($emails as $email) {
            Mail::to($email)->send(new EventAttendeeAnnouncement($event, $data['subject'], $data['message']));
        }

        return back()->with('status', 'Email sent to '.$emails->count().' attendees. / ส่งอีเมลถึงผู้เข้าร่วม '.$emails->count().' คนแล้ว');
    }

    public function messageAttendees(Request $request, Event $event, CustomerNotificationService $notifications): RedirectResponse
    {
        abort_unless($request->user()->canManageEvent($event), 403);

        $availableChannels = $notifications->availableChannels();

        if ($availableChannels === []) {
            return back()->withErrors(['channels' => 'Notification channels are not configured yet. / ยังไม่ได้ตั้งค่าช่องทางแจ้งเตือน']);
        }

        $data = $request->validate([
            'subject' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string', 'max:1000'],
            'audience' => ['required', 'in:approved,all'],
            'channels' => ['required', 'array', 'min:1'],
            'channels.*' => ['required', 'in:line,web_push'],
        ]);

        $data['channels'] = array_values(array_intersect($data['channels'], $availableChannels));

        if ($data['channels'] === []) {
            return back()->withErrors(['channels' => 'Selected notification channel is not configured. / ช่องทางแจ้งเตือนที่เลือกยังไม่ได้ตั้งค่า']);
        }

        $users = $this->attendeeUsers($event, $data['audience']);

        if ($users->isEmpty()) {
            return back()->withErrors(['message' => 'No logged-in attendees found for this event. / ไม่พบผู้เข้าร่วมที่ล็อกอินสำหรับอีเวนต์นี้']);
        }

        $counts = $notifications->eventMessage($event, $users, $data['subject'], $data['message'], $data['channels']);

        return back()->with('status', 'Message sent. LINE: '.$counts['line'].' Web Push: '.$counts['web_push'].' / ส่งข้อความแล้ว LINE: '.$counts['line'].' Web Push: '.$counts['web_push']);
    }

    public function update(Request $request, Event $event): RedirectResponse
    {
        abort_unless($request->user()->canManageEvent($event), 403);

        $event->update($this->validated($request));
        $this->syncTicketTypes($request, $event);

        return redirect()->route('admin.events.index')->with('status', 'Event updated.');
    }

    public function updateTicketStatus(Request $request, Event $event, Ticket $ticket): RedirectResponse
    {
        abort_unless($request->user()->canManageEvent($event), 403);
        abort_unless($ticket->event_id === $event->id, 404);

        $data = $request->validate([
            'status' => ['required', 'in:pending,approved,checked_in,checked_out,rejected,refunded,expired'],
        ]);

        $updates = ['status' => $data['status']];

        if ($data['status'] === 'checked_in') {
            $updates['checked_in_at'] = $ticket->checked_in_at ?? now();
        }

        if ($data['status'] === 'checked_out') {
            $updates['checked_in_at'] = $ticket->checked_in_at ?? now();
            $updates['checked_out_at'] = $ticket->checked_out_at ?? now();
        }

        if (in_array($data['status'], ['pending', 'approved', 'rejected', 'refunded', 'expired'], true)) {
            $updates['checked_in_at'] = null;
            $updates['checked_out_at'] = null;
        }

        $ticket->update($updates);

        if (in_array($data['status'], ['checked_in', 'checked_out'], true)) {
            $ticket->logs()->create([
                'scanned_by' => $request->user()->id,
                'action' => $data['status'] === 'checked_in' ? 'manual_check_in' : 'manual_check_out',
                'note' => 'Updated from event overview.',
            ]);
        }

        return back()->with('status', 'Ticket status updated.');
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'social_description' => ['nullable', 'string', 'max:500'],
            'venue' => ['required', 'string', 'max:255'],
            'location' => ['nullable', 'string', 'max:255'],
            'location_url' => ['nullable', 'url', 'max:500'],
            'hosted_by' => ['nullable', 'string', 'max:255'],
            'hosted_by_url' => ['nullable', 'url', 'max:500'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date', 'after:starts_at'],
            'poster' => ['nullable', 'image', 'max:4096'],
            'ticket_image' => ['nullable', 'image', 'max:4096'],
            'social_image' => ['nullable', 'image', 'max:4096'],
            'qr_payment_image' => ['nullable', 'image', 'max:4096'],
            'bank_name' => ['nullable', 'string', 'max:255'],
            'bank_account_name' => ['nullable', 'string', 'max:255'],
            'bank_account_number' => ['nullable', 'string', 'max:255'],
            'qr_payment_account_name' => ['nullable', 'string', 'max:255'],
            'qr_payment_account' => ['nullable', 'string', 'max:255'],
            'payment_instructions' => ['nullable', 'string'],
            'is_published' => ['nullable', 'boolean'],
            'show_countdown' => ['nullable', 'boolean'],
        ]);

        if ($request->hasFile('poster')) {
            $data['poster_path'] = $request->file('poster')->store('event-posters', 'uploads');
        }

        if ($request->hasFile('ticket_image')) {
            $data['ticket_image_path'] = $request->file('ticket_image')->store('ticket-art', 'uploads');
        }

        if ($request->hasFile('social_image')) {
            $data['social_image_path'] = $request->file('social_image')->store('social-share', 'uploads');
        }

        if ($request->hasFile('qr_payment_image')) {
            $data['qr_payment_image_path'] = $request->file('qr_payment_image')->store('payment-qr', 'uploads');
        }

        $data['is_published'] = $request->boolean('is_published');
        $data['show_countdown'] = $request->boolean('show_countdown');
        $data['description'] = $this->safeHtml($data['description'] ?? null);

        return $data;
    }

    public function destroy(Request $request, Event $event): RedirectResponse
    {
        abort_unless($request->user()->role === 'super_admin', 403);

        DB::transaction(function () use ($event) {
            TicketOrder::whereHas('items', fn ($query) => $query->where('event_id', $event->id))->delete();
            $event->delete();
        });

        return redirect()->route('admin.events.index')->with('status', 'Event deleted.');
    }

    public function destroyTicket(Request $request, Event $event, Ticket $ticket): RedirectResponse
    {
        abort_unless($request->user()->canManageEvent($event), 403);
        abort_unless($ticket->event_id === $event->id, 404);

        $ticket->delete();

        return back()->with('status', 'Ticket deleted.');
    }

    private function safeHtml(?string $html): ?string
    {
        if ($html === null) {
            return null;
        }

        $html = preg_replace('/<\s*(script|style|iframe|object|embed)[^>]*>.*?<\s*\/\s*\1\s*>/is', '', $html);
        $html = preg_replace('/\s+on[a-z]+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/is', '', $html);
        $html = preg_replace('/\s+(href|src)\s*=\s*("[^"]*javascript:[^"]*"|\'[^\']*javascript:[^\']*\'|[^\s>]*javascript:[^\s>]*)/is', '', $html);

        return strip_tags($html, '<p><br><strong><b><em><i><u><ul><ol><li><a><h2><h3><blockquote>');
    }

    private function syncTicketTypes(Request $request, Event $event): void
    {
        $inactiveIds = collect($request->input('inactive_ticket_type_ids', []))
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        if ($inactiveIds->isNotEmpty()) {
            $event->ticketTypes()
                ->whereIn('id', $inactiveIds)
                ->update(['status' => 'inactive']);
        }

        foreach ($request->input('tickets', []) as $ticket) {
            if (empty($ticket['name'])) {
                continue;
            }

            $event->ticketTypes()->updateOrCreate(
                ['id' => $ticket['id'] ?? null],
                [
                    'name' => $ticket['name'],
                    'description' => $ticket['description'] ?? null,
                    'price_thb' => (int) ($ticket['price_thb'] ?? 0),
                    'full_price_thb' => filled($ticket['full_price_thb'] ?? null) ? (int) $ticket['full_price_thb'] : null,
                    'capacity' => (int) ($ticket['capacity'] ?? 0),
                    'sale_starts_at' => $ticket['sale_starts_at'] ?? null,
                    'sale_ends_at' => $ticket['sale_ends_at'] ?? null,
                    'status' => $ticket['status'] ?? 'active',
                ]
            );
        }
    }

    private function attendeeEmails(Event $event, string $audience)
    {
        return TicketOrder::query()
            ->whereHas('items', fn ($query) => $query->where('event_id', $event->id))
            ->when($audience === 'approved', fn ($query) => $query->where('status', 'approved'))
            ->whereNotNull('customer_email')
            ->where('customer_email', '!=', '')
            ->pluck('customer_email')
            ->map(fn ($email) => strtolower(trim($email)))
            ->filter(fn ($email) => filter_var($email, FILTER_VALIDATE_EMAIL))
            ->unique()
            ->values();
    }

    private function attendeeUsers(Event $event, string $audience)
    {
        $userIds = TicketOrder::query()
            ->whereHas('items', fn ($query) => $query->where('event_id', $event->id))
            ->when($audience === 'approved', fn ($query) => $query->where('status', 'approved'))
            ->whereNotNull('user_id')
            ->pluck('user_id')
            ->unique()
            ->values();

        return User::query()
            ->with('pushSubscriptions')
            ->whereIn('id', $userIds)
            ->get();
    }
}
