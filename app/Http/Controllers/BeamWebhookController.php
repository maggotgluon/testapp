<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\TicketOrder;
use App\Services\BeamService;
use App\Services\CustomerNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BeamWebhookController extends Controller
{
    public function __invoke(Request $request, BeamService $beam, CustomerNotificationService $notifications)
    {
        $payload = $request->getContent();
        $signature = $request->header('X-Beam-Signature', '');

        if (! $beam->verifyWebhookSignature($payload, $signature)) {
            Log::warning('Beam webhook: invalid signature');

            return response()->json(['error' => 'Invalid signature'], 401);
        }

        $event = $request->input('event');

        if (! in_array($event, ['charge.succeeded', 'charge.failed'], true)) {
            return response()->json(['received' => true]);
        }

        $chargeId = $request->input('data.id');

        if (! $chargeId) {
            Log::warning('Beam webhook: missing charge ID', ['event' => $event]);

            return response()->json(['error' => 'Missing charge ID'], 422);
        }

        if ($event === 'charge.succeeded') {
            DB::transaction(function () use ($chargeId, $request, $notifications) {
                $payment = Payment::query()
                    ->where('beam_charge_id', $chargeId)
                    ->lockForUpdate()
                    ->first();

                if (! $payment) {
                    Log::warning('Beam webhook: payment not found', ['charge_id' => $chargeId]);

                    return;
                }

                if ($payment->status === 'paid') {
                    return;
                }

                $order = $payment->order;

                if (! $order) {
                    Log::warning('Beam webhook: order not found', ['payment_id' => $payment->id]);

                    return;
                }

                $payment->update([
                    'status' => 'paid',
                ]);

                $order->update([
                    'status' => 'approved',
                    'approved_at' => now(),
                ]);

                $order->tickets()->where('status', 'pending')->each(function ($ticket) {
                    $ticket->update(['status' => 'approved']);
                    if ($ticket->ticketType) {
                        $ticket->ticketType()->increment('sold_count');
                    }
                });

                $notifications->orderApproved($order);
            });

            return response()->json(['received' => true]);
        }

        if ($event === 'charge.failed') {
            $payment = Payment::query()->where('beam_charge_id', $chargeId)->first();

            if (! $payment) {
                Log::warning('Beam webhook: payment not found for failed charge', ['charge_id' => $chargeId]);

                return response()->json(['received' => true]);
            }

            $payment->update([
                'status' => 'failed',
                'note' => $payment->note."\nBeam charge failed: ".($request->input('data.failureMessage') ?? 'Unknown'),
            ]);

            return response()->json(['received' => true]);
        }

        return response()->json(['received' => true]);
    }
}
