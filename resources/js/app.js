import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.data('scanner', (config = {}) => ({
    code: '',
    message: '',
    ok: false,
    currentTicket: null,
    quickMode: false,
    selectedAction: 'check_in',
    selectedEventId: '',
    guideOpen: false,
    ticketModalOpen: false,
    modalTicket: null,
    modalMessage: '',
    modalOk: false,
    cameraStream: null,
    cameraLoopActive: false,
    events: config.events || [],
    recentScans: (config.recentScans || []).map((scan, index) => ({ ...scan, clientId: `server-${index}` })),
    flash: '',
    scanning: false,
    lastScannedCode: '',
    lastScannedAt: 0,
    audioContext: null,
    init() {
        this.selectedEventId = this.events[0]?.id ? String(this.events[0].id) : '';
        this.$nextTick(() => this.$refs.codeInput?.focus());
    },
    async handleScan() {
        if (this.quickMode) {
            if (this.selectedAction === 'view') {
                await this.lookup();
                return;
            }

            await this.submit(this.selectedAction);
            return;
        }

        await this.lookup();
    },
    async lookup() {
        await this.requestScan(null);
    },
    async submit(action) {
        await this.requestScan(action);
    },
    async requestScan(action = null) {
        if (this.scanning) {
            return;
        }

        const code = String(this.code || '').trim();
        if (!code) {
            this.failFeedback('Please scan or enter a ticket UUID. / กรุณาสแกนหรือกรอก UUID ตั๋ว');
            return;
        }

        if (this.quickMode && !this.selectedEventId) {
            this.failFeedback('Please select an event for quick mode. / กรุณาเลือกอีเวนต์สำหรับโหมดเร็ว');
            return;
        }

        this.scanning = true;
        this.message = action ? 'Updating ticket... / กำลังอัปเดตตั๋ว...' : 'Looking up ticket... / กำลังค้นหาตั๋ว...';

        try {
            const response = await fetch('/admin/scanner', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    Accept: 'application/json',
                },
                body: JSON.stringify({
                    code,
                    action,
                    event_id: this.quickMode ? this.selectedEventId : null,
                }),
            });

            const payload = await response.json();
            this.ok = payload.ok;

            if (payload.ticket) {
                this.currentTicket = payload.ticket;
                this.showTicketModal(payload);
            }

            this.message = payload.ok && payload.ticket
                ? `${payload.message} ${payload.ticket.holder} - ${payload.ticket.event} (${this.statusLabel(payload.ticket.status)})`
                : payload.message;

            if (payload.ok) {
                this.successFeedback();
                this.addRecent(payload);
                this.code = '';
                this.$nextTick(() => this.$refs.codeInput?.focus());
            } else {
                this.failFeedback(payload.message, payload);
            }
        } catch (error) {
            this.failFeedback('Scanner request failed. Please try again. / สแกนไม่สำเร็จ กรุณาลองใหม่');
        } finally {
            this.scanning = false;
        }
    },
    async startCamera() {
        if (!('BarcodeDetector' in window)) {
            this.failFeedback('Camera barcode detection is not supported in this browser. / เบราว์เซอร์นี้ยังไม่รองรับการสแกนบาร์โค้ดด้วยกล้อง');
            return;
        }

        this.stopCamera();
        const stream = await navigator.mediaDevices.getUserMedia({
            video: {
                facingMode: 'environment',
                width: { ideal: 960, max: 1280 },
                height: { ideal: 540, max: 720 },
            },
        });
        const video = this.$refs.video;
        this.cameraStream = stream;
        this.cameraLoopActive = true;
        video.classList.remove('hidden');
        video.srcObject = stream;
        await video.play().catch(() => {});

        const detector = new BarcodeDetector({ formats: ['qr_code', 'code_128'] });
        const loop = async () => {
            if (!this.cameraLoopActive) {
                return;
            }

            const codes = await detector.detect(video).catch(() => []);
            if (codes.length > 0) {
                const rawValue = codes[0].rawValue;
                const now = Date.now();
                if (rawValue !== this.lastScannedCode || now - this.lastScannedAt > 2000) {
                    this.lastScannedCode = rawValue;
                    this.lastScannedAt = now;
                    this.code = rawValue;
                    await this.handleScan();
                }
                this.stopCamera();
                return;
            }
            window.setTimeout(loop, 220);
        };
        loop();
    },
    stopCamera() {
        this.cameraLoopActive = false;
        if (this.cameraStream) {
            this.cameraStream.getTracks().forEach((track) => track.stop());
            this.cameraStream = null;
        }

        const video = this.$refs.video;
        if (video) {
            video.srcObject = null;
            video.classList.add('hidden');
        }
    },
    addRecent(payload) {
        this.recentScans = [
            {
                ...payload,
                clientId: `scan-${Date.now()}-${Math.random().toString(16).slice(2)}`,
                scanned_at: payload.scanned_at || new Date().toLocaleTimeString('th-TH', { hour12: false }),
            },
            ...this.recentScans,
        ].slice(0, 20);
    },
    statusLabel(status) {
        return String(status || '').replaceAll('_', ' ');
    },
    canCheckIn() {
        return this.currentTicket?.status === 'approved';
    },
    canCheckOut() {
        return this.currentTicket?.status === 'checked_in';
    },
    canModalCheckIn() {
        return this.modalTicket?.status === 'approved';
    },
    canModalCheckOut() {
        return this.modalTicket?.status === 'checked_in';
    },
    showTicketModal(payload) {
        this.modalTicket = payload.ticket;
        this.modalMessage = payload.message || '';
        this.modalOk = Boolean(payload.ok);
        this.ticketModalOpen = true;
    },
    async modalSubmit(action) {
        if (!this.modalTicket?.uuid) {
            return;
        }

        this.code = this.modalTicket.uuid;
        await this.submit(action);
    },
    successFeedback() {
        this.ok = true;
        this.flashScreen('success');
        this.beep(880, 90, 'sine');
        this.vibrate([80]);
    },
    failFeedback(message, payload = null) {
        this.ok = false;
        this.message = message;
        if (payload?.ticket) {
            this.currentTicket = payload.ticket;
            this.addRecent(payload);
        } else if (String(this.code || '').trim()) {
            this.addRecent({
                ok: false,
                message,
                scanned_at: new Date().toLocaleTimeString('th-TH', { hour12: false }),
                ticket: null,
            });
        }
        this.flashScreen('error');
        this.beep(180, 160, 'square');
        this.vibrate([120, 60, 120]);
    },
    flashScreen(type) {
        this.flash = type;
        window.setTimeout(() => {
            this.flash = '';
        }, 280);
    },
    vibrate(pattern) {
        if ('vibrate' in navigator) {
            navigator.vibrate(pattern);
        }
    },
    beep(frequency, duration, type = 'sine') {
        try {
            this.audioContext = this.audioContext || new (window.AudioContext || window.webkitAudioContext)();
            const oscillator = this.audioContext.createOscillator();
            const gain = this.audioContext.createGain();
            oscillator.type = type;
            oscillator.frequency.value = frequency;
            gain.gain.value = 0.05;
            oscillator.connect(gain);
            gain.connect(this.audioContext.destination);
            oscillator.start();
            oscillator.stop(this.audioContext.currentTime + duration / 1000);
        } catch (error) {
            // Sound feedback is best-effort because some mobile browsers block audio.
        }
    },
}));

