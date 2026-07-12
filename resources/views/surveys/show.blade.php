<x-layouts.app :title="$survey->title">
    <form method="POST" action="{{ route('surveys.store', $survey) }}" class="mx-auto max-w-2xl rounded-lg border border-zinc-200 bg-white p-6 dark:border-white/10 dark:bg-white/[0.04]" x-data="{ meta: { screen: '', timezone: '', language: '', platform: '', cookies_enabled: '' } }" x-init="meta = { screen: screen.width+'x'+screen.height, timezone: Intl.DateTimeFormat().resolvedOptions().timeZone, language: navigator.language, platform: navigator.platform, cookies_enabled: navigator.cookieEnabled ? '1' : '0' }">
        @csrf
        <input type="hidden" name="meta[screen]" x-model="meta.screen">
        <input type="hidden" name="meta[timezone]" x-model="meta.timezone">
        <input type="hidden" name="meta[language]" x-model="meta.language">
        <input type="hidden" name="meta[platform]" x-model="meta.platform">
        <input type="hidden" name="meta[cookies_enabled]" x-model="meta.cookies_enabled">
        <div class="flex items-start justify-between gap-4">
            <div>
                <p class="inline-flex items-center gap-2 text-sm font-semibold text-emerald-700 dark:text-emerald-200"><x-icon name="clipboard-list" /><x-t en="Survey" th="แบบสอบถาม" /></p>
                <h1 class="mt-2 text-2xl font-semibold text-zinc-950 dark:text-white">{{ $survey->title }}</h1>
                @if($descriptionHtml)
                    <div class="prose prose-sm mt-2 text-zinc-600 dark:text-zinc-400">{!! $descriptionHtml !!}</div>
                @endif
            </div>
            @guest
                <a class="inline-flex shrink-0 items-center gap-2 rounded-md border border-zinc-200 px-3 py-2 text-sm font-semibold text-zinc-700 dark:border-white/10 dark:text-zinc-200" href="{{ route('login', ['redirect' => request()->fullUrl()]) }}"><x-icon name="log-in" /><x-t en="Login" th="เข้าสู่ระบบ" /></a>
            @endguest
        </div>

        <div class="mt-6 grid gap-5">
            @foreach($survey->questions ?? [] as $question)
                @php
                    $key = $question['key'] ?? 'question_'.$loop->index;
                    $type = $question['type'] ?? 'text';
                    $answer = old('answers.'.$key, $response->answers[$key] ?? ($type === 'checkboxes' ? [] : ''));
                    $options = $question['options'] ?? [];
                    $hasOther = ! empty($question['hasOther']);
                    $otherLabel = $question['otherLabel'] ?? 'Other / อื่นๆ';
                    $searchable = ! empty($question['searchable']) && count($options) > 5;
                    $otherValue = '';
                    $selectedOther = false;
                    if ($hasOther) {
                        if ($type === 'checkboxes') {
                            $otherAnswers = array_filter((array) $answer, fn ($v) => str_starts_with((string) $v, 'Other:'));
                            $otherValue = $otherAnswers ? explode('Other:', $otherAnswers[array_key_first($otherAnswers)], 2)[1] ?? '' : '';
                            $selectedOther = ! empty($otherAnswers);
                        } else {
                            $selectedOther = str_starts_with((string) $answer, 'Other:');
                            if ($selectedOther) {
                                $otherValue = explode('Other:', (string) $answer, 2)[1] ?? '';
                            }
                        }
                    }
                @endphp
                <fieldset class="rounded-md border border-zinc-200 bg-zinc-50 p-4 dark:border-white/10 dark:bg-zinc-900">
                    <legend class="px-1 text-sm font-semibold text-zinc-950 dark:text-white">
                        {{ $question['label'] ?? 'Question' }}
                        @if(! empty($question['required']))
                            <span class="ml-1 rounded bg-rose-400/20 px-1.5 py-0.5 text-xs text-rose-700 dark:text-rose-200"><x-t en="required" th="จำเป็น" /></span>
                        @endif
                    </legend>
                    @if(! empty($question['help']))
                        <p class="mt-1 text-xs text-zinc-500">{{ $question['help'] }}</p>
                    @endif

                    @if($type === 'textarea')
                        <textarea class="mt-3 w-full rounded-md border border-zinc-200 bg-white px-3 py-2 text-zinc-950 dark:border-white/10 dark:bg-zinc-950 dark:text-white" name="answers[{{ $key }}]" rows="4">{{ $answer }}</textarea>
                    @elseif($type === 'select')
                        @if($searchable)
                            <div class="relative mt-3" x-data="{ open: false, search: '', selected: '{{ $answer && !$selectedOther ? e($answer) : '' }}', otherSelected: {{ json_encode($selectedOther) }} }">
                                <input class="w-full rounded-md border border-zinc-200 bg-white px-3 py-2 text-zinc-950 dark:border-white/10 dark:bg-zinc-950 dark:text-white"
                                    type="text"
                                    x-model="search"
                                    @focus="open = true"
                                    @blur="setTimeout(() => open = false, 200)"
                                    @input="open = true"
                                    placeholder="{{ __('Choose one') }}"
                                    autocomplete="off">
                                <input type="hidden" name="answers[{{ $key }}]" x-model="selected">
                                <div class="absolute left-0 right-0 top-full z-10 mt-1 max-h-48 overflow-y-auto rounded-md border border-zinc-200 bg-white shadow-lg dark:border-white/10 dark:bg-zinc-900" x-show="open" x-cloak>
                                    @foreach($options as $option)
                                        <button type="button"
                                            class="block w-full px-3 py-2 text-left text-sm text-zinc-950 hover:bg-zinc-100 dark:text-white dark:hover:bg-white/10"
                                            x-show="!search || '{{ strtolower($option) }}'.includes(search.toLowerCase())"
                                            @mousedown.prevent="selected = '{{ $option }}'; search = '{{ $option }}'; open = false; otherSelected = false">
                                            {{ $option }}
                                        </button>
                                    @endforeach
                                    @if($hasOther)
                                        <button type="button"
                                            class="block w-full px-3 py-2 text-left text-sm text-zinc-950 hover:bg-zinc-100 dark:text-white dark:hover:bg-white/10"
                                            x-show="!search || 'other'.includes(search.toLowerCase())"
                                            @mousedown.prevent="selected = 'Other:'; search = '{{ $otherLabel }}'; open = false; otherSelected = true">
                                            {{ $otherLabel }}
                                        </button>
                                    @endif
                                </div>
                                @if($hasOther)
                                    <input class="mt-2 w-full rounded-md border border-zinc-200 bg-white px-3 py-2 text-zinc-950 dark:border-white/10 dark:bg-zinc-950 dark:text-white"
                                        type="text"
                                        name="answers[{{ $key }}_other]"
                                        x-show="otherSelected"
                                        x-cloak
                                        value="{{ $otherValue }}"
                                        placeholder="{{ $otherLabel }}">
                                @endif
                            </div>
                        @else
                            <select class="mt-3 w-full rounded-md border border-zinc-200 bg-white px-3 py-2 text-zinc-950 dark:border-white/10 dark:bg-zinc-950 dark:text-white" name="answers[{{ $key }}]">
                                <option value=""><x-t en="Choose one" th="เลือกหนึ่งข้อ" /></option>
                                @foreach($options as $option)
                                    <option value="{{ $option }}" @selected($answer === $option && !$selectedOther)>{{ $option }}</option>
                                @endforeach
                                @if($hasOther)
                                    <option value="Other:" @selected($selectedOther)>{{ $otherLabel }}</option>
                                @endif
                            </select>
                            @if($hasOther)
                                <input class="mt-2 w-full rounded-md border border-zinc-200 bg-white px-3 py-2 text-zinc-950 dark:border-white/10 dark:bg-zinc-950 dark:text-white"
                                    type="text"
                                    name="answers[{{ $key }}_other]"
                                    value="{{ $otherValue }}"
                                    placeholder="{{ $otherLabel }}"
                                    @if($selectedOther) style="display:block" @else style="display:none" @endif
                                    x-init="$watch(function() { const sel = $el.closest('fieldset').querySelector('select'); if (sel) { $el.style.display = sel.value === 'Other:' ? 'block' : 'none'; } })"
                                    x-on:change=""
                                    onchange="this.style.display = this.closest('fieldset').querySelector('select').value === 'Other:' ? 'block' : 'none'"
                                    onfocus="this.closest('fieldset').querySelector('select').value === 'Other:' ? null : this.style.display = 'none'">
                            @endif
                        @endif
                    @elseif($type === 'radio')
                        <div class="mt-3 grid gap-2">
                            @foreach($options as $option)
                                <label class="flex items-center gap-2 text-sm text-zinc-700 dark:text-zinc-300"><input class="text-emerald-500" type="radio" name="answers[{{ $key }}]" value="{{ $option }}" @checked($answer === $option && !$selectedOther)> {{ $option }}</label>
                            @endforeach
                            @if($hasOther)
                                <label class="flex items-center gap-2 text-sm text-zinc-700 dark:text-zinc-300"><input class="text-emerald-500" type="radio" name="answers[{{ $key }}]" value="Other:" @checked($selectedOther)> {{ $otherLabel }}</label>
                                <input class="mt-1 w-full rounded-md border border-zinc-200 bg-white px-3 py-2 text-zinc-950 dark:border-white/10 dark:bg-zinc-950 dark:text-white"
                                    type="text"
                                    name="answers[{{ $key }}_other]"
                                    value="{{ $otherValue }}"
                                    placeholder="{{ $otherLabel }}"
                                    oninput="this.previousElementSibling.querySelector('input[type=radio]').checked = true">
                            @endif
                        </div>
                    @elseif($type === 'checkboxes')
                        <div class="mt-3 grid gap-2">
                            @foreach($options as $option)
                                <label class="flex items-center gap-2 text-sm text-zinc-700 dark:text-zinc-300"><input class="rounded text-emerald-500" type="checkbox" name="answers[{{ $key }}][]" value="{{ $option }}" @checked(in_array($option, (array) $answer, true))> {{ $option }}</label>
                            @endforeach
                            @if($hasOther)
                                <label class="flex items-center gap-2 text-sm text-zinc-700 dark:text-zinc-300"><input class="rounded text-emerald-500" type="checkbox" name="answers[{{ $key }}][]" value="Other:" @checked($selectedOther)> {{ $otherLabel }}</label>
                                <input class="mt-1 w-full rounded-md border border-zinc-200 bg-white px-3 py-2 text-zinc-950 dark:border-white/10 dark:bg-zinc-950 dark:text-white"
                                    type="text"
                                    name="answers[{{ $key }}_other]"
                                    value="{{ $otherValue }}"
                                    placeholder="{{ $otherLabel }}"
                                    oninput="this.previousElementSibling.querySelector('input[type=checkbox]').checked = true">
                            @endif
                        </div>
                    @else
                        <input class="mt-3 w-full rounded-md border border-zinc-200 bg-white px-3 py-2 text-zinc-950 dark:border-white/10 dark:bg-zinc-950 dark:text-white" type="{{ in_array($type, ['email', 'number', 'date'], true) ? $type : 'text' }}" name="answers[{{ $key }}]" value="{{ $answer }}">
                    @endif
                </fieldset>
            @endforeach
        </div>

        <div class="mt-6 rounded-md border border-zinc-200 bg-zinc-50 p-3 text-xs text-zinc-500 dark:border-white/10 dark:bg-zinc-900 dark:text-zinc-400">
            <x-t
                en="By clicking 'Complete survey', you acknowledge that your responses will be used to improve our services. No personal data is shared with third parties. Only the organizer and beneficiaries have access."
            /><br>
            <x-t
                en="By clicking 'Complete survey', you acknowledge that your responses will be used to improve our services. No personal data is shared with third parties. Only the organizer and beneficiaries have access."
                th="การคลิก 'ส่งแบบสอบถาม' แสดงว่าคุณรับทราบว่าคำตอบของคุณจะถูกนำไปใช้เพื่อพัฒนาบริการของเรา ข้อมูลส่วนบุคคลจะไม่ถูกแชร์กับบุคคลที่สาม เฉพาะผู้จัดงานและผู้รับประโยชน์เท่านั้นที่สามารถเข้าถึงได้"
            />
        </div>

        <div class="mt-6 flex flex-wrap gap-3">
            @php
                $submitEn = match($survey->placement) {
                    'free_ticket_gate' => 'Complete survey & get free ticket',
                    'before_payment' => 'Complete survey & proceed to payment',
                    default => 'Complete survey',
                };
                $submitTh = match($survey->placement) {
                    'free_ticket_gate' => 'ส่งแบบสอบถามและรับตั๋วฟรี',
                    'before_payment' => 'ส่งแบบสอบถามและดำเนินการชำระเงิน',
                    default => 'ส่งแบบสอบถาม',
                };
            @endphp
            <button class="inline-flex items-center gap-2 rounded-md bg-emerald-400 px-4 py-3 font-semibold text-zinc-950" name="action" value="complete">
                <x-icon name="check" /><x-t :en="$submitEn" :th="$submitTh" />
            </button>
            <button class="inline-flex items-center gap-2 rounded-md border border-zinc-200 px-4 py-3 font-semibold text-zinc-700 dark:border-white/10 dark:text-zinc-200" name="action" value="draft">
                <x-icon name="save" />
                <!-- <x-t en="Save progress" th="บันทึกไว้ก่อน" /> -->
            </button>
        </div>
    </form>
</x-layouts.app>
