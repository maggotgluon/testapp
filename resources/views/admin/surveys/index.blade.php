<x-layouts.app title="Surveys">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-3xl font-semibold text-zinc-950 dark:text-white"><x-t en="Surveys" th="แบบสอบถาม" /></h1>
            <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400"><x-t en="Place data capture at key event and checkout moments." th="เก็บข้อมูลผู้ใช้ตามจุดสำคัญของอีเวนต์และการซื้อ" /></p>
        </div>
        <a class="inline-flex items-center gap-2 rounded-md bg-emerald-400 px-4 py-2 font-semibold text-zinc-950" href="{{ route('admin.surveys.create') }}"><x-icon name="plus" /><x-t en="New survey" th="เพิ่มแบบสอบถาม" /></a>
    </div>

    <form class="mt-5 grid gap-3 rounded-lg border border-zinc-200 bg-white p-4 dark:border-white/10 dark:bg-white/[0.04] sm:grid-cols-3">
        <label class="text-sm text-zinc-700 dark:text-zinc-300"><x-t en="Event" th="อีเวนต์" />
            <select class="mt-1 w-full rounded-md border border-zinc-200 bg-white px-3 py-2 text-zinc-950 dark:border-white/10 dark:bg-zinc-950 dark:text-white" name="event_id">
                <option value=""><x-t en="All events" th="ทุกอีเวนต์" /></option>
                @foreach($events as $event)
                    <option value="{{ $event->id }}" @selected(request('event_id') == $event->id)>{{ $event->name }}</option>
                @endforeach
            </select>
        </label>
        <label class="text-sm text-zinc-700 dark:text-zinc-300"><x-t en="Placement" th="ตำแหน่ง" />
            <select class="mt-1 w-full rounded-md border border-zinc-200 bg-white px-3 py-2 text-zinc-950 dark:border-white/10 dark:bg-zinc-950 dark:text-white" name="placement">
                <option value=""><x-t en="All placements" th="ทุกตำแหน่ง" /></option>
                @foreach(\App\Models\Survey::PLACEMENTS as $key => $label)
                    <option value="{{ $key }}" @selected(request('placement') === $key)>{{ $label }}</option>
                @endforeach
            </select>
        </label>
        <div class="flex items-end gap-2">
            <button class="inline-flex flex-1 items-center justify-center gap-2 rounded-md bg-emerald-400 px-4 py-2 font-semibold text-zinc-950"><x-icon name="search" /><x-t en="Filter" th="กรอง" /></button>
            <a class="inline-flex items-center justify-center rounded-md border border-zinc-200 px-3 py-2 text-sm font-semibold text-zinc-700 dark:border-white/10 dark:text-zinc-200" href="{{ route('admin.surveys.index') }}"><x-t en="Reset" th="ล้าง" /></a>
        </div>
    </form>

    <section class="mt-6 rounded-lg border border-zinc-200 bg-white dark:border-white/10 dark:bg-white/[0.04]">
        <div class="divide-y divide-white/10">
            @forelse($surveys as $survey)
                <div class="interactive-row group flex flex-wrap items-center justify-between gap-4 p-4 dark:bg-white/[0.03]">
                    <a class="click-area-link" href="{{ route('admin.surveys.edit', $survey) }}" aria-label="Edit survey {{ $survey->title }}"></a>
                    <div class="click-area-content">
                        <div class="font-semibold text-zinc-950 group-hover:text-emerald-700 dark:text-white">{{ $survey->title }}</div>
                        <div class="text-sm text-zinc-600 dark:text-zinc-400">{{ $survey->event?->name ?? 'All events' }} · {{ $survey->placementLabel() }} · {{ count($survey->questions ?? []) }} questions · <a class="font-medium text-emerald-700 hover:text-emerald-600 dark:text-emerald-300 dark:hover:text-emerald-200" href="{{ route('admin.surveys.responses', $survey) }}">{{ $survey->responses->where('status', 'completed')->count() }} completed</a></div>
                    </div>
                    <div class="click-area-content flex flex-wrap items-center gap-2">
                        <span class="rounded bg-zinc-100 px-3 py-1 text-sm dark:bg-white/10 {{ $survey->is_active ? 'text-emerald-700 dark:text-emerald-200' : 'text-zinc-600 dark:text-zinc-400' }}">{{ $survey->is_active ? 'active' : 'inactive' }}</span>
                        <form method="POST" action="{{ route('admin.surveys.destroy', $survey) }}" onsubmit="return confirm('Delete this survey?')">
                            @csrf
                            @method('DELETE')
                            <button class="inline-flex items-center gap-2 rounded-md border border-rose-300 px-3 py-2 text-sm font-semibold text-rose-700 dark:border-rose-400/40 dark:text-rose-200"><x-icon name="trash-2" /><x-t en="Delete" th="ลบ" /></button>
                        </form>
                    </div>
                </div>
            @empty
                <div class="p-6 text-sm text-zinc-600 dark:text-zinc-400"><x-t en="No surveys found." th="ยังไม่มีแบบสอบถาม" /></div>
            @endforelse
        </div>
        <div class="p-4">{{ $surveys->links() }}</div>
    </section>
</x-layouts.app>
