<x-layouts.app :title="'Responses: '.$survey->title">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-3xl font-semibold text-zinc-950 dark:text-white">{{ $survey->title }}</h1>
            <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">{{ $responses->total() }} <x-t en="completed responses" th="คำตอบที่เสร็จสมบูรณ์" /></p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <a class="inline-flex items-center gap-2 rounded-md border border-zinc-200 px-4 py-2 text-sm font-semibold text-zinc-700 dark:border-white/10 dark:text-zinc-200" href="{{ route('admin.surveys.responses.export.csv', $survey) }}">
                <x-icon name="file-down" /><x-t en="Export CSV" th="ส่งออก CSV" />
            </a>
            <a class="inline-flex items-center gap-2 rounded-md bg-emerald-400 px-4 py-2 font-semibold text-zinc-950" href="{{ route('admin.surveys.responses.export.pdf', $survey) }}">
                <x-icon name="file-down" /><x-t en="Export PDF" th="ส่งออก PDF" />
            </a>
            <a class="inline-flex items-center gap-2 rounded-md border border-zinc-200 px-4 py-2 text-sm font-semibold text-zinc-700 dark:border-white/10 dark:text-zinc-200" href="{{ route('admin.surveys.responses.export.preview', $survey) }}">
                <x-icon name="eye" /><x-t en="Preview" th="ดูตัวอย่าง" />
            </a>
            <a class="inline-flex items-center gap-2 rounded-md border border-zinc-200 px-3 py-2 text-sm font-semibold text-zinc-700 dark:border-white/10 dark:text-zinc-200" href="{{ route('admin.surveys.index') }}">
                <x-icon name="arrow-left" /><x-t en="Back" th="กลับ" />
            </a>
        </div>
    </div>

    @php $questions = $survey->questions ?? []; @endphp

    <section class="mt-6 overflow-x-auto rounded-lg border border-zinc-200 bg-white dark:border-white/10 dark:bg-white/[0.04]">
        <table class="w-full text-sm" x-data="{ metaRow: null }">
            <thead>
                <tr class="border-b border-zinc-200 dark:border-white/10">
                    <th class="whitespace-nowrap px-4 py-3 text-left font-semibold text-zinc-950 dark:text-white">#</th>
                    <th class="whitespace-nowrap px-4 py-3 text-left font-semibold text-zinc-950 dark:text-white"><x-t en="Respondent" th="ผู้ตอบ" /></th>
                    <th class="whitespace-nowrap px-4 py-3 text-left font-semibold text-zinc-950 dark:text-white"><x-t en="Submitted" th="วันที่ส่ง" /></th>
                    @foreach($questions as $question)
                        <th class="whitespace-nowrap px-4 py-3 text-left font-semibold text-zinc-950 dark:text-white">{{ $question['label'] }}</th>
                    @endforeach
                    <th class="whitespace-nowrap px-4 py-3 text-left font-semibold text-zinc-950 dark:text-white"><x-t en="Actions" th="จัดการ" /></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-200 dark:divide-white/10">
                @forelse($responses as $response)
                    <tr class="hover:bg-zinc-50 dark:hover:bg-white/[0.02]">
                        <td class="px-4 py-3 text-zinc-600 dark:text-zinc-400">{{ $loop->iteration }}</td>
                        <td class="px-4 py-3 text-zinc-950 dark:text-white">{{ $response->user?->name ?? $response->session_id ?? 'Guest' }}</td>
                        <td class="whitespace-nowrap px-4 py-3 text-zinc-600 dark:text-zinc-400">{{ $response->completed_at?->inDisplayTimezone()->format('Y-m-d H:i') ?? '-' }}</td>
                        @foreach($questions as $question)
                            <td class="px-4 py-3 text-zinc-950 dark:text-white">
                                @php
                                    $answer = $response->answers[$question['key']] ?? '';
                                @endphp
                                @if(is_array($answer))
                                    {{ implode(', ', $answer) }}
                                @else
                                    {{ $answer }}
                                @endif
                            </td>
                        @endforeach
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-1">
                                @if($response->meta)
                                    <button class="inline-flex items-center gap-1.5 rounded-md border border-zinc-200 px-2 py-1 text-xs font-semibold text-zinc-600 hover:bg-zinc-50 dark:border-white/10 dark:text-zinc-400 dark:hover:bg-white/10"
                                        type="button"
                                        @click="metaRow = metaRow === {{ $loop->index }} ? null : {{ $loop->index }}">
                                        <x-icon name="info" class="h-3.5 w-3.5" />
                                    </button>
                                @endif
                                <form method="POST" action="{{ route('admin.surveys.responses.destroy', [$survey, $response]) }}" onsubmit="return confirm('Delete this response? / ลบคำตอบนี้?')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="inline-flex items-center gap-1.5 rounded-md border border-rose-300 px-2 py-1 text-xs font-semibold text-rose-700 hover:bg-rose-50 dark:border-rose-400/30 dark:text-rose-200 dark:hover:bg-rose-400/10">
                                        <x-icon name="trash-2" class="h-3.5 w-3.5" /><x-t en="Delete" th="ลบ" />
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @if($response->meta)
                        <tr x-cloak x-show="metaRow === {{ $loop->index }}" x-transition>
                            <td colspan="{{ count($questions) + 4 }}" class="bg-zinc-50 px-6 py-3 dark:bg-zinc-900">
                                <div class="grid grid-cols-2 gap-x-8 gap-y-1 text-xs text-zinc-600 dark:text-zinc-400 sm:grid-cols-3 lg:grid-cols-4">
                                    @foreach($response->meta as $key => $value)
                                        <div><span class="font-semibold text-zinc-950 dark:text-white">{{ $key }}:</span> {{ $value }}</div>
                                    @endforeach
                                </div>
                            </td>
                        </tr>
                    @endif
                @empty
                    <tr>
                        <td class="px-4 py-6 text-center text-zinc-600 dark:text-zinc-400" colspan="{{ count($questions) + 4 }}">
                            <x-t en="No responses yet." th="ยังไม่มีคำตอบ" />
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        <div class="p-4">{{ $responses->links() }}</div>
    </section>
</x-layouts.app>
