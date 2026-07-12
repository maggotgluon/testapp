<x-layouts.app :title="'Preview: '.$survey->title">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-3xl font-semibold text-zinc-950 dark:text-white">{{ $survey->title }}</h1>
            <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">{{ count($responses) }} <x-t en="responses" th="คำตอบ" /></p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <a class="inline-flex items-center gap-2 rounded-md border border-zinc-200 px-4 py-2 text-sm font-semibold text-zinc-700 dark:border-white/10 dark:text-zinc-200" href="{{ route('admin.surveys.responses.export.csv', $survey) }}">
                <x-icon name="file-down" /><x-t en="Export CSV" th="ส่งออก CSV" />
            </a>
            <a class="inline-flex items-center gap-2 rounded-md bg-emerald-400 px-4 py-2 font-semibold text-zinc-950" href="{{ route('admin.surveys.responses.export.pdf', $survey) }}">
                <x-icon name="file-down" /><x-t en="Export PDF" th="ส่งออก PDF" />
            </a>
            <a class="inline-flex items-center gap-2 rounded-md border border-zinc-200 px-4 py-2 text-sm font-semibold text-zinc-700 dark:border-white/10 dark:text-zinc-200" href="{{ route('admin.surveys.responses', $survey) }}">
                <x-icon name="arrow-left" /><x-t en="Back to responses" th="กลับไปยังคำตอบ" />
            </a>
        </div>
    </div>

    <section class="mt-6 overflow-x-auto rounded-lg border border-zinc-200 bg-white dark:border-white/10 dark:bg-white/[0.04]">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-zinc-200 dark:border-white/10">
                    <th class="whitespace-nowrap px-4 py-3 text-left font-semibold text-zinc-950 dark:text-white">#</th>
                    <th class="whitespace-nowrap px-4 py-3 text-left font-semibold text-zinc-950 dark:text-white"><x-t en="Respondent" th="ผู้ตอบ" /></th>
                    <th class="whitespace-nowrap px-4 py-3 text-left font-semibold text-zinc-950 dark:text-white"><x-t en="Submitted" th="วันที่ส่ง" /></th>
                    @foreach($questions as $question)
                        <th class="whitespace-nowrap px-4 py-3 text-left font-semibold text-zinc-950 dark:text-white">{{ $question['label'] }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-200 dark:divide-white/10">
                @forelse($responses as $i => $response)
                    <tr class="hover:bg-zinc-50 dark:hover:bg-white/[0.02]">
                        <td class="px-4 py-3 text-zinc-600 dark:text-zinc-400">{{ $i + 1 }}</td>
                        <td class="px-4 py-3 text-zinc-950 dark:text-white">{{ $response->user?->name ?? 'Guest' }}</td>
                        <td class="whitespace-nowrap px-4 py-3 text-zinc-600 dark:text-zinc-400">{{ $response->completed_at?->format('Y-m-d H:i') ?? '-' }}</td>
                        @foreach($questions as $question)
                            <td class="max-w-xs break-words px-4 py-3 text-zinc-950 dark:text-white" style="word-break: break-word; overflow-wrap: break-word;">
                                @php $answer = $response->answers[$question['key']] ?? ''; @endphp
                                {{ is_array($answer) ? implode(', ', $answer) : $answer }}
                            </td>
                        @endforeach
                    </tr>
                @empty
                    <tr>
                        <td class="px-4 py-6 text-center text-zinc-600 dark:text-zinc-400" colspan="{{ count($questions) + 3 }}">
                            <x-t en="No responses yet." th="ยังไม่มีคำตอบ" />
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </section>

    @if(count($responses) > 0)
        <div class="mt-4 flex items-center justify-center gap-2 text-sm text-zinc-600 dark:text-zinc-400">
            <x-t en="Showing all" th="แสดงทั้งหมด" /> {{ count($responses) }} <x-t en="responses." th="คำตอบ" />
        </div>
    @endif
</x-layouts.app>
