<?php

namespace App\Services;

use App\Models\Payment;
use chillerlan\QRCode\QRCode;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Throwable;

class SlipQrDecoderService
{
    private const THAI_BANKS = [
        '002' => 'Bangkok Bank / ธนาคารกรุงเทพ',
        '004' => 'Kasikornbank / ธนาคารกสิกรไทย',
        '006' => 'Krungthai Bank / ธนาคารกรุงไทย',
        '011' => 'TTB Bank / ธนาคารทหารไทยธนชาต',
        '014' => 'Siam Commercial Bank / ธนาคารไทยพาณิชย์',
        '017' => 'Citibank / ธนาคารซิตี้แบงก์',
        '020' => 'Standard Chartered / ธนาคารสแตนดาร์ดชาร์เตอร์ด',
        '022' => 'CIMB Thai / ธนาคารซีไอเอ็มบีไทย',
        '024' => 'United Overseas Bank / ธนาคารยูโอบี',
        '025' => 'Bank of Ayudhya / ธนาคารกรุงศรีอยุธยา',
        '030' => 'Government Savings Bank / ธนาคารออมสิน',
        '033' => 'Government Housing Bank / ธนาคารอาคารสงเคราะห์',
        '034' => 'Bank for Agriculture and Agricultural Cooperatives / ธ.ก.ส.',
        '069' => 'Kiatnakin Phatra Bank / ธนาคารเกียรตินาคินภัทร',
        '073' => 'Land and Houses Bank / ธนาคารแลนด์ แอนด์ เฮ้าส์',
    ];

    public function decode(?string $slipPath): array
    {
        if (! $slipPath) {
            return [];
        }

        $absolutePath = Storage::disk('uploads')->path($slipPath);

        if (! is_file($absolutePath)) {
            return [
                'slip_qr_status' => 'decode_error',
                'slip_qr_data' => ['message' => 'Payment slip file was not found.'],
            ];
        }

        try {
            $payload = trim((string) (new QRCode())->readFromFile($absolutePath));
        } catch (Throwable $exception) {
            return [
                'slip_qr_status' => 'no_qr',
                'slip_qr_data' => ['message' => 'No readable QR code was found in the slip image.'],
            ];
        }

        if ($payload === '') {
            return [
                'slip_qr_status' => 'no_qr',
                'slip_qr_data' => ['message' => 'The QR code was empty.'],
            ];
        }

        return $this->parsePayloadForReview($payload);
    }

    public function parsePayloadForReview(string $payload): array
    {
        $payload = trim($payload);
        $data = [
            'format' => 'raw',
            'payload_length' => strlen($payload),
        ];

        $query = $this->queryParams($payload);
        $emv = $this->parseEmvPayload($payload);
        $slipVerify = $this->parseSlipVerifyPayload($payload);

        if ($query !== []) {
            $data['format'] = 'url';
            $data['query'] = $query;
        }

        if ($slipVerify !== []) {
            $data['format'] = 'slip_verify';
            $data['slip_verify'] = $slipVerify;
        } elseif ($emv !== []) {
            $data['format'] = 'emv';
            $data['emv'] = $emv;
        }

        if ($emv !== []) {
            $data['emv'] = $emv;
        }

        $amount = $this->firstAmount([
            $query['amount'] ?? null,
            $query['amt'] ?? null,
            $query['total'] ?? null,
            $emv['54'] ?? null,
        ]);

        $paidAt = $this->firstDate([
            $query['paid_at'] ?? null,
            $query['datetime'] ?? null,
            $query['timestamp'] ?? null,
            $query['date'] ?? null,
        ]);

        return [
            'slip_qr_status' => 'decoded',
            'slip_qr_payload' => $payload,
            'slip_qr_data' => $data,
            'slip_qr_amount_thb' => $amount,
            'slip_qr_paid_at' => $paidAt,
            'slip_qr_reference' => $this->firstFilled([
                $slipVerify['transaction_reference'] ?? null,
                $query['ref'] ?? null,
                $query['reference'] ?? null,
                $query['transactionId'] ?? null,
                $query['transaction_id'] ?? null,
                $query['transRef'] ?? null,
                $query['trans_ref'] ?? null,
                $query['billPaymentRef1'] ?? null,
                $emv['62.05'] ?? null,
                $emv['62.07'] ?? null,
                $emv['emvco']['merchant_account_information']['promptpay_id'] ?? null,
            ]),
            'slip_qr_receiver' => $this->firstFilled([
                $slipVerify['sending_bank_name'] ?? null,
                $query['receiver'] ?? null,
                $query['merchant'] ?? null,
                $query['to'] ?? null,
                $query['account'] ?? null,
                $emv['29.01'] ?? null,
                $emv['29.02'] ?? null,
                $emv['29.03'] ?? null,
                $emv['30.01'] ?? null,
                $emv['30.02'] ?? null,
                $emv['30.03'] ?? null,
            ]),
        ];
    }

