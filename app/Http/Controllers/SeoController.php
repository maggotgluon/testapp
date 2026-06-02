<?php

namespace App\Http\Controllers;

use App\Models\Event;
use Illuminate\Http\Response;

class SeoController extends Controller
{
    public function robots(): Response
    {
        $sitemapUrl = route('seo.sitemap');
        $content = <<<ROBOTS
User-agent: *
Disallow: /admin/
Disallow: /crm/
Disallow: /login
Disallow: /admin/login
Disallow: /auth/
Disallow: /orders/
Disallow: /orders/lookup
Disallow: /tickets/
Disallow: /payments/
Disallow: /profile
Disallow: /line/
Disallow: /push-subscriptions

Sitemap: {$sitemapUrl}
ROBOTS;

        return response($content, 200)->header('Content-Type', 'text/plain');
    }

    public function sitemap(): Response
    {
        $urls = collect([
            [
                'loc' => route('events.index'),
                'lastmod' => now()->toDateString(),
                'changefreq' => 'daily',
                'priority' => '0.8',
            ],
            [
                'loc' => route('about'),
                'lastmod' => now()->toDateString(),
                'changefreq' => 'monthly',
                'priority' => '0.5',
            ],
            [
                'loc' => route('guides.buy-ticket'),
                'lastmod' => now()->toDateString(),
                'changefreq' => 'monthly',
                'priority' => '0.5',
            ],
            [
                'loc' => route('guides.gate-check-in'),
                'lastmod' => now()->toDateString(),
                'changefreq' => 'monthly',
                'priority' => '0.4',
            ],
            [
                'loc' => route('legal.terms'),
                'lastmod' => now()->toDateString(),
                'changefreq' => 'yearly',
                'priority' => '0.3',
            ],
            [
                'loc' => route('legal.privacy'),
                'lastmod' => now()->toDateString(),
                'changefreq' => 'yearly',
                'priority' => '0.3',
            ],
            [
                'loc' => route('legal.refund'),
                'lastmod' => now()->toDateString(),
                'changefreq' => 'yearly',
                'priority' => '0.3',
            ],
            [
                'loc' => route('legal.event-admission'),
                'lastmod' => now()->toDateString(),
                'changefreq' => 'yearly',
                'priority' => '0.3',
            ],
            [
                'loc' => route('legal.cookies'),
                'lastmod' => now()->toDateString(),
                'changefreq' => 'yearly',
                'priority' => '0.3',
            ],
        ]);

        $events = Event::query()
            ->visible()
            ->orderBy('starts_at')
            ->get()
            ->map(fn (Event $event) => [
                'loc' => route('events.show', $event),
                'lastmod' => $event->updated_at?->toDateString() ?? now()->toDateString(),
                'changefreq' => $event->starts_at->isFuture() ? 'daily' : 'weekly',
                'priority' => '1.0',
            ]);

        $urls = $urls->merge($events);

        $xml = view('seo.sitemap', compact('urls'))->render();

        return response($xml, 200)->header('Content-Type', 'application/xml');
    }
}
