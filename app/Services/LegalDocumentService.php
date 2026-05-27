<?php

namespace App\Services;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class LegalDocumentService
{
    private const DOCUMENTS = [
        'terms' => [
            'route' => 'legal.terms',
            'title' => ['en' => 'Terms and Conditions', 'th' => 'ข้อกำหนดและเงื่อนไข'],
            'description' => 'TicketFlow terms and conditions for buying, managing, and using event tickets.',
        ],
        'privacy' => [
            'route' => 'legal.privacy',
            'title' => ['en' => 'Privacy Policy', 'th' => 'นโยบายความเป็นส่วนตัว'],
            'description' => 'TicketFlow privacy policy for customer, ticket, payment, LINE, and notification data.',
        ],
        'refund' => [
            'route' => 'legal.refund',
            'title' => ['en' => 'Refund Policy', 'th' => 'นโยบายการคืนเงิน'],
            'description' => 'TicketFlow refund policy for pending, approved, rejected, cancelled, and transferred event ticket orders.',
        ],
        'event-admission' => [
            'route' => 'legal.event-admission',
            'title' => ['en' => 'Event Admission Policy', 'th' => 'นโยบายการเข้างาน'],
            'description' => 'TicketFlow event admission policy for ticket QR codes, gate scanning, rejected entry, and event rules.',
        ],
        'cookies' => [
            'route' => 'legal.cookies',
            'title' => ['en' => 'Cookie Policy', 'th' => 'นโยบายคุกกี้'],
            'description' => 'TicketFlow cookie policy for sessions, login, checkout, analytics, and embedded service cookies.',
        ],
    ];

    public function all(): array
    {
        return collect(self::DOCUMENTS)
            ->map(fn (array $document, string $key) => $this->document($key))
            ->all();
    }

    public function document(string $key): array
    {
        abort_unless(isset(self::DOCUMENTS[$key]), 404);

        $document = self::DOCUMENTS[$key];

        return [
            ...$document,
            'key' => $key,
            'url' => route($document['route']),
            'html' => [
                'en' => $this->markdown($key, 'en'),
                'th' => $this->markdown($key, 'th'),
            ],
        ];
    }

    public function modal(array $keys): array
    {
        return collect($keys)
            ->mapWithKeys(fn (string $key) => [$key => Arr::only($this->document($key), ['title', 'html', 'url'])])
            ->all();
    }

    private function markdown(string $key, string $locale): string
    {
        $path = resource_path("legal-docs/{$key}.{$locale}.md");

        abort_unless(File::exists($path), 500, "Missing legal document: {$key}.{$locale}");

        return Str::markdown(File::get($path), [
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
        ]);
    }
}
