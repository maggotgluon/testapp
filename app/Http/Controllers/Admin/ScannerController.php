<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ScannerController extends Controller
{
    public function index(): View
    {
        return view('admin.scanner');
    }

    public function scan(Request $request): JsonResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string'],
            'action' => ['required', 'in:check_in,check_out'],
            'gate' => ['nullable', 'string', 'max:120'],
        ]);

        $uuid = basename(parse_url($data['code'], PHP_URL_PATH) ?: $data['code']);
        $ticket = Ticket::with(['event', 'ticketType'])->where('uuid', $uuid)->first();

        if (! $ticket) {
            return response()->json(['ok' => false, 'message' => 'Ticket not found. / ไม่พบตั๋ว'], 404);
        }

        if (! in_array($ticket->status, ['approved', 'checked_in'], true)) {
            return response()->json(['ok' => false, 'message' => 'Ticket is '.$ticket->status.'. / สถานะตั๋วคือ '.$ticket->status], 422);
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

        return response()->json([
            'ok' => true,
            'message' => str_replace('_', ' ', $data['action']).' complete. / ทำรายการสำเร็จ',
            'ticket' => [
                'holder' => $ticket->holder_name,
                'event' => $ticket->event->name,
                'type' => $ticket->ticketType->name,
                'status' => $ticket->fresh()->status,
            ],
        ]);
    }
}
