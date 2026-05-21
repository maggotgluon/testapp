<x-layouts.app title="Scanner">
    <div class="mx-auto max-w-2xl rounded-lg border border-zinc-200 dark:border-white/10 bg-white dark:bg-white/[0.04] p-6" x-data="scanner()">
        <h1 class="text-3xl font-semibold text-zinc-950 dark:text-white">Gate scanner</h1>
        <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">Paste a ticket URL or UUID. Browsers with BarcodeDetector can use the camera button.</p>
        <div class="mt-5 grid gap-3">
            <input x-model="code" class="rounded-md border border-zinc-200 dark:border-white/10 bg-white dark:bg-zinc-950 px-3 py-3 font-mono text-zinc-950 dark:text-white" placeholder="Ticket UUID or scanned URL">
            <div class="flex flex-wrap gap-2">
                <button @click="submit('check_in')" class="rounded-md bg-emerald-400 px-4 py-2 font-semibold text-zinc-950">Check in</button>
                <button @click="submit('check_out')" class="rounded-md border border-zinc-200 dark:border-white/10 px-4 py-2 font-semibold text-zinc-800 dark:text-zinc-100">Check out</button>
                <button @click="startCamera()" class="rounded-md border border-zinc-200 dark:border-white/10 px-4 py-2 text-zinc-800 dark:text-zinc-100" type="button">Camera</button>
            </div>
            <video x-ref="video" class="hidden aspect-video w-full rounded-lg bg-black" autoplay muted playsinline></video>
            <template x-if="message"><div class="rounded-md border border-zinc-200 dark:border-white/10 bg-zinc-50 dark:bg-zinc-900 p-4 text-sm" :class="ok ? 'text-emerald-700 dark:text-emerald-200' : 'text-rose-700 dark:text-rose-200'" x-text="message"></div></template>
        </div>
    </div>
</x-layouts.app>