    public function withDuplicateReview(array $result, ?Payment $currentPayment = null): array
    {
        if (($result['slip_qr_status'] ?? null) !== 'decoded') {
            return $result;
        }

        $payload = $result['slip_qr_payload'] ?? null;
        $reference = $result['slip_qr_reference'] ?? null;

        if (! $payload && ! $reference) {
            return $result;
        }

        $duplicate = Payment::query()
            ->with('order:id,order_number,customer_name,customer_phone')
            ->when($currentPayment?->exists, fn ($query) => $query->whereKeyNot($currentPayment->getKey()))
            ->where(function ($query) use ($payload, $reference) {
                if ($payload) {
                    $query->where('slip_qr_payload', $payload);
                }

                if ($reference) {
                    $query->orWhere('slip_qr_reference', $reference);
                }
            })
            ->whereNotNull('slip_qr_status')
            ->first();

        if (! $duplicate) {
            return $result;
        }

        $data = $result['slip_qr_data'] ?? [];
        $data['duplicate'] = [
            'payment_id' => $duplicate->id,
            'ticket_order_id' => $duplicate->ticket_order_id,
            'order_number' => $duplicate->order?->order_number,
            'customer_name' => $duplicate->order?->customer_name,
            'matched_by' => $payload && $duplicate->slip_qr_payload === $payload ? 'payload' : 'reference',
        ];

        return array_merge($result, [
            'slip_qr_status' => 'duplicate',
            'slip_qr_data' => $data,
        ]);
    }

    private function parseSlipVerifyPayload(string $payload): array
    {
        $fields = $this->parseTlv($payload);
        $verifyFields = $this->parseTlv($fields['00'] ?? '');

        if (($verifyFields['00'] ?? null) !== '000001') {
            return [];
        }

        $bankCode = $verifyFields['01'] ?? null;
        $transactionReference = $verifyFields['02'] ?? null;

        if (! $bankCode || ! $transactionReference) {
            return [];
        }

        return [
            'api_type' => $verifyFields['00'],
            'sending_bank' => $bankCode,
            'sending_bank_name' => self::THAI_BANKS[$bankCode] ?? 'Unknown bank / ไม่ทราบธนาคาร',
            'transaction_reference' => $transactionReference,
            'country_code' => $fields['51'] ?? null,
            'checksum' => $fields['91'] ?? null,
        ];
    }

    private function parseEmvPayload(string $payload): array
    {
        if (! preg_match('/^\d{4}/', $payload)) {
            return [];
        }

        $fields = $this->parseTlv($payload);

        if (! isset($fields['00'])) {
            return [];
        }

        foreach ($fields as $tag => $value) {
            if ((int) $tag >= 26 && (int) $tag <= 62) {
                foreach ($this->parseTlv($value) as $nestedTag => $nestedValue) {
                    $fields[$tag.'.'.$nestedTag] = $nestedValue;
                }
            }
        }

        $fields['emvco'] = $this->emvcoSummary($fields, $payload);

        return $fields;
    }

