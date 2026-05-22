<?php

namespace App\Services;

use App\Models\Event;
use App\Models\Ticket;
use chillerlan\QRCode\Output\QRMarkupSVG;
use chillerlan\QRCode\QRCode;

class QrCodeService
{
    private const ID_PAYLOAD_FORMAT = '00';
    private const ID_POI_METHOD = '01';
    private const ID_MERCHANT_INFORMATION_BOT = '29';
    private const ID_TRANSACTION_CURRENCY = '53';
    private const ID_TRANSACTION_AMOUNT = '54';
    private const ID_COUNTRY_CODE = '58';
    private const ID_CRC = '63';

    private const PAYLOAD_FORMAT_EMV_QRCPS_MERCHANT_PRESENTED_MODE = '01';
    private const POI_METHOD_STATIC = '11';
    private const POI_METHOD_DYNAMIC = '12';
    private const MERCHANT_INFORMATION_TEMPLATE_ID_GUID = '00';
    private const BOT_ID_MERCHANT_PHONE_NUMBER = '01';
    private const BOT_ID_MERCHANT_TAX_ID = '02';
    private const BOT_ID_MERCHANT_EWALLET_ID = '03';
    private const GUID_PROMPTPAY = 'A000000677010111';
    private const TRANSACTION_CURRENCY_THB = '764';
    private const COUNTRY_CODE_TH = 'TH';

    public function svg(string $payload): string
    {
        return (new QRCode([
            'outputInterface' => QRMarkupSVG::class,
            'outputBase64' => false,
            'scale' => 8,
            'quietzoneSize' => 2,
        ]))->render($payload);
    }

    public function ticketPayload(Ticket $ticket): string
    {
        return $ticket->uuid;
    }

    public function paymentPayload(Event $event, int $amountThb): string
    {
        return $this->promptPayPayload((string) $event->qr_payment_account, $amountThb > 0 ? $amountThb : null);
    }

    public function promptPayPayload(string $target, ?int $amountThb = null): string
    {
        $target = $this->sanitizeTarget($target);
        $targetType = match (true) {
            strlen($target) >= 15 => self::BOT_ID_MERCHANT_EWALLET_ID,
            strlen($target) >= 13 => self::BOT_ID_MERCHANT_TAX_ID,
            default => self::BOT_ID_MERCHANT_PHONE_NUMBER,
        };

        $data = [
            $this->field(self::ID_PAYLOAD_FORMAT, self::PAYLOAD_FORMAT_EMV_QRCPS_MERCHANT_PRESENTED_MODE),
            $this->field(self::ID_POI_METHOD, $amountThb ? self::POI_METHOD_DYNAMIC : self::POI_METHOD_STATIC),
            $this->field(self::ID_MERCHANT_INFORMATION_BOT, $this->serialize([
                $this->field(self::MERCHANT_INFORMATION_TEMPLATE_ID_GUID, self::GUID_PROMPTPAY),
                $this->field($targetType, $this->formatTarget($target)),
            ])),
            $this->field(self::ID_COUNTRY_CODE, self::COUNTRY_CODE_TH),
            $this->field(self::ID_TRANSACTION_CURRENCY, self::TRANSACTION_CURRENCY_THB),
            $amountThb ? $this->field(self::ID_TRANSACTION_AMOUNT, number_format($amountThb, 2, '.', '')) : null,
        ];

        $dataToCrc = $this->serialize($data).self::ID_CRC.'04';
        $data[] = $this->field(self::ID_CRC, $this->formatCrc($this->crc16Xmodem($dataToCrc)));

        return $this->serialize($data);
    }

    private function field(string $id, string $value): string
    {
        return $id.str_pad((string) strlen($value), 2, '0', STR_PAD_LEFT).$value;
    }

    private function serialize(array $fields): string
    {
        return implode('', array_filter($fields));
    }

    private function sanitizeTarget(string $target): string
    {
        return preg_replace('/[^0-9]/', '', $target) ?? '';
    }

    private function formatTarget(string $target): string
    {
        $numbers = $this->sanitizeTarget($target);

        if (strlen($numbers) >= 13) {
            return $numbers;
        }

        return substr(str_repeat('0', 13).preg_replace('/^0/', '66', $numbers), -13);
    }

    private function crc16Xmodem(string $payload): int
    {
        $crc = 0xffff;

        foreach (unpack('C*', $payload) as $byte) {
            $crc ^= ($byte << 8);

            for ($i = 0; $i < 8; $i++) {
                $crc = ($crc & 0x8000) ? (($crc << 1) ^ 0x1021) : ($crc << 1);
                $crc &= 0xffff;
            }
        }

        return $crc;
    }

    private function formatCrc(int $crc): string
    {
        return str_pad(strtoupper(dechex($crc)), 4, '0', STR_PAD_LEFT);
    }
}
