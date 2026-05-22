import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.data('scanner', () => ({
    code: '',
    message: '',
    ok: false,
    async submit(action) {
        this.message = 'Scanning... / กำลังสแกน...';

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
            this.message = 'Camera barcode detection is not supported in this browser. / เบราว์เซอร์นี้ยังไม่รองรับการสแกนบาร์โค้ดด้วยกล้อง';
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
    paymentMethod: 'qr_payment',
    couponCode: '',
    slipName: '',
    errorMessage: '',
    customerName: config.customerName || '',
    tickets: config.tickets,
    quantities: Object.fromEntries(config.tickets.map((ticket) => [ticket.id, 0])),
    holderNames: Object.fromEntries(config.tickets.map((ticket) => [ticket.id, []])),
    holderTouched: Object.fromEntries(config.tickets.map((ticket) => [ticket.id, []])),
    payment: config.payment,
    increment(ticketId) {
        this.quantities[ticketId] = Math.min(20, Number(this.quantities[ticketId] || 0) + 1);
        this.syncHolderNames(ticketId);
    },
    decrement(ticketId) {
        this.quantities[ticketId] = Math.max(0, Number(this.quantities[ticketId] || 0) - 1);
        this.syncHolderNames(ticketId);
    },
    syncHolderNames(ticketId) {
        const quantity = Number(this.quantities[ticketId] || 0);
        this.holderNames[ticketId] = this.holderNames[ticketId] || [];
        this.holderTouched[ticketId] = this.holderTouched[ticketId] || [];
        while (this.holderNames[ticketId].length < quantity) {
            this.holderNames[ticketId].push(this.customerName);
            this.holderTouched[ticketId].push(false);
        }
    },
    syncDefaultHolderNames() {
        this.tickets.forEach((ticket) => {
            this.syncHolderNames(ticket.id);
            this.holderNames[ticket.id].forEach((name, index) => {
                if (!this.holderTouched[ticket.id][index] || !String(name || '').trim()) {
                    this.holderNames[ticket.id][index] = this.customerName;
                    this.holderTouched[ticket.id][index] = false;
                }
            });
        });
    },
    markHolderTouched(ticketId, index) {
        this.holderTouched[ticketId] = this.holderTouched[ticketId] || [];
        this.holderTouched[ticketId][index] = true;
    },
    holderSlots(ticketId) {
        this.syncHolderNames(ticketId);

        return Array.from({ length: Number(this.quantities[ticketId] || 0) }, (_, index) => index);
    },
    subtotal() {
        return config.tickets.reduce((sum, ticket) => {
            return sum + (Number(this.quantities[ticket.id] || 0) * Number(ticket.price));
        }, 0);
    },
    activeCoupon() {
        const code = String(this.couponCode || '').trim().toUpperCase();

        if (!code) {
            return null;
        }

        return config.coupons.find((coupon) => coupon.code === code) || null;
    },
    applicableCoupons() {
        return config.coupons.filter((coupon) => this.eligibleSubtotal(coupon) > 0);
    },
    applyCoupon(code) {
        this.couponCode = String(code || '').trim().toUpperCase();
    },
    eligibleSubtotal(coupon) {
        return config.tickets.reduce((sum, ticket) => {
            if (coupon.ticket_type_id && Number(coupon.ticket_type_id) !== Number(ticket.id)) {
                return sum;
            }

            return sum + (Number(this.quantities[ticket.id] || 0) * Number(ticket.price));
        }, 0);
    },
    discount() {
        const coupon = this.activeCoupon();

        if (!coupon) {
            return 0;
        }

        if (coupon.scope === 'item') {
            return config.tickets.reduce((sum, ticket) => {
                if (coupon.ticket_type_id && Number(coupon.ticket_type_id) !== Number(ticket.id)) {
                    return sum;
                }

                const quantity = Number(this.quantities[ticket.id] || 0);
                const lineTotal = quantity * Number(ticket.price);
                const lineDiscount = coupon.type === 'percent'
                    ? Math.round(lineTotal * (Number(coupon.value) / 100))
                    : quantity * Number(coupon.value);

                return sum + Math.min(lineTotal, lineDiscount);
            }, 0);
        }

        const eligibleSubtotal = this.eligibleSubtotal(coupon);
        const discount = coupon.type === 'percent'
            ? Math.round(eligibleSubtotal * (Number(coupon.value) / 100))
            : Number(coupon.value);

        return Math.min(eligibleSubtotal, discount);
    },
    total() {
        return Math.max(0, this.subtotal() - this.discount());
    },
    paymentQrUrl() {
        return `/payments/events/${config.eventId}/qr?amount=${this.total()}`;
    },
    prepareSubmit(event) {
        this.errorMessage = '';
        this.syncDefaultHolderNames();

        if (this.subtotal() <= 0) {
            event.preventDefault();
            this.errorMessage = 'Please select at least one ticket. / กรุณาเลือกตั๋วอย่างน้อย 1 ใบ';
            return;
        }

        if (!event.target.reportValidity()) {
            event.preventDefault();
            this.errorMessage = 'Please complete all required fields. / กรุณากรอกข้อมูลที่จำเป็นให้ครบ';
        }
    },
}));

Alpine.data('adminTicketTypes', (config) => {
    const blankRow = () => ({
        id: '',
        name: '',
        description: '',
        price_thb: 0,
        capacity: 0,
        sale_starts_at: '',
        sale_ends_at: '',
        status: 'active',
    });

    return {
        rows: config.rows.length ? config.rows : [blankRow(), blankRow()],
        inactiveIds: [],
        blankRow,
        addRow() {
            this.rows.push(this.blankRow());
        },
        removeRow(index) {
            const row = this.rows[index];

            if (row?.id) {
                this.inactiveIds.push(row.id);
            }

            this.rows.splice(index, 1);

            if (this.rows.length === 0) {
                this.addRow();
            }
        },
    };
});

Alpine.data('lineLiffLogin', (config) => ({
    loading: false,
    message: '',
    liff: null,
    async init() {
        const liffId = config.liffId || import.meta.env.VITE_LINE_LIFF_ID;

        if (!liffId) {
            return;
        }

        try {
            this.liff = await this.loadSdk();
            await this.liff.init({ liffId });

            if (config.auto && this.liff.isInClient()) {
                await this.login();
                return;
            }

            if (this.liff.isLoggedIn() && sessionStorage.getItem('line_liff_login_pending') === '1') {
                await this.login();
            }
        } catch (error) {
            if (!config.auto) {
                this.message = 'LINE LIFF could not start. Please check the LIFF ID and endpoint URL. / ไม่สามารถเริ่ม LINE LIFF ได้ กรุณาตรวจสอบ LIFF ID และ Endpoint URL';
            }
        }
    },
    loadSdk() {
        if (window.liff) {
            return Promise.resolve(window.liff);
        }

        return new Promise((resolve, reject) => {
            const existingScript = document.querySelector('script[data-line-liff-sdk]');

            if (existingScript) {
                existingScript.addEventListener('load', () => resolve(window.liff));
                existingScript.addEventListener('error', reject);
                return;
            }

            const script = document.createElement('script');
            script.src = 'https://static.line-scdn.net/liff/edge/2/sdk.js';
            script.async = true;
            script.dataset.lineLiffSdk = 'true';
            script.onload = () => resolve(window.liff);
            script.onerror = reject;
            document.head.appendChild(script);
        });
    },
    async login() {
        this.loading = true;
        this.message = '';

        try {
            if (!this.liff) {
                await this.init();
            }

            if (!this.liff.isLoggedIn()) {
                sessionStorage.setItem('line_liff_login_pending', '1');
                this.liff.login({ redirectUri: window.location.href });
                return;
            }

            const idToken = this.liff.getIDToken();

            if (!idToken) {
                throw new Error('Missing LINE id token. / ไม่พบ LINE id token');
            }

            const profile = await this.liff.getProfile().catch(() => null);
            const response = await fetch(config.loginUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    Accept: 'application/json',
                },
                body: JSON.stringify({
                    id_token: idToken,
                    profile,
                    redirect: config.redirectUrl || config.profileUrl || window.location.href,
                }),
            });

            const payload = await response.json();

            if (!response.ok) {
                const errors = payload.errors || {};
                throw new Error(errors.line?.[0] || payload.message || 'LINE login failed. / เข้าสู่ระบบ LINE ไม่สำเร็จ');
            }

            sessionStorage.removeItem('line_liff_login_pending');
            window.location.href = payload.redirect || config.profileUrl;
        } catch (error) {
            this.loading = false;
            this.message = error.message || 'LINE login failed. Please try again. / เข้าสู่ระบบ LINE ไม่สำเร็จ กรุณาลองอีกครั้ง';
        }
    },
}));

Alpine.start();
