@props([
    'en' => '',
    'th' => '',
])
@php
    $english = trim((string) $en);
    $thai = trim((string) $th);
    $initial = collect([$english, $thai])->filter()->unique()->implode(' / ');
@endphp
<span {{ $attributes->merge([
    'data-i18n-auto' => 'true',
    'data-i18n-en' => $english,
    'data-i18n-th' => $thai,
]) }}>{{ $initial }}</span>
