@php
    $questionSeed = old('questions', collect($survey->questions ?? [])->map(fn ($question) => array_merge($question, [
        'options' => implode("\n", $question['options'] ?? []),
    ]))->values()->all());
@endphp
<x-layouts.app :title="$survey->exists ? 'Edit survey' : 'New survey'">
    <form method="POST" action="{{ $survey->exists ? route('admin.surveys.update', $survey) : route('admin.surveys.store') }}" class="mx-auto max-w-4xl rounded-lg border border-zinc-200 bg-white p-6 dark:border-white/10 dark:bg-white/[0.04]" x-data="surveyBuilder(@js($questionSeed))">
        @csrf
        @if($survey->exists) @method('PUT') @endif

        <h1 class="inline-flex items-center gap-2 text-2xl font-semibold text-zinc-950 dark:text-white"><x-icon name="clipboard-list" class="h-6 w-6 text-emerald-500" />{{ $survey->exists ? 'Edit survey / แก้ไขแบบสอบถาม' : 'New survey / เพิ่มแบบสอบถาม' }}</h1>

        <div class="mt-5 grid gap-4 sm:grid-cols-2">
            <label class="text-sm text-zinc-700 dark:text-zinc-300 sm:col-span-2"><x-t en="Title" th="ชื่อแบบสอบถาม" /><input class="mt-1 w-full rounded-md border border-zinc-200 bg-white px-3 py-2 text-zinc-950 dark:border-white/10 dark:bg-zinc-950 dark:text-white" name="title" value="{{ old('title', $survey->title) }}" required></label>
            <label class="text-sm text-zinc-700 dark:text-zinc-300 sm:col-span-2"><x-t en="Description" th="คำอธิบาย" /><textarea class="mt-1 w-full rounded-md border border-zinc-200 bg-white px-3 py-2 text-zinc-950 dark:border-white/10 dark:bg-zinc-950 dark:text-white" name="description" rows="3">{{ old('description', $survey->description) }}</textarea></label>
            <label class="text-sm text-zinc-700 dark:text-zinc-300"><x-t en="Event" th="อีเวนต์" />
                <select class="mt-1 w-full rounded-md border border-zinc-200 bg-white px-3 py-2 text-zinc-950 dark:border-white/10 dark:bg-zinc-950 dark:text-white" name="event_id">
                    <option value=""><x-t en="All events" th="ทุกอีเวนต์" /></option>
                    @foreach($events as $event)
                        <option value="{{ $event->id }}" @selected((string) old('event_id', $survey->event_id) === (string) $event->id)>{{ $event->name }}</option>
                    @endforeach
                </select>
            </label>
            <label class="text-sm text-zinc-700 dark:text-zinc-300"><x-t en="Placement" th="ตำแหน่ง" />
                <select class="mt-1 w-full rounded-md border border-zinc-200 bg-white px-3 py-2 text-zinc-950 dark:border-white/10 dark:bg-zinc-950 dark:text-white" name="placement" required>
                    @foreach($placements as $key => $label)
                        <option value="{{ $key }}" @selected(old('placement', $survey->placement) === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </label>
            <label class="text-sm text-zinc-700 dark:text-zinc-300"><x-t en="Starts at" th="เริ่มแสดง" /><input class="mt-1 w-full rounded-md border border-zinc-200 bg-white px-3 py-2 text-zinc-950 dark:border-white/10 dark:bg-zinc-950 dark:text-white" type="datetime-local" name="starts_at" value="{{ old('starts_at', $survey->starts_at?->format('Y-m-d\TH:i')) }}"></label>
            <label class="text-sm text-zinc-700 dark:text-zinc-300"><x-t en="Ends at" th="สิ้นสุด" /><input class="mt-1 w-full rounded-md border border-zinc-200 bg-white px-3 py-2 text-zinc-950 dark:border-white/10 dark:bg-zinc-950 dark:text-white" type="datetime-local" name="ends_at" value="{{ old('ends_at', $survey->ends_at?->format('Y-m-d\TH:i')) }}"></label>
            <label class="flex items-center gap-2 text-sm text-zinc-700 dark:text-zinc-300"><input class="rounded border-zinc-200 text-emerald-500 dark:border-white/10" type="checkbox" name="is_active" value="1" @checked(old('is_active', $survey->is_active ?? true))> <x-t en="Active" th="เปิดใช้งาน" /></label>
        </div>

        <section class="mt-6">
            <div class="flex items-center justify-between gap-3">
                <h2 class="text-lg font-semibold text-zinc-950 dark:text-white"><x-t en="Questions" th="คำถาม" /></h2>
                <button class="inline-flex items-center gap-2 rounded-md border border-emerald-300 px-3 py-2 text-sm font-semibold text-emerald-700 dark:border-emerald-400/40 dark:text-emerald-200" type="button" @click="addQuestion()"><x-icon name="plus" /><x-t en="Add question" th="เพิ่มคำถาม" /></button>
            </div>

            <div class="mt-3 grid gap-4">
                <template x-for="(question, index) in questions" :key="question.localId">
                    <div class="rounded-md border border-zinc-200 bg-zinc-50 p-4 dark:border-white/10 dark:bg-zinc-900">
                        <div class="grid gap-3 sm:grid-cols-2">
                            <label class="text-sm text-zinc-700 dark:text-zinc-300 sm:col-span-2"><x-t en="Question text" th="ข้อความคำถาม" /><input class="mt-1 w-full rounded-md border border-zinc-200 bg-white px-3 py-2 text-zinc-950 dark:border-white/10 dark:bg-zinc-950 dark:text-white" :name="`questions[${index}][label]`" x-model="question.label" required></label>
                            <label class="text-sm text-zinc-700 dark:text-zinc-300"><x-t en="Key" th="คีย์ข้อมูล" /><input class="mt-1 w-full rounded-md border border-zinc-200 bg-white px-3 py-2 text-zinc-950 dark:border-white/10 dark:bg-zinc-950 dark:text-white" :name="`questions[${index}][key]`" x-model="question.key" placeholder="fitness_goal"></label>
                            <label class="text-sm text-zinc-700 dark:text-zinc-300"><x-t en="Type" th="ประเภท" />
                                <select class="mt-1 w-full rounded-md border border-zinc-200 bg-white px-3 py-2 text-zinc-950 dark:border-white/10 dark:bg-zinc-950 dark:text-white" :name="`questions[${index}][type]`" x-model="question.type">
                                    <option value="text">Text</option>
                                    <option value="textarea">Long text</option>
                                    <option value="email">Email</option>
                                    <option value="number">Number</option>
                                    <option value="date">Date</option>
                                    <option value="select">Select</option>
                                    <option value="radio">Multiple choice</option>
                                    <option value="checkboxes">Checkboxes</option>
                                </select>
                            </label>
                            <label class="text-sm text-zinc-700 dark:text-zinc-300 sm:col-span-2"><x-t en="Help text" th="คำอธิบายเพิ่มเติม" /><input class="mt-1 w-full rounded-md border border-zinc-200 bg-white px-3 py-2 text-zinc-950 dark:border-white/10 dark:bg-zinc-950 dark:text-white" :name="`questions[${index}][help]`" x-model="question.help"></label>
                            <label class="text-sm text-zinc-700 dark:text-zinc-300 sm:col-span-2" x-show="['select', 'radio', 'checkboxes'].includes(question.type)" x-cloak><x-t en="Options, one per line" th="ตัวเลือก แยกบรรทัด" /><textarea class="mt-1 w-full rounded-md border border-zinc-200 bg-white px-3 py-2 text-zinc-950 dark:border-white/10 dark:bg-zinc-950 dark:text-white" :name="`questions[${index}][options]`" x-model="question.options" rows="4"></textarea></label>
                            <div class="flex flex-wrap items-center gap-4 sm:col-span-2" x-show="['select', 'radio', 'checkboxes'].includes(question.type)" x-cloak>
                                <label class="flex items-center gap-2 text-sm text-zinc-700 dark:text-zinc-300"><input class="rounded text-emerald-500" type="checkbox" :name="`questions[${index}][hasOther]`" value="1" x-model="question.hasOther"> <x-t en="Include 'Other' option" th="เพิ่มตัวเลือก 'อื่นๆ'" /></label>
                                <label class="flex items-center gap-2 text-sm text-zinc-700 dark:text-zinc-300" x-show="question.hasOther"><x-t en="Other label" th="ข้อความอื่นๆ" /><input class="w-48 rounded-md border border-zinc-200 bg-white px-3 py-2 text-zinc-950 dark:border-white/10 dark:bg-zinc-950 dark:text-white" :name="`questions[${index}][otherLabel]`" x-model="question.otherLabel" placeholder="Other / อื่นๆ"></label>
                            </div>
                            <div class="flex flex-wrap items-center gap-4 sm:col-span-2" x-show="question.type === 'select'" x-cloak>
                                <label class="flex items-center gap-2 text-sm text-zinc-700 dark:text-zinc-300"><input class="rounded text-emerald-500" type="checkbox" :name="`questions[${index}][searchable]`" value="1" x-model="question.searchable"> <x-t en="Searchable (when > 5 options)" th="ค้นหาได้ (เมื่อมีมากกว่า 5 ตัวเลือก)" /></label>
                            </div>
                            <div class="flex flex-wrap items-center justify-between gap-3 sm:col-span-2">
                                <label class="flex items-center gap-2 text-sm text-zinc-700 dark:text-zinc-300"><input class="rounded text-emerald-500" type="checkbox" :name="`questions[${index}][required]`" value="1" x-model="question.required"> <x-t en="Required" th="จำเป็น" /></label>
                                <button class="inline-flex items-center gap-2 rounded-md border border-rose-300 px-3 py-2 text-sm font-semibold text-rose-700 dark:border-rose-400/40 dark:text-rose-200" type="button" @click="removeQuestion(index)"><x-icon name="trash-2" /><x-t en="Remove" th="ลบ" /></button>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </section>

        <button class="mt-6 inline-flex items-center gap-2 rounded-md bg-emerald-400 px-4 py-3 font-semibold text-zinc-950"><x-icon name="save" /><x-t en="Save survey" th="บันทึกแบบสอบถาม" /></button>
    </form>

</x-layouts.app>
