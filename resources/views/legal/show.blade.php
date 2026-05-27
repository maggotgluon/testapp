<x-layouts.app
    :title="$document['title']['en'].' / '.$document['title']['th']"
    :meta-description="$document['description']"
    :canonical-url="$document['url']"
>
    <article class="mx-auto max-w-4xl" x-data="{
        lang: (navigator.language || '').toLowerCase().startsWith('th') ? 'th' : 'en',
        document: @js($document),
    }">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <p class="text-sm font-semibold uppercase text-emerald-600 dark:text-emerald-300">Legal / เอกสารทางกฎหมาย</p>
                <h1 class="mt-3 text-4xl font-semibold text-zinc-950 dark:text-white" x-text="document.title[lang]"></h1>
                <p class="mt-3 text-sm text-zinc-500">Last updated: May 25, 2026 / อัปเดตล่าสุด: 25 พฤษภาคม 2026</p>
            </div>
            <div class="inline-grid grid-cols-2 overflow-hidden rounded-md border border-zinc-200 text-sm font-semibold dark:border-white/10">
                <button class="px-4 py-2" type="button" :class="lang === 'en' ? 'bg-emerald-400 text-zinc-950' : 'text-zinc-700 hover:bg-zinc-100 dark:text-zinc-200 dark:hover:bg-white/10'" @click="lang = 'en'">English</button>
                <button class="px-4 py-2" type="button" :class="lang === 'th' ? 'bg-emerald-400 text-zinc-950' : 'text-zinc-700 hover:bg-zinc-100 dark:text-zinc-200 dark:hover:bg-white/10'" @click="lang = 'th'">ไทย</button>
            </div>
        </div>

        <nav class="mt-6 flex flex-wrap gap-2 text-sm">
            @foreach($documents as $legalDocument)
                <a class="inline-flex rounded-md border px-3 py-2 font-semibold {{ $legalDocument['key'] === $document['key'] ? 'border-emerald-400 bg-emerald-400/10 text-emerald-700 dark:text-emerald-200' : 'border-zinc-200 text-zinc-700 hover:border-emerald-300 dark:border-white/10 dark:text-zinc-200' }}" href="{{ $legalDocument['url'] }}">
                    <span x-text="@js($legalDocument['title'])[lang]"></span>
                </a>
            @endforeach
        </nav>

        <div class="mt-6 rounded-lg border border-zinc-200 bg-white p-6 dark:border-white/10 dark:bg-white/[0.04]">
            <div class="legal-document max-w-none" x-html="document.html[lang]"></div>
        </div>
    </article>
</x-layouts.app>
