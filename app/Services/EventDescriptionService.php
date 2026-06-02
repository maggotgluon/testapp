<?php

namespace App\Services;

use Illuminate\Support\Str;

class EventDescriptionService
{
    public function render(?string $content, string $format = 'html'): string
    {
        if (! $content) {
            return '';
        }

        $html = $format === 'markdown'
            ? Str::markdown($content, ['html_input' => 'strip', 'allow_unsafe_links' => false])
            : $content;

        return $this->safeHtml($html) ?? '';
    }

    public function safeHtml(?string $html): ?string
    {
        if ($html === null) {
            return null;
        }

        $html = preg_replace('/<\s*(script|style|iframe|object|embed)[^>]*>.*?<\s*\/\s*\1\s*>/is', '', $html);
        $html = preg_replace('/\s+on[a-z]+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/is', '', $html);
        $html = preg_replace('/\s+(href|src)\s*=\s*("[^"]*javascript:[^"]*"|\'[^\']*javascript:[^\']*\'|[^\s>]*javascript:[^\s>]*)/is', '', $html);

        return strip_tags($html, '<p><br><strong><b><em><i><u><ul><ol><li><a><h2><h3><h4><blockquote><code><pre>');
    }
}
