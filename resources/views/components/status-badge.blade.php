@props([
    'status',
    'type' => 'order',
])

@php
    $key = str_replace('-', '_', (string) $status);
    $labels = [
        'pending' => 'Pending / รอตรวจสอบ',
        'approved' => 'Approved / อนุมัติแล้ว',
        'checked_in' => 'Checked in / เช็กอินแล้ว',
        'checked_out' => 'Checked out / เช็กเอาต์แล้ว',
        'rejected' => 'Rejected / ปฏิเสธ',
        'cancelled' => 'Cancelled / ยกเลิก',
        'refunded' => 'Refunded / คืนเงินแล้ว',
        'expired' => 'Expired / หมดอายุ',
    ];
    $classes = [
        'pending' => 'border-amber-300 bg-amber-100 text-amber-800 dark:border-amber-400/30 dark:bg-amber-400/15 dark:text-amber-100',
        'approved' => 'border-emerald-300 bg-emerald-100 text-emerald-800 dark:border-emerald-400/30 dark:bg-emerald-400/15 dark:text-emerald-100',
        'checked_in' => 'border-sky-300 bg-sky-100 text-sky-800 dark:border-sky-400/30 dark:bg-sky-400/15 dark:text-sky-100',
        'checked_out' => 'border-indigo-300 bg-indigo-100 text-indigo-800 dark:border-indigo-400/30 dark:bg-indigo-400/15 dark:text-indigo-100',
        'rejected' => 'border-rose-300 bg-rose-100 text-rose-800 dark:border-rose-400/30 dark:bg-rose-400/15 dark:text-rose-100',
        'cancelled' => 'border-zinc-300 bg-zinc-100 text-zinc-700 dark:border-white/15 dark:bg-white/10 dark:text-zinc-200',
        'refunded' => 'border-violet-300 bg-violet-100 text-violet-800 dark:border-violet-400/30 dark:bg-violet-400/15 dark:text-violet-100',
        'expired' => 'border-zinc-300 bg-zinc-100 text-zinc-600 dark:border-white/15 dark:bg-white/10 dark:text-zinc-300',
    ];
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center rounded-full border px-2.5 py-1 text-xs font-semibold '.($classes[$key] ?? 'border-zinc-300 bg-zinc-100 text-zinc-700 dark:border-white/15 dark:bg-white/10 dark:text-zinc-200')]) }}>
    {{ $labels[$key] ?? str_replace('_', ' ', $key) }}
</span>