Alpine.data('checkout', (config) => ({
    paymentMethod: 'qr_payment',
    couponCode: '',
    slipName: '',
    errorMessage: '',
    validationAttempted: false,
    invalidSection: '',
    customerName: config.customerName || '',
    customerPhone: config.customerPhone || '',
    termsAccepted: false,
    tickets: config.tickets,
    promotions: config.promotions || [],
    quantities: Object.fromEntries(config.tickets.map((ticket) => [ticket.id, 0])),
    holderNames: Object.fromEntries(config.tickets.map((ticket) => [ticket.id, []])),
    holderTouched: Object.fromEntries(config.tickets.map((ticket) => [ticket.id, []])),
    payment: config.payment,
    increment(ticketId) {
        this.quantities[ticketId] = Math.min(20, Number(this.quantities[ticketId] || 0) + 1);
        this.syncHolderNames(ticketId);
        this.notifyCart();
    },
    decrement(ticketId) {
        this.quantities[ticketId] = Math.max(0, Number(this.quantities[ticketId] || 0) - 1);
        this.syncHolderNames(ticketId);
        this.notifyCart();
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
    cartQuantity() {
        return config.tickets.reduce((sum, ticket) => sum + Number(this.quantities[ticket.id] || 0), 0);
    },
    notifyCart() {
        window.dispatchEvent(new CustomEvent('checkout-cart-updated', {
            detail: { quantity: this.cartQuantity() },
        }));
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
    eligibleQuantity(promotion) {
        return config.tickets.reduce((sum, ticket) => {
            if (promotion.ticket_type_id && Number(promotion.ticket_type_id) !== Number(ticket.id)) {
                return sum;
            }

            return sum + Number(this.quantities[ticket.id] || 0);
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
    activePromotions() {
        return this.promotions.filter((promotion) => this.discountForPromotion(promotion, this.discount()) > 0);
    },
    promotionEligibleSubtotal(promotion) {
        return config.tickets.reduce((sum, ticket) => {
            if (promotion.ticket_type_id && Number(promotion.ticket_type_id) !== Number(ticket.id)) {
                return sum;
            }

            return sum + (Number(this.quantities[ticket.id] || 0) * Number(ticket.price));
        }, 0);
    },
    discountForPromotion(promotion, couponDiscount = this.discount()) {
        if (couponDiscount > 0 && promotion.combines_with_coupons === false) {
            return 0;
        }

        const minQuantity = Number(promotion.min_quantity || 1);
        if (this.eligibleQuantity(promotion) < minQuantity) {
            return 0;
        }

        const eligibleSubtotal = this.promotionEligibleSubtotal(promotion);
        let discount = 0;

        if (promotion.type === 'buy_x_get_y') {
            const buyQuantity = Math.max(1, Number(promotion.buy_quantity || 1));
            const getQuantity = Math.max(1, Number(promotion.get_quantity || 1));
            const groupSize = buyQuantity + getQuantity;
            const freeQuantity = Math.floor(this.eligibleQuantity(promotion) / groupSize) * getQuantity;
            const unitPrices = [];

            config.tickets.forEach((ticket) => {
                if (promotion.ticket_type_id && Number(promotion.ticket_type_id) !== Number(ticket.id)) {
                    return;
                }

                for (let index = 0; index < Number(this.quantities[ticket.id] || 0); index += 1) {
                    unitPrices.push(Number(ticket.price));
                }
            });

            discount = unitPrices.sort((a, b) => a - b).slice(0, freeQuantity).reduce((sum, price) => sum + price, 0);
        } else if (promotion.scope === 'item') {
            discount = config.tickets.reduce((sum, ticket) => {
                if (promotion.ticket_type_id && Number(promotion.ticket_type_id) !== Number(ticket.id)) {
                    return sum;
                }

                const quantity = Number(this.quantities[ticket.id] || 0);
                const lineTotal = quantity * Number(ticket.price);
                const lineDiscount = promotion.type === 'percent'
                    ? Math.round(lineTotal * (Number(promotion.value || 0) / 100))
                    : quantity * Number(promotion.value || 0);

                return sum + Math.min(lineTotal, lineDiscount);
            }, 0);
        } else {
            discount = promotion.type === 'percent'
                ? Math.round(eligibleSubtotal * (Number(promotion.value || 0) / 100))
                : Number(promotion.value || 0);
        }

        if (promotion.max_discount_thb) {
            discount = Math.min(discount, Number(promotion.max_discount_thb));
        }

        return Math.min(eligibleSubtotal, discount);
    },
    promotionDiscount() {
        const couponDiscount = this.discount();
        const discount = this.promotions.reduce((sum, promotion) => sum + this.discountForPromotion(promotion, couponDiscount), 0);

        return Math.min(Math.max(0, this.subtotal() - couponDiscount), discount);
    },
    total() {
        return Math.max(0, this.subtotal() - this.discount() - this.promotionDiscount());
    },
    paymentQrUrl() {
        return `/payments/events/${config.eventId}/qr?amount=${this.total()}`;
    },
    canSubmitOrder() {
        return this.subtotal() > 0
            && String(this.customerName || '').trim().length > 0
            && String(this.customerPhone || '').trim().length > 0
            && Boolean(this.slipName)
            && this.termsAccepted;
    },
    firstMissingSection() {
        if (this.subtotal() <= 0) {
            return 'tickets';
        }

        if (!String(this.customerName || '').trim() || !String(this.customerPhone || '').trim()) {
            return 'customer';
        }

        if (!this.slipName) {
            return 'payment';
        }

        if (!this.termsAccepted) {
            return 'legal';
        }

        return '';
    },
    highlightMissingSection() {
        this.validationAttempted = true;
        this.invalidSection = this.firstMissingSection();
        this.errorMessage = 'Please complete the highlighted section before submitting. / กรุณากรอกส่วนที่ไฮไลต์ให้ครบก่อนส่งคำสั่งซื้อ';

        this.$nextTick(() => {
            document.querySelector(`[data-checkout-section="${this.invalidSection}"]`)?.scrollIntoView({ behavior: 'smooth', block: 'center' });
        });
    },
    guardSubmit(event) {
        if (this.canSubmitOrder()) {
            return;
        }

        event.preventDefault();
        this.highlightMissingSection();
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

Alpine.data('floatingReserve', () => ({
    inCheckout: false,
    cartQuantity: 0,
    init() {
        const checkout = document.querySelector('#checkout');

        if (!checkout || !('IntersectionObserver' in window)) {
            return;
        }

        const observer = new IntersectionObserver(([entry]) => {
            this.inCheckout = entry.isIntersecting;
        }, {
            rootMargin: '-20% 0px -45% 0px',
            threshold: 0.01,
        });

        observer.observe(checkout);
        window.addEventListener('checkout-cart-updated', (event) => {
            this.cartQuantity = Number(event.detail?.quantity || 0);
        });
    },
    visible() {
        return !this.inCheckout && this.cartQuantity < 1;
    },
}));

Alpine.data('eventCountdown', (config) => ({
    label: 'Event starts in / เริ่มงานในอีก',
    days: '00',
    hours: '00',
    minutes: '00',
    seconds: '00',
    timer: null,
    init() {
        this.update();
        this.timer = window.setInterval(() => this.update(), 1000);
    },
    update() {
        const target = new Date(config.startsAt).getTime();
        const diff = target - Date.now();

        if (diff <= 0) {
            this.label = 'Event has started / อีเวนต์เริ่มแล้ว';
            this.days = this.hours = this.minutes = this.seconds = '00';
            if (this.timer) {
                window.clearInterval(this.timer);
            }
            return;
        }

        this.days = String(Math.floor(diff / 86400000)).padStart(2, '0');
        this.hours = String(Math.floor((diff % 86400000) / 3600000)).padStart(2, '0');
        this.minutes = String(Math.floor((diff % 3600000) / 60000)).padStart(2, '0');
        this.seconds = String(Math.floor((diff % 60000) / 1000)).padStart(2, '0');
    },
}));

Alpine.data('webPushSettings', () => ({
    supported: false,
    subscribed: false,
    message: 'Checking notification support... / กำลังตรวจสอบการรองรับ...',
    registration: null,
    async init() {
        this.supported = 'serviceWorker' in navigator && 'PushManager' in window && 'Notification' in window && Boolean(document.querySelector('meta[name="webpush-public-key"]')?.content);

        if (!this.supported) {
            this.message = 'Web Push is not available on this device or VAPID keys are missing. / อุปกรณ์นี้ยังไม่รองรับ Web Push หรือยังไม่ได้ตั้งค่า VAPID';
            return;
        }

        this.registration = await navigator.serviceWorker.register('/sw.js');
        const subscription = await this.registration.pushManager.getSubscription();
        this.subscribed = Boolean(subscription);
        this.message = this.subscribed
            ? 'Web Push is enabled on this device. / เปิด Web Push บนอุปกรณ์นี้แล้ว'
            : 'Web Push is ready. / พร้อมเปิด Web Push';
    },
    async subscribe() {
        if (!this.supported) {
            return;
        }

        const permission = await Notification.requestPermission();

        if (permission !== 'granted') {
            this.message = 'Notification permission was not granted. / ยังไม่ได้อนุญาตการแจ้งเตือน';
            return;
        }

        const subscription = await this.registration.pushManager.subscribe({
            userVisibleOnly: true,
            applicationServerKey: urlBase64ToUint8Array(document.querySelector('meta[name="webpush-public-key"]').content),
        });

        await fetch(document.querySelector('meta[name="push-subscribe-url"]').content, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                Accept: 'application/json',
            },
            body: JSON.stringify(subscription.toJSON()),
        });

        this.subscribed = true;
        this.message = 'Web Push is enabled on this device. / เปิด Web Push บนอุปกรณ์นี้แล้ว';
    },
    async unsubscribe() {
        const subscription = await this.registration?.pushManager.getSubscription();

        if (!subscription) {
            this.subscribed = false;
            return;
        }

        await fetch(document.querySelector('meta[name="push-unsubscribe-url"]').content, {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                Accept: 'application/json',
            },
            body: JSON.stringify({ endpoint: subscription.endpoint }),
        });

        await subscription.unsubscribe();
        this.subscribed = false;
        this.message = 'Web Push is off on this device. / ปิด Web Push บนอุปกรณ์นี้แล้ว';
    },
}));

Alpine.data('adminTicketTypes', (config) => {
    const blankRow = () => ({
        id: '',
        name: '',
        description: '',
        price_thb: 0,
        full_price_thb: '',
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

if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/sw.js').catch(() => {});
    });
}

Alpine.start();

function urlBase64ToUint8Array(base64String) {
    const padding = '='.repeat((4 - base64String.length % 4) % 4);
    const base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
    const rawData = window.atob(base64);
    const outputArray = new Uint8Array(rawData.length);

    for (let i = 0; i < rawData.length; ++i) {
        outputArray[i] = rawData.charCodeAt(i);
    }

    return outputArray;
}
