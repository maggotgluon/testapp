<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CheckInLog;
use App\Models\Event;
use App\Models\Ticket;
use App\Services\CrmSyncService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ScannerController extends Controller
{
    public function index(Request $request): View
    {
        $user = request()->user();
        $events = Event::query()
            ->where('ends_at', '>=', now()->subDay())
            ->orderBy('starts_at')
            ->get()
            ->filter(fn (Event $event) => $user->canManageEvent($event))
            ->values();

        $manageableEventIds = $events->pluck('id');
        $perPage = (int) $request->integer('per_page', 20);
        $perPage = in_array($perPage, [10, 20, 50, 100], true) ? $perPage : 20;

        $recentScanLogs = CheckInLog::query()
            ->with(['ticket.event', 'ticket.ticketType', 'ticket.order'])
            ->where('scanned_by', $user->id)
            ->whereHas('ticket', fn ($query) => $query->whereIn('event_id', $manageableEventIds))
            ->when($request->filled('event_id'), fn ($query) => $query->whereHas('ticket', fn ($ticketQuery) => $ticketQuery->where('event_id', $request->integer('event_id'))))
            ->when($request->filled('action'), fn ($query) => $query->where('action', $request->input('action')))
            ->when($request->filled('ticket_status'), fn ($query) => $query->whereHas('ticket', fn ($ticketQuery) => $ticketQuery->where('status', $request->input('ticket_status'))))
            ->latest()
            ->paginate($perPage)
            ->withQueryString();

        $recentScans = $recentScanLogs->getCollection()->map(fn (CheckInLog $log) => [
                'ok' => true,
                'message' => str_replace('_', ' ', $log->action),
                'action' => $log->action,
                'scanned_at' => $log->created_at?->format('H:i:s'),
                'ticket' => $this->ticketPayload($log->ticket),
            ]);

        return view('admin.scanner', [
            'events' => $events,
            'perPage' => $perPage,
            'recentScanLogs' => $recentScanLogs,
            'recentScans' => $recentScans,
        ]);
    }

    public function scan(Request $request, CrmSyncService $crm): JsonResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string'],
            'action' => ['nullable', 'in:check_in,check_out'],
            'event_id' => ['nullable', 'integer', 'exists:events,id'],
            'gate' => ['nullable', 'string', 'max:120'],
        ]);

        $uuid = basename(parse_url($data['code'], PHP_URL_PATH) ?: $data['code']);
        $ticket = Ticket::with(['event', 'ticketType', 'order'])->where('uuid', $uuid)->first();

        if (! $ticket) {
            return response()->json(['ok' => false, 'message' => 'Ticket not found. / ไม่พบตั๋ว'], 404);
        }

        if (! $request->user()->canManageEvent($ticket->event)) {
            return response()->json(['ok' => false, 'message' => 'You are not assigned to this event. / คุณไม่ได้รับสิทธิ์สำหรับอีเวนต์นี้'], 403);
        }

        if (($data['event_id'] ?? null) && (int) $data['event_id'] !== (int) $ticket->event_id) {
            return response()->json([
                'ok' => false,
                'message' => 'Ticket belongs to another event. / ตั๋วนี้เป็นของอีเวนต์อื่น',
                'ticket' => $this->ticketPayload($ticket),
            ], 422);
        }

        if (! ($data['action'] ?? null)) {
            return response()->json([
                'ok' => true,
                'message' => 'Ticket found. / พบตั๋วแล้ว',
                'ticket' => $this->ticketPayload($ticket),
            ]);
        }

        $result = DB::transaction(function () use ($uuid, $data, $request) {
            $ticket = Ticket::query()
                ->with(['event', 'ticketType', 'order'])
                ->where('uuid', $uuid)
                ->lockForUpdate()
                ->firstOrFail();

            $expectedStatus = $data['action'] === 'check_in' ? 'approved' : 'checked_in';

            if ($ticket->status !== $expectedStatus) {
                return [
                    'ok' => false,
                    'status' => 422,
                    'message' => $data['action'] === 'check_in'
                        ? 'Ticket must be approved before check-in. Current status: '.$ticket->status.'. / ตั๋วต้องอนุมัติก่อนเช็กอิน สถานะปัจจุบัน: '.$ticket->status
                        : 'Ticket must be checked in before check-out. Current status: '.$ticket->status.'. / ตั๋วต้องเช็กอินก่อนเช็กเอาต์ สถานะปัจจุบัน: '.$ticket->status,
                    'ticket' => $ticket,
                ];
            }

            if ($data['action'] === 'check_in') {
                $ticket->update(['status' => 'checked_in', 'checked_in_at' => now()]);
            } else {
                $ticket->update(['status' => 'checked_out', 'checked_out_at' => now()]);
            }

            $ticket->logs()->create([
                'scanned_by' => $request->user()->id,
                'action' => $data['action'],
                'gate' => $data['gate'] ?? null,
            ]);

            return [
                'ok' => true,
                'status' => 200,
                'message' => str_replace('_', ' ', $data['action']).' complete. / ทำรายการสำเร็จ',
                'ticket' => $ticket->fresh(['event', 'ticketType', 'order.user']),
            ];
        });

        if (! $result['ok']) {
            return response()->json([
                'ok' => false,
                'message' => $result['message'],
                'ticket' => $this->ticketPayload($result['ticket']),
            ], $result['status']);
        }

        $crm->pushTicketActivity($result['ticket'], $data['action'] === 'check_in' ? 'ticket_checked_in' : 'ticket_checked_out');

        return response()->json([
            'ok' => true,
            'message' => $result['message'],
            'action' => $data['action'],
            'scanned_at' => now()->format('H:i:s'),
            'ticket' => $this->ticketPayload($result['ticket']),
        ]);
    }

    private function ticketPayload(Ticket $ticket): array
    {
        return [
            'uuid' => $ticket->uuid,
            'holder' => $ticket->holder_name,
            'phone' => $ticket->holder_phone,
            'event_id' => $ticket->event_id,
            'event' => $ticket->event?->name,
            'type' => $ticket->ticketType?->name,
            'order_number' => $ticket->order?->order_number,
            'status' => $ticket->status,
            'checked_in_at' => $ticket->checked_in_at?->format('M j, H:i'),
            'checked_out_at' => $ticket->checked_out_at?->format('M j, H:i'),
            'url' => route('tickets.show', $ticket->uuid),
        ];
    }
}
