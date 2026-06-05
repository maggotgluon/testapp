<?php

namespace App\Services;

use App\Models\Event;
use App\Models\Payment;
use App\Models\TicketOrder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PaymentSlipStorageService
{
    private const ACTIVE_DIRECTORY = 'payment-slips';
    private const ARCHIVE_DIRECTORY = 'payment-slips-archive';
    private const MAX_DIMENSION = 1600;
    private const JPEG_QUALITY = 82;

    public function store(UploadedFile $file): string
    {
        return $this->storeCompressed($file) ?? $file->store(self::ACTIVE_DIRECTORY, 'uploads');
    }

    public function replaceForPayment(TicketOrder $order, Payment $payment, UploadedFile $file): string
    {
        $oldPath = $payment->slip_path ?: $order->payment_slip_path;
        $path = $this->store($file);

        if ($oldPath && $oldPath !== $path) {
            Storage::disk('uploads')->delete($oldPath);
        }

        $order->update(['payment_slip_path' => $path]);
        $payment->update([
            'slip_path' => $path,
            'slip_archived_path' => null,
            'slip_archived_at' => null,
            'slip_deleted_at' => null,
        ]);

        return $path;
    }

    public function deleteActiveSlipForOrder(TicketOrder $order): int
    {
        $order->loadMissing('payments');
        $paths = collect([$order->payment_slip_path])
            ->merge($order->payments->pluck('slip_path'))
            ->filter()
            ->unique()
            ->values();

        foreach ($paths as $path) {
            Storage::disk('uploads')->delete($path);
        }

        $order->payments()
            ->whereNotNull('slip_path')
            ->update([
                'slip_path' => null,
                'slip_deleted_at' => now(),
            ]);

        if ($order->payment_slip_path) {
            $order->update(['payment_slip_path' => null]);
        }

        return $paths->count();
    }

    public function deleteActiveSlipForPayment(Payment $payment): bool
    {
        if (! $payment->slip_path) {
            return false;
        }

        Storage::disk('uploads')->delete($payment->slip_path);
        $payment->update([
            'slip_path' => null,
            'slip_deleted_at' => now(),
        ]);

        $payment->order?->update(['payment_slip_path' => null]);

        return true;
    }

    public function archiveApprovedSlipsForEndedEvent(Event $event): array
    {
        $stats = [
            'archived' => 0,
            'missing' => 0,
            'already_archived' => 0,
        ];

        if ($event->ends_at->isFuture()) {
            return $stats;
        }

        $orders = TicketOrder::query()
            ->with('payments')
            ->where('status', 'approved')
            ->whereHas('items', fn ($query) => $query->where('event_id', $event->id))
            ->get();

        foreach ($orders as $order) {
            foreach ($order->payments as $payment) {
                if ($payment->slip_archived_path) {
                    $stats['already_archived']++;
                    continue;
                }

                if (! $payment->slip_path) {
                    $stats['missing']++;
                    continue;
                }

                if (! Storage::disk('uploads')->exists($payment->slip_path)) {
                    $stats['missing']++;
                    $payment->update([
                        'slip_path' => null,
                        'slip_deleted_at' => $payment->slip_deleted_at ?? now(),
                    ]);
                    continue;
                }

                $archivePath = self::ARCHIVE_DIRECTORY.'/'.$event->id.'/'.$payment->id.'-'.basename($payment->slip_path);
                Storage::disk('local')->put($archivePath, Storage::disk('uploads')->get($payment->slip_path));
                Storage::disk('uploads')->delete($payment->slip_path);

                $payment->update([
                    'slip_archived_path' => $archivePath,
                    'slip_archived_at' => now(),
                    'slip_path' => null,
                ]);

                $stats['archived']++;
            }

            if ($order->payment_slip_path) {
                $order->update(['payment_slip_path' => null]);
            }
        }

        return $stats;
    }

    private function storeCompressed(UploadedFile $file): ?string
    {
        if (! function_exists('imagecreatetruecolor')) {
            return null;
        }

        $source = $this->sourceImage($file);

        if (! $source) {
            return null;
        }

        [$width, $height] = getimagesize($file->getRealPath()) ?: [0, 0];

        if ($width <= 0 || $height <= 0) {
            imagedestroy($source);

            return null;
        }

        $scale = min(1, self::MAX_DIMENSION / max($width, $height));
        $targetWidth = max(1, (int) round($width * $scale));
        $targetHeight = max(1, (int) round($height * $scale));
        $target = imagecreatetruecolor($targetWidth, $targetHeight);
        imagefill($target, 0, 0, imagecolorallocate($target, 255, 255, 255));
        imagecopyresampled($target, $source, 0, 0, 0, 0, $targetWidth, $targetHeight, $width, $height);

        $path = self::ACTIVE_DIRECTORY.'/'.Str::uuid().'.jpg';
        $temporaryPath = tempnam(sys_get_temp_dir(), 'payment-slip-');

        if (! $temporaryPath || ! imagejpeg($target, $temporaryPath, self::JPEG_QUALITY)) {
            imagedestroy($source);
            imagedestroy($target);

            return null;
        }

        Storage::disk('uploads')->put($path, file_get_contents($temporaryPath));
        @unlink($temporaryPath);
        imagedestroy($source);
        imagedestroy($target);

        return $path;
    }

    private function sourceImage(UploadedFile $file)
    {
        $path = $file->getRealPath();
        $type = @exif_imagetype($path);

        return match ($type) {
            IMAGETYPE_JPEG => function_exists('imagecreatefromjpeg') ? @imagecreatefromjpeg($path) : null,
            IMAGETYPE_PNG => function_exists('imagecreatefrompng') ? @imagecreatefrompng($path) : null,
            IMAGETYPE_WEBP => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($path) : null,
            default => null,
        };
    }
}