    private function emvcoSummary(array $fields, string $payload): array
    {
        $merchantAccount = $this->merchantAccountInformation($fields);
        $crc = $this->crcReview($payload, $fields['63'] ?? null);
        $amount = $this->firstAmount([$fields['54'] ?? null]);
        $currencyCode = $fields['53'] ?? null;
        $countryCode = $fields['58'] ?? null;
        $methodCode = $fields['01'] ?? null;

        return [
            'initiation_method' => match ($methodCode) {
                '11' => 'static',
                '12' => 'dynamic',
                default => 'unknown',
            },
            'initiation_method_code' => $methodCode,
            'merchant_account_information' => $merchantAccount,
            'currency_code' => $currencyCode,
            'currency' => $currencyCode === '764' ? 'THB' : $currencyCode,
            'amount' => $amount,
            'country_code' => $countryCode,
            'crc_checksum' => $crc,
        ];
    }

    private function merchantAccountInformation(array $fields): array
    {
        foreach (range(26, 51) as $tagNumber) {
            $tag = str_pad((string) $tagNumber, 2, '0', STR_PAD_LEFT);
            $guid = $fields[$tag.'.00'] ?? null;

            if ($guid !== 'A000000677010111') {
                continue;
            }

            $promptPayId = $this->firstFilled([
                $fields[$tag.'.01'] ?? null,
                $fields[$tag.'.02'] ?? null,
                $fields[$tag.'.03'] ?? null,
            ]);

            return [
                'tag' => $tag,
                'globally_unique_identifier' => $guid,
                'promptpay_id' => $promptPayId,
                'promptpay_type' => match (true) {
                    isset($fields[$tag.'.01']) => 'phone',
                    isset($fields[$tag.'.02']) => 'national_id_or_tax_id',
                    isset($fields[$tag.'.03']) => 'ewallet_id',
                    default => null,
                },
                'routing_data' => $fields[$tag] ?? null,
            ];
        }

        return [];
    }

    private function crcReview(string $payload, ?string $reported): array
    {
        if (! $reported || ! str_ends_with($payload, '6304'.$reported)) {
            return [
                'value' => $reported,
                'valid' => false,
            ];
        }

        $payloadForCrc = substr($payload, 0, -4);
        $expected = $this->formatCrc($this->crc16Xmodem($payloadForCrc));

        return [
            'value' => strtoupper($reported),
            'expected' => $expected,
            'valid' => strtoupper($reported) === $expected,
        ];
    }

    private function parseTlv(string $payload): array
    {
        $offset = 0;
        $fields = [];
        $length = strlen($payload);

        while ($offset + 4 <= $length) {
            $tag = substr($payload, $offset, 2);
            $valueLength = substr($payload, $offset + 2, 2);

            if (! ctype_digit($tag.$valueLength)) {
                break;
            }

            $offset += 4;
            $size = (int) $valueLength;

            if ($offset + $size > $length) {
                break;
            }

            $fields[$tag] = substr($payload, $offset, $size);
            $offset += $size;
        }

        return $fields;
    }

    private function queryParams(string $payload): array
    {
        $query = parse_url($payload, PHP_URL_QUERY);

        if (! is_string($query) || $query === '') {
            return [];
        }

        parse_str($query, $params);

        return array_filter($params, fn ($value) => is_scalar($value) && trim((string) $value) !== '');
    }

    private function firstAmount(array $values): ?float
    {
        foreach ($values as $value) {
            if ($value === null) {
                continue;
            }

            $amount = (float) preg_replace('/[^0-9.]/', '', (string) $value);

            if ($amount > 0) {
                return round($amount, 2);
            }
        }

        return null;
    }

    private function firstDate(array $values): ?Carbon
    {
        foreach ($values as $value) {
            if (! is_scalar($value) || trim((string) $value) === '') {
                continue;
            }

            try {
                return Carbon::parse((string) $value);
            } catch (Throwable) {
                continue;
            }
        }

        return null;
    }

    private function firstFilled(array $values): ?string
    {
        foreach ($values as $value) {
            if (is_scalar($value) && trim((string) $value) !== '') {
                return trim((string) $value);
            }
        }

        return null;
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
