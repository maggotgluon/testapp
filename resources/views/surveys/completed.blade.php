<x-layouts.app :title="$survey->title">
    <div class="mx-auto max-w-xl rounded-lg border border-emerald-400/30 bg-emerald-400/10 p-6 text-emerald-950 dark:text-emerald-50">
        <div class="inline-flex items-center gap-2 font-semibold"><x-icon name="check-circle" /><x-t en="Survey already completed" th="ทำแบบสอบถามนี้แล้ว" /></div>
        <h1 class="mt-2 text-2xl font-semibold">{{ $survey->title }}</h1>
        <a class="mt-5 inline-flex items-center gap-2 rounded-md bg-emerald-400 px-4 py-3 font-semibold text-zinc-950" href="{{ $returnTo }}"><x-icon name="arrow-right" /><x-t en="Continue" th="ดำเนินการต่อ" /></a>
    </div>
</x-layouts.app>
