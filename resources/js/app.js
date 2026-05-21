import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.data('scanner', () => ({
    code: '',
    message: '',
    ok: false,
    async submit(action) {
        this.message = 'Scanning...';

        const response = await fetch('/admin/scanner', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                Accept: 'application/json',
            },
            body: JSON.stringify({ code: this.code, action }),
        });

        const payload = await response.json();
        this.ok = payload.ok;
        this.message = payload.ok
            ? `${payload.message} ${payload.ticket.holder} - ${payload.ticket.event}`
            : payload.message;
    },
    async startCamera() {
        if (!('BarcodeDetector' in window)) {
            this.ok = false;
            this.message = 'Camera barcode detection is not supported in this browser.';
            return;
        }

        const stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } });
        const video = this.$refs.video;
        video.classList.remove('hidden');
        video.srcObject = stream;

        const detector = new BarcodeDetector({ formats: ['qr_code', 'code_128'] });
        const loop = async () => {
            const codes = await detector.detect(video).catch(() => []);
            if (codes.length > 0) {
                this.code = codes[0].rawValue;
                stream.getTracks().forEach((track) => track.stop());
                video.classList.add('hidden');
                return;
            }
            requestAnimationFrame(loop);
        };
        loop();
    },
}));

Alpine.data('checkout', (config) => ({
    paymentMethod: 'bank_transfer',
    quantities: Object.fromEntries(config.tickets.map((ticket) => [ticket.id, 0])),
    payment: config.payment,
    total() {
        return config.tickets.reduce((sum, ticket) => {
            return sum + (Number(this.quantities[ticket.id] || 0) * Number(ticket.price));
        }, 0);
    },
    paymentQrUrl() {
        return `/payments/events/${config.eventId}/qr?amount=${this.total()}`;
    },
}));

Alpine.start();
