<x-layouts.app
    :title="$document['title']['en'].' / '.$document['title']['th']"
    :meta-description="$document['description']"
    :canonical-url="$document['url']"
>
    <article class="mx-auto max-w-4xl" x-data="{
        lang: window.TicketFlowLanguage?.current() || ((navigator.language || '').toLowerCase().startsWith('th') ? 'th' : 'en'),
        document: @js($document),
        localized(value) {
            const en = value.en || value.th || '';
            const th = value.th || value.en || '';

            if (this.lang === 'th') {
                return th;
            }

            if (this.lang === 'both') {
                return [value.en, value.th].filter(Boolean).join(' / ');
            }

            return en;
        },
        localizedHtml(value) {
            const en = value.en || value.th || '';
            const th = value.th || value.en || '';

            if (this.lang === 'th') {
                return th;
            }

            if (this.lang === 'both') {
                return [value.en, value.th].filter(Boolean).join('<hr class=\'my-6 border-zinc-200 dark:border-white/10\'>');
            }

            return en;
        },
        init() {
            window.addEventListener('ticketflow:language-change', (event) => {
                this.lang = event.detail.language;
            });
        },
    }">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <p class="text-sm font-semibold uppercase text-emerald-600 dark:text-emerald-300"><x-t en="Legal" th="เอกสารทางกฎหมาย" /></p>
                <h1 class="mt-3 text-4xl font-semibold text-zinc-950 dark:text-white" x-text="localized(document.title)"></h1>
                <p class="mt-3 text-sm text-zinc-500"><x-t en="Last updated: May 25, 2026" th="อัปเดตล่าสุด: 25 พฤษภาคม 2026" /></p>
            </div>
        </div>

        <nav class="mt-6 flex flex-wrap gap-2 text-sm">
            @foreach($documents as $legalDocument)
                <a class="inline-flex rounded-md border px-3 py-2 font-semibold {{ $legalDocument['key'] === $document['key'] ? 'border-emerald-400 bg-emerald-400/10 text-emerald-700 dark:text-emerald-200' : 'border-zinc-200 text-zinc-700 hover:border-emerald-300 dark:border-white/10 dark:text-zinc-200' }}" href="{{ $legalDocument['url'] }}">
                    <span x-text="localized(@js($legalDocument['title']))"></span>
                </a>
            @endforeach
        </nav>

        <div class="mt-6 rounded-lg border border-zinc-200 bg-white p-6 dark:border-white/10 dark:bg-white/[0.04]">
            <div class="legal-document max-w-none" x-html="localizedHtml(document.html)"></div>
        </div>
    </article>
</x-layouts.app>
