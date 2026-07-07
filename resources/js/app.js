import Alpine from 'alpinejs';

window.Alpine = Alpine;

const uiLanguage = (() => {
    const storageKey = 'ticketflow.uiLanguage';
    const supported = ['en', 'th', 'both'];
    const thaiPattern = /[\u0E00-\u0E7F]/;
    const localizedSelector = '[data-i18n-auto], [data-i18n-en], [data-i18n-th]';
    const skipSelector = [
        'script',
        'style',
        'svg',
        'canvas',
        'input',
        'textarea',
        'code',
        'pre',
        '[contenteditable="true"]',
        localizedSelector,
        '[data-i18n-skip]',
        '[data-i18n-skip] *',
    ].join(',');

    const deviceLanguage = () => {
        const language = (navigator.language || '').toLowerCase();

        if (language.startsWith('th')) {
            return 'th';
        }

        if (language.startsWith('en')) {
            return 'en';
        }

        return 'both';
    };

    const savedLanguage = () => {
        try {
            return localStorage.getItem(storageKey);
        } catch (error) {
            return null;
        }
    };

    const saveLanguage = (language) => {
        try {
            localStorage.setItem(storageKey, language);
        } catch (error) {
            // Some in-app browsers can block localStorage; the current page can still switch.
        }
    };

    const current = () => {
        const saved = savedLanguage();

        return supported.includes(saved) ? saved : deviceLanguage();
    };

    const split = (value) => {
        if (!value || !value.includes(' / ')) {
            return null;
        }

        const match = value.match(/^(\s*)(.*?)(\s*)$/s);
        const body = match?.[2] || value.trim();
        const parts = body.split(/\s+\/\s+/);

        if (parts.length < 2) {
            return null;
        }

        const thaiIndex = parts.findIndex((part) => thaiPattern.test(part));

        if (thaiIndex < 1) {
            return null;
        }

        return {
            prefix: match?.[1] || '',
            suffix: match?.[3] || '',
            en: parts.slice(0, thaiIndex).join(' / ').trim(),
            th: parts.slice(thaiIndex).join(' / ').trim(),
        };
    };

    const localized = (parts, language = current()) => {
        if (!parts) {
            return null;
        }

        const en = String(parts.en || '').trim();
        const th = String(parts.th || '').trim();
        const fallback = en || th;
        const text = language === 'th'
            ? (th || fallback)
            : (language === 'en' ? (en || fallback) : [en, th].filter(Boolean).join(' / '));

        return `${parts.prefix}${text}${parts.suffix}`;
    };

    const format = (value, language = current()) => {
        if (!value) {
            return '';
        }

        if (typeof value === 'string') {
            const parts = split(value);

            return parts ? localized(parts, language) : value;
        }

        return localized({
            prefix: value.prefix || '',
            suffix: value.suffix || '',
            en: value.en,
            th: value.th,
        }, language) || '';
    };

    const updateNode = (node, language = current()) => {
        if (!node.dataset?.i18nEn && !node.dataset?.i18nTh) {
            return;
        }

        node.textContent = localized({
            prefix: node.dataset.i18nPrefix || '',
            suffix: node.dataset.i18nSuffix || '',
            en: node.dataset.i18nEn,
            th: node.dataset.i18nTh,
        }, language);
    };

    const localizeAttribute = (element, attribute, language = current()) => {
        if (element.closest?.(skipSelector)) {
            return;
        }

        const originalKey = `i18n${attribute.replace(/(^|-)([a-z])/g, (_, __, char) => char.toUpperCase())}`;
        const original = element.dataset?.[originalKey] || element.getAttribute(attribute);
        const parts = split(original);

        if (!parts) {
            return;
        }

        element.dataset[originalKey] = `${parts.prefix}${parts.en} / ${parts.th}${parts.suffix}`;
        element.setAttribute(attribute, localized(parts, language));
    };

    const processTextNode = (node, language = current()) => {
        if (!node.textContent?.trim() || node.parentElement?.closest(skipSelector)) {
            return;
        }

        const parts = split(node.textContent);

        if (!parts) {
            return;
        }

        const span = document.createElement('span');
        span.dataset.i18nAuto = 'true';
        span.dataset.i18nPrefix = parts.prefix;
        span.dataset.i18nSuffix = parts.suffix;
        span.dataset.i18nEn = parts.en;
        span.dataset.i18nTh = parts.th;
        span.textContent = localized(parts, language);
        node.replaceWith(span);
    };

    const processElement = (root = document.body, language = current()) => {
        if (!root) {
            return;
        }

        if (root.nodeType === Node.ELEMENT_NODE && root.matches?.(localizedSelector)) {
            updateNode(root, language);
            return;
        }

        if (root.closest?.(skipSelector)) {
            return;
        }

        if (root.nodeType === Node.TEXT_NODE) {
            processTextNode(root, language);
            return;
        }

        if (root.nodeType !== Node.ELEMENT_NODE) {
            return;
        }

        ['placeholder', 'title', 'aria-label', 'alt'].forEach((attribute) => {
            if (root.hasAttribute?.(attribute)) {
                localizeAttribute(root, attribute, language);
            }
        });

        const walker = document.createTreeWalker(root, NodeFilter.SHOW_TEXT, {
            acceptNode(node) {
                return node.parentElement?.closest(skipSelector)
                    ? NodeFilter.FILTER_REJECT
                    : NodeFilter.FILTER_ACCEPT;
            },
        });
        const textNodes = [];
        let node = walker.nextNode();

        while (node) {
            textNodes.push(node);
            node = walker.nextNode();
        }

        textNodes.forEach((textNode) => processTextNode(textNode, language));
        root.querySelectorAll?.(localizedSelector).forEach((node) => updateNode(node, language));
        root.querySelectorAll?.('[placeholder], [title], [aria-label], [alt]').forEach((element) => {
            ['placeholder', 'title', 'aria-label', 'alt'].forEach((attribute) => {
                if (element.hasAttribute(attribute)) {
                    localizeAttribute(element, attribute, language);
                }
            });
        });
    };

    const apply = (language = current()) => {
        document.documentElement.dataset.uiLang = language;
        document.documentElement.lang = language === 'th' ? 'th' : 'en';
        document.querySelectorAll('[data-language-switcher]').forEach((select) => {
            select.value = language;
        });
        document.querySelectorAll('[data-language-option]').forEach((button) => {
            const active = button.dataset.languageOption === language;
            button.dataset.active = active ? 'true' : 'false';
            button.setAttribute('aria-pressed', active ? 'true' : 'false');
        });
        processElement(document.body, language);
        window.dispatchEvent(new CustomEvent('ticketflow:language-change', {
            detail: { language },
        }));
    };

    const set = (language) => {
        const next = supported.includes(language) ? language : deviceLanguage();
        saveLanguage(next);
        apply(next);
    };

    const init = () => {
        apply();
        document.addEventListener('change', (event) => {
            const switcher = event.target.closest?.('[data-language-switcher]');

            if (switcher) {
                set(switcher.value);
            }
        });
        document.addEventListener('click', (event) => {
            const option = event.target.closest?.('[data-language-option]');

            if (option) {
                set(option.dataset.languageOption);
            }
        });

        const observer = new MutationObserver((mutations) => {
            for (const mutation of mutations) {
                mutation.addedNodes.forEach((node) => processElement(node));
            }
        });
        observer.observe(document.body, { childList: true, subtree: true });
    };

    return { init, set, current, apply, format };
})();

window.TicketFlowLanguage = uiLanguage;

const initialUiLanguage = uiLanguage.current();
document.documentElement.dataset.uiLang = initialUiLanguage;
document.documentElement.lang = initialUiLanguage === 'th' ? 'th' : 'en';

Alpine.data('scanner', (config = {}) => ({
    code: '',
    message: '',
    ok: false,
    currentTicket: null,
    quickMode: false,
    quickSettingsOpen: false,
    scanFiltersOpen: false,
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
    recentLimit: Number(config.recentLimit || 20),
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
            this.failFeedback(TicketFlowLanguage.format({ en: 'Please scan or enter a ticket UUID.', th: 'กรุณาสแกนหรือกรอก UUID ตั๋ว' }));
            return;
        }

        if (this.quickMode && !this.selectedEventId) {
            this.failFeedback(TicketFlowLanguage.format({ en: 'Please select an event for quick mode.', th: 'กรุณาเลือกอีเวนต์สำหรับโหมดเร็ว' }));
            return;
        }

        this.scanning = true;
        this.message = action
            ? TicketFlowLanguage.format({ en: 'Updating ticket...', th: 'กำลังอัปเดตตั๋ว...' })
            : TicketFlowLanguage.format({ en: 'Looking up ticket...', th: 'กำลังค้นหาตั๋ว...' });

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
            this.failFeedback(TicketFlowLanguage.format({ en: 'Scanner request failed. Please try again.', th: 'สแกนไม่สำเร็จ กรุณาลองใหม่' }));
        } finally {
            this.scanning = false;
        }
    },
    async startCamera() {
        if (!('BarcodeDetector' in window)) {
            this.failFeedback(TicketFlowLanguage.format({ en: 'Camera barcode detection is not supported in this browser.', th: 'เบราว์เซอร์นี้ยังไม่รองรับการสแกนบาร์โค้ดด้วยกล้อง' }));
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
        ].slice(0, this.recentLimit);
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
    paymentMethods: config.paymentMethods?.length ? config.paymentMethods : ['qr_payment'],
    paymentOptions: (config.paymentOptions?.length ? config.paymentOptions : (config.paymentMethods || ['qr_payment']).map((method) => ({ key: method, method, label: null })))
        .map((option) => ({ ...option, label: TicketFlowLanguage.format(option.label) || option.label })),
    paymentAccountKey: (config.paymentOptions?.[0]?.key) || (config.paymentMethods?.[0]) || 'qr_payment',
    paymentMethod: (config.paymentOptions?.[0]?.method) || (config.paymentMethods?.length ? config.paymentMethods : ['qr_payment'])[0],
    couponCode: '',
    couponApplied: false,
    couponMessage: '',
    slipName: '',
    slipPreviewUrl: '',
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
    beamEnabled: config.beamEnabled || false,
    beamFeeBehavior: config.beamFeeBehavior || 'merchant_absorb',
    beamFeePercent: config.beamFeePercent || 0,
    paymentMethodLabel(method) {
        return {
            qr_payment: TicketFlowLanguage.format({ en: 'QR payment', th: 'ชำระด้วย QR' }),
            bank_transfer: TicketFlowLanguage.format({ en: 'Direct bank transfer', th: 'โอนผ่านธนาคาร' }),
            cash: TicketFlowLanguage.format({ en: 'Cash sale', th: 'ชำระเงินสด' }),
            beam: TicketFlowLanguage.format({ en: 'Beam Checkout', th: 'ชำระด้วย Beam' }),
        }[method] || method;
    },
    selectedPayment() {
        return this.paymentOptions.find((option) => option.key === this.paymentAccountKey) || this.paymentOptions[0] || { method: this.paymentMethod };
    },
    syncPaymentMethod() {
        this.paymentMethod = this.selectedPayment().method || this.paymentMethod;
    },
    setSlipPreview(event) {
        const file = event.target.files?.[0] || null;
        this.slipName = file?.name || '';

        if (this.slipPreviewUrl) {
            URL.revokeObjectURL(this.slipPreviewUrl);
        }

        this.slipPreviewUrl = file ? URL.createObjectURL(file) : '';
    },
    increment(ticketId) {
        const ticket = this.tickets.find((t) => t.id === ticketId);
        const maxQty = (ticket && Number(ticket.price) === 0) ? 1 : 20;
        this.quantities[ticketId] = Math.min(maxQty, Number(this.quantities[ticketId] || 0) + 1);
        this.syncHolderNames(ticketId);
        this.notifyCart(ticketId);
    },
    decrement(ticketId) {
        this.quantities[ticketId] = Math.max(0, Number(this.quantities[ticketId] || 0) - 1);
        this.syncHolderNames(ticketId);
        this.notifyCart(ticketId);
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
    notifyCart(changedTicketId) {
        if (changedTicketId) {
            const changedTicket = this.tickets.find((t) => t.id === changedTicketId);
            if (changedTicket && Number(changedTicket.price) === 0) {
                if (Number(this.quantities[changedTicketId] || 0) > 0) {
                    this.quantities[changedTicketId] = 1;
                    this.syncHolderNames(changedTicketId);
                    this.tickets.forEach((ticket) => {
                        if (ticket.id !== changedTicketId && Number(ticket.price) === 0) {
                            this.quantities[ticket.id] = 0;
                            this.syncHolderNames(ticket.id);
                        }
                    });
                }
            }
        } else {
            let freeSelectedId = null;
            this.tickets.forEach((ticket) => {
                if (Number(ticket.price) === 0 && Number(this.quantities[ticket.id] || 0) > 0) {
                    if (freeSelectedId === null) {
                        freeSelectedId = ticket.id;
                        this.quantities[ticket.id] = 1;
                        this.syncHolderNames(ticket.id);
                    } else {
                        this.quantities[ticket.id] = 0;
                        this.syncHolderNames(ticket.id);
                    }
                }
            });
        }

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
        return config.coupons.filter((coupon) => coupon.show_on_checkout !== false && this.eligibleSubtotal(coupon) > 0);
    },
    applyCoupon(code) {
        this.couponCode = String(code || '').trim().toUpperCase();
        this.applyTypedCoupon();
    },
    applyTypedCoupon() {
        const code = String(this.couponCode || '').trim().toUpperCase();
        this.couponCode = code;
        const coupon = this.activeCoupon();

        if (!code) {
            this.couponApplied = false;
            this.couponMessage = TicketFlowLanguage.format({ en: 'Please enter a coupon code.', th: 'กรุณากรอกรหัสคูปอง' });
            return;
        }

        if (!coupon) {
            this.couponApplied = false;
            this.couponMessage = TicketFlowLanguage.format({ en: 'Coupon not found or not available for this event.', th: 'ไม่พบคูปองหรือคูปองใช้กับอีเวนต์นี้ไม่ได้' });
            return;
        }

        const amount = this.discount();
        if (amount <= 0) {
            this.couponApplied = false;
            this.couponMessage = TicketFlowLanguage.format({ en: 'Coupon is valid, but conditions are not met yet.', th: 'คูปองถูกต้อง แต่ยังไม่เข้าเงื่อนไข' });
            return;
        }

        this.couponApplied = true;
        this.couponMessage = TicketFlowLanguage.format({
            en: `Coupon applied: THB ${amount.toLocaleString()} discount.`,
            th: `ใช้คูปองแล้ว ลด THB ${amount.toLocaleString()}`,
        });
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
    activePromotionNames() {
        return this.activePromotions().map((promotion) => promotion.name).filter(Boolean).join(', ');
    },
    visibleActivePromotions() {
        return this.activePromotions().filter((promotion) => promotion.show_on_event_page !== false);
    },
    promotionThreshold(promotion) {
        if (promotion.type === 'buy_x_get_y') {
            return Math.max(1, Number(promotion.buy_quantity || 1)) + Math.max(1, Number(promotion.get_quantity || 1));
        }

        return Number(promotion.min_quantity || 1);
    },
    promotionConditionText(promotion) {
        const conditions = [];
        const threshold = this.promotionThreshold(promotion);

        if (threshold > 1) {
            conditions.push(TicketFlowLanguage.format({
                en: `Requires ${threshold} ticket${threshold === 1 ? '' : 's'}`,
                th: `ต้องซื้ออย่างน้อย ${threshold} ใบ`,
            }));
        }

        if (promotion.max_discount_thb) {
            conditions.push(TicketFlowLanguage.format({
                en: `Maximum discount THB ${Number(promotion.max_discount_thb).toLocaleString()}`,
                th: `ส่วนลดสูงสุด THB ${Number(promotion.max_discount_thb).toLocaleString()}`,
            }));
        }

        if (promotion.usage_limit) {
            const remaining = Math.max(0, Number(promotion.usage_limit) - Number(promotion.used_count || 0));
            conditions.push(TicketFlowLanguage.format({
                en: `${remaining.toLocaleString()} use${remaining === 1 ? '' : 's'} remaining`,
                th: `เหลือสิทธิ์ใช้งาน ${remaining.toLocaleString()} ครั้ง`,
            }));
        }

        if (promotion.combines_with_coupons === false) {
            conditions.push(TicketFlowLanguage.format({ en: 'Cannot combine with coupons', th: 'ใช้ร่วมกับคูปองไม่ได้' }));
        }

        return conditions.join(' · ');
    },
    promotionUnlockText(promotion) {
        if (promotion.type === 'buy_x_get_y') {
            const getQuantity = Math.max(1, Number(promotion.get_quantity || 1));
            return TicketFlowLanguage.format({
                en: `Buy 1 more to get ${getQuantity} free with ${promotion.name}.`,
                th: `ซื้อเพิ่มอีก 1 ใบ เพื่อรับฟรี ${getQuantity} ใบจาก ${promotion.name}`,
            });
        }

        return TicketFlowLanguage.format({
            en: `Buy 1 more to unlock ${promotion.name}.`,
            th: `ซื้อเพิ่มอีก 1 ใบเพื่อใช้โปรโมชัน ${promotion.name}`,
        });
    },
    promotionHints() {
        return this.promotions
            .filter((promotion) => promotion.show_on_event_page !== false && this.discountForPromotion(promotion, this.discount()) <= 0)
            .map((promotion) => {
                const currentQuantity = this.eligibleQuantity(promotion);
                const threshold = this.promotionThreshold(promotion);
                const needed = Math.max(0, threshold - currentQuantity);
                if (needed !== 1) {
                    return null;
                }

                return {
                    id: promotion.id,
                    text: this.promotionUnlockText(promotion),
                    condition: this.promotionConditionText(promotion),
                };
            })
            .filter(Boolean);
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
        return `/payments/events/${config.eventId}/qr?amount=${this.total()}&account=${encodeURIComponent(this.paymentAccountKey || '')}`;
    },
    slipRequired() {
        return this.total() > 0 && !['cash', 'beam'].includes(this.paymentMethod);
    },
    paymentInstructions() {
        if (this.total() === 0) {
            return TicketFlowLanguage.format({ en: 'This order is free. Tickets will be activated automatically.', th: 'ออเดอร์นี้ฟรี ตั๋วจะเปิดใช้งานอัตโนมัติ' });
        }

        if (this.paymentMethod === 'cash') {
            return TicketFlowLanguage.format({ en: 'Cash sale does not require a slip. Admin approval will activate tickets.', th: 'ชำระเงินสดไม่ต้องแนบสลิป แอดมินจะอนุมัติเพื่อเปิดใช้งานตั๋ว' });
        }

        if (this.paymentMethod === 'beam') {
            return TicketFlowLanguage.format({ en: 'Complete payment via Beam Checkout. Tickets will be activated automatically after payment.', th: 'ชำระเงินผ่าน Beam Checkout ตั๋วจะเปิดใช้งานอัตโนมัติหลังชำระเงิน' });
        }

        return this.selectedPayment().instructions || this.payment.instructions || TicketFlowLanguage.format({ en: 'Scan/pay first, then upload the completed bank payment slip image. Admin approval will activate tickets.', th: 'สแกนและชำระเงินก่อน แล้วอัปโหลดรูปสลิปจากธนาคาร แอดมินจะตรวจสอบและอนุมัติตั๋ว' });
    },
    canSubmitOrder() {
        return this.cartQuantity() > 0
            && String(this.customerName || '').trim().length > 0
            && String(this.customerPhone || '').trim().length > 0
            && (!this.slipRequired() || Boolean(this.slipName))
            && this.termsAccepted;
    },
    firstMissingSection() {
        if (this.cartQuantity() <= 0) {
            return 'tickets';
        }

        if (!String(this.customerName || '').trim() || !String(this.customerPhone || '').trim()) {
            return 'customer';
        }

        if (this.slipRequired() && !this.slipName) {
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
        this.errorMessage = TicketFlowLanguage.format({ en: 'Please complete the highlighted section before submitting.', th: 'กรุณากรอกส่วนที่ไฮไลต์ให้ครบก่อนส่งคำสั่งซื้อ' });

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

        if (this.cartQuantity() <= 0) {
            event.preventDefault();
            this.errorMessage = TicketFlowLanguage.format({ en: 'Please select at least one ticket.', th: 'กรุณาเลือกตั๋วอย่างน้อย 1 ใบ' });
            return;
        }

        if (this.total() === 0 && config.freeApprovalSurveyUrl) {
            event.preventDefault();
            window.location.href = config.freeApprovalSurveyUrl;
            return;
        }

        if (!event.target.reportValidity()) {
            event.preventDefault();
            this.errorMessage = TicketFlowLanguage.format({ en: 'Please complete all required fields.', th: 'กรุณากรอกข้อมูลที่จำเป็นให้ครบ' });
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
    label: TicketFlowLanguage.format({ en: 'Event starts in', th: 'เริ่มงานในอีก' }),
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
            this.label = TicketFlowLanguage.format({ en: 'Event has started', th: 'อีเวนต์เริ่มแล้ว' });
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

Alpine.data('ticketExport', (config) => ({
    previewOpen: false,
    previewUrl: '',
    previewLoading: false,
    async renderCanvas() {
        const canvas = document.createElement('canvas');
        canvas.width = 1080;
        canvas.height = 1920;
        const ctx = canvas.getContext('2d');

        ctx.fillStyle = '#ffffff';
        ctx.fillRect(0, 0, canvas.width, canvas.height);

        await this.drawHero(ctx);
        await this.drawText(ctx);

        if (config.active && config.qrUrl) {
            await this.drawQr(ctx);
        } else {
            this.drawInactiveNotice(ctx);
        }

        return canvas;
    },
    async previewPng() {
        this.previewLoading = true;
        const canvas = await this.renderCanvas();
        this.previewUrl = canvas.toDataURL('image/png');
        this.previewOpen = true;
        this.previewLoading = false;
    },
    closePreview() {
        this.previewOpen = false;
    },
    async downloadPng() {
        const canvas = await this.renderCanvas();
        this.downloadCanvas(canvas);
    },
    printPdf() {
        window.print();
    },
    async drawHero(ctx) {
        const heroHeight = 720;
        const gradient = ctx.createLinearGradient(0, 0, 1080, heroHeight);
        gradient.addColorStop(0, '#10b981');
        gradient.addColorStop(0.5, '#0ea5e9');
        gradient.addColorStop(1, '#18181b');
        ctx.fillStyle = gradient;
        ctx.fillRect(0, 0, 1080, heroHeight);

        if (config.imageUrl) {
            const image = await this.loadImage(config.imageUrl).catch(() => null);
            if (image) {
                this.coverImage(ctx, image, 0, 0, 1080, heroHeight);
            }
        }

        const shade = ctx.createLinearGradient(0, 420, 0, heroHeight);
        shade.addColorStop(0, 'rgba(9,9,11,0)');
        shade.addColorStop(1, 'rgba(9,9,11,0.88)');
        ctx.fillStyle = shade;
        ctx.fillRect(0, 420, 1080, heroHeight - 420);

        ctx.fillStyle = 'rgba(24, 24, 27, 0.78)';
        this.roundRect(ctx, 48, 550, 984, 240, 28, true, false);

        ctx.fillStyle = '#ffffff';
        ctx.font = '700 32px sans-serif';
        ctx.fillText(String(config.ticketType || '').toUpperCase(), 72, 605);
        ctx.font = '700 58px sans-serif';
        this.wrapText(ctx, config.eventName, 72, 670, 900, 66, 2);
    },
    async drawText(ctx) {
        const bodyGradient = ctx.createLinearGradient(0, 720, 1080, 1920);
        bodyGradient.addColorStop(0, '#0f766e');
        bodyGradient.addColorStop(0.48, '#312e81');
        bodyGradient.addColorStop(1, '#064e3b');
        ctx.fillStyle = bodyGradient;
        ctx.fillRect(0, 720, 1080, 1240);

        if (config.imageUrl) {
            const image = await this.loadImage(config.imageUrl).catch(() => null);
            if (image) {
                ctx.save();
                ctx.filter = 'blur(42px) saturate(1.25)';
                ctx.globalAlpha = 0.58;
                this.coverImage(ctx, image, -70, 650, 1220, 1420);
                ctx.restore();
            }
        }

        const readabilityGradient = ctx.createLinearGradient(0, 720, 0, 1920);
        readabilityGradient.addColorStop(0, 'rgba(9, 9, 11, 0.52)');
        readabilityGradient.addColorStop(0.35, 'rgba(9, 9, 11, 0.36)');
        readabilityGradient.addColorStop(1, 'rgba(9, 9, 11, 0.64)');
        ctx.fillStyle = readabilityGradient;
        ctx.fillRect(0, 720, 1080, 1240);

        ctx.shadowColor = 'rgba(0, 0, 0, 0.35)';
        ctx.shadowBlur = 12;
        ctx.shadowOffsetY = 3;
        ctx.fillStyle = '#ffffff';
        ctx.font = '700 44px sans-serif';
        ctx.fillText(config.holderName || '-', 72, 840);

        ctx.fillStyle = 'rgba(255, 255, 255, 0.72)';
        ctx.font = '400 26px sans-serif';
        ctx.fillText(TicketFlowLanguage.format({ en: 'Holder', th: 'ผู้ถือบัตร' }), 72, 792);
        ctx.fillText(TicketFlowLanguage.format({ en: 'Date & time', th: 'วันเวลา' }), 72, 890);
        ctx.fillText(TicketFlowLanguage.format({ en: 'Venue', th: 'สถานที่' }), 72, 982);
        ctx.fillText(TicketFlowLanguage.format({ en: 'Order', th: 'ออเดอร์' }), 72, 1882);

        ctx.fillStyle = '#ffffff';
        ctx.font = '600 30px sans-serif';
        ctx.fillText(`${config.startsAt || '-'} - ${String(config.endsAt || '').split(' ').pop() || ''}`, 72, 930);
        this.wrapText(ctx, config.venue || '-', 72, 1025, 900, 44, 2);

        ctx.fillStyle = 'rgba(255, 255, 255, 0.84)';
        ctx.font = '400 30px sans-serif';
        this.wrapText(ctx, config.location || '', 72, 1065, 900, 38, 2);

        ctx.fillStyle = 'rgba(255, 255, 255, 0.74)';
        ctx.font = '400 26px monospace';
        this.wrapText(ctx, `${config.orderNumber || '-'} `, 1000, 1882, 900, 34, 2, 'right');
        ctx.shadowColor = 'transparent';
        ctx.shadowBlur = 0;
        ctx.shadowOffsetY = 0;
    },
    async drawQr(ctx) {
        const qr = await this.loadImage(config.qrUrl).catch(() => null);
        const boxX = 190;
        const boxY = 1128;
        const boxSize = 680;

        ctx.fillStyle = '#ffffff';
        ctx.strokeStyle = '#e4e4e7';
        ctx.lineWidth = 4;
        this.roundRect(ctx, boxX, boxY, boxSize, boxSize, 28, true, true);

        if (qr) {
            ctx.drawImage(qr, boxX + 60, boxY + 48, 560, 560);
        }

        ctx.fillStyle = '#18181b';
        ctx.font = '400 24px monospace';
        this.wrapText(ctx, config.uuid || '', boxX + 60, boxY + 632, 560, 28, 2, 'center');
    },
    drawInactiveNotice(ctx) {
        ctx.fillStyle = '#fffbeb';
        ctx.strokeStyle = '#f59e0b';
        ctx.lineWidth = 4;
        this.roundRect(ctx, 72, 1370, 936, 250, 28, true, true);
        ctx.fillStyle = '#92400e';
        ctx.font = '700 42px sans-serif';
        ctx.fillText('Ticket not active yet', 120, 1460);
        ctx.font = '400 30px sans-serif';
        this.wrapText(ctx, TicketFlowLanguage.format({ en: 'QR code will show after payment is approved.', th: 'QR จะแสดงหลังอนุมัติการชำระเงิน' }), 120, 1518, 820, 38, 3);
    },
    loadImage(src) {
        return new Promise((resolve, reject) => {
            const image = new Image();
            image.crossOrigin = 'anonymous';
            image.onload = () => resolve(image);
            image.onerror = reject;
            image.src = src;
        });
    },
    coverImage(ctx, image, x, y, width, height) {
        const ratio = Math.max(width / image.width, height / image.height);
        const drawWidth = image.width * ratio;
        const drawHeight = image.height * ratio;
        ctx.drawImage(image, x + (width - drawWidth) / 2, y + (height - drawHeight) / 2, drawWidth, drawHeight);
    },
    wrapText(ctx, text, x, y, maxWidth, lineHeight, maxLines = 3, align = 'left') {
        const words = String(text || '').split(/\s+/).filter(Boolean);
        let line = '';
        let lines = 0;
        const originalAlign = ctx.textAlign;
        ctx.textAlign = align;
        const drawX = align === 'center' ? x + maxWidth / 2 : x;

        words.forEach((word) => {
            if (lines >= maxLines) {
                return;
            }

            const next = line ? `${line} ${word}` : word;
            if (ctx.measureText(next).width > maxWidth && line) {
                ctx.fillText(line, drawX, y + lines * lineHeight);
                line = word;
                lines += 1;
            } else {
                line = next;
            }
        });

        if (line && lines < maxLines) {
            ctx.fillText(line, drawX, y + lines * lineHeight);
        }

        ctx.textAlign = originalAlign;
    },
    roundRect(ctx, x, y, width, height, radius, fill, stroke) {
        ctx.beginPath();
        ctx.moveTo(x + radius, y);
        ctx.arcTo(x + width, y, x + width, y + height, radius);
        ctx.arcTo(x + width, y + height, x, y + height, radius);
        ctx.arcTo(x, y + height, x, y, radius);
        ctx.arcTo(x, y, x + width, y, radius);
        ctx.closePath();
        if (fill) ctx.fill();
        if (stroke) ctx.stroke();
    },
    downloadCanvas(canvas) {
        const link = document.createElement('a');
        link.download = `${config.filename || 'ticket'}.png`;
        link.href = canvas.toDataURL('image/png');
        link.click();
    },
}));

Alpine.data('webPushSettings', () => ({
    supported: false,
    subscribed: false,
    message: TicketFlowLanguage.format({ en: 'Checking notification support...', th: 'กำลังตรวจสอบการรองรับ...' }),
    registration: null,
    async init() {
        this.supported = 'serviceWorker' in navigator && 'PushManager' in window && 'Notification' in window && Boolean(document.querySelector('meta[name="webpush-public-key"]')?.content);

        if (!this.supported) {
            this.message = TicketFlowLanguage.format({ en: 'Web Push is not available on this device or VAPID keys are missing.', th: 'อุปกรณ์นี้ยังไม่รองรับ Web Push หรือยังไม่ได้ตั้งค่า VAPID' });
            return;
        }

        this.registration = await navigator.serviceWorker.register('/sw.js');
        const subscription = await this.registration.pushManager.getSubscription();
        this.subscribed = Boolean(subscription);
        this.message = this.subscribed
            ? TicketFlowLanguage.format({ en: 'Web Push is enabled on this device.', th: 'เปิด Web Push บนอุปกรณ์นี้แล้ว' })
            : TicketFlowLanguage.format({ en: 'Web Push is ready.', th: 'พร้อมเปิด Web Push' });
    },
    async subscribe() {
        if (!this.supported) {
            return;
        }

        const permission = await Notification.requestPermission();

        if (permission !== 'granted') {
            this.message = TicketFlowLanguage.format({ en: 'Notification permission was not granted.', th: 'ยังไม่ได้อนุญาตการแจ้งเตือน' });
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
        this.message = TicketFlowLanguage.format({ en: 'Web Push is enabled on this device.', th: 'เปิด Web Push บนอุปกรณ์นี้แล้ว' });
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
        this.message = TicketFlowLanguage.format({ en: 'Web Push is off on this device.', th: 'ปิด Web Push บนอุปกรณ์นี้แล้ว' });
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
        reflectTicketEnd(ticket) {
            if (!ticket.sale_starts_at) {
                return;
            }

            if (!ticket.sale_ends_at || ticket.sale_ends_at < ticket.sale_starts_at) {
                const end = new Date(ticket.sale_starts_at);
                end.setDate(end.getDate() + 7);
                ticket.sale_ends_at = end.toISOString().slice(0, 16);
            }
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

Alpine.data('adminEventForm', (config) => ({
    description: config.description || '',
    descriptionFormat: config.descriptionFormat || 'html',
    paymentAccounts: (config.paymentAccounts?.length ? config.paymentAccounts : [
        { key: 'qr-payment', method: 'qr_payment', label: TicketFlowLanguage.format({ en: 'QR payment', th: 'ชำระด้วย QR' }), bank_name: '', account_name: '', account_number: '', instructions: '', is_active: true },
        { key: 'bank-transfer', method: 'bank_transfer', label: TicketFlowLanguage.format({ en: 'Bank transfer', th: 'โอนธนาคาร' }), bank_name: '', account_name: '', account_number: '', instructions: '', is_active: true },
    ]).map((account, index) => ({
        key: account.key || `payment-${index}-${Date.now()}`,
        method: account.method || 'qr_payment',
        label: account.label || '',
        bank_name: account.bank_name || '',
        account_name: account.account_name || '',
        account_number: account.account_number || '',
        instructions: account.instructions || '',
        is_active: account.is_active !== false,
    })),
    banks: config.banks || [],
    previewOpen: false,
    blankPaymentAccount() {
        return {
            key: `payment-${Date.now()}-${Math.random().toString(36).slice(2, 7)}`,
            method: 'qr_payment',
            label: TicketFlowLanguage.format({ en: 'QR payment', th: 'ชำระด้วย QR' }),
            bank_name: '',
            account_name: '',
            account_number: '',
            instructions: '',
            is_active: true,
        };
    },
    addPaymentAccount() {
        this.paymentAccounts.push(this.blankPaymentAccount());
    },
    removePaymentAccount(index) {
        this.paymentAccounts.splice(index, 1);

        if (this.paymentAccounts.length === 0) {
            this.addPaymentAccount();
        }
    },
    movePaymentAccount(index, direction) {
        const target = index + direction;

        if (target < 0 || target >= this.paymentAccounts.length) {
            return;
        }

        const accounts = [...this.paymentAccounts];
        const [account] = accounts.splice(index, 1);
        accounts.splice(target, 0, account);
        this.paymentAccounts = accounts;
    },
    firstAccount(method) {
        return this.paymentAccounts.find((account) => account.method === method && account.is_active) || {};
    },
    firstPaymentInstructions() {
        return this.paymentAccounts.find((account) => account.instructions)?.instructions || '';
    },
    escapeHtml(value) {
        const div = document.createElement('div');
        div.textContent = value || '';

        return div.innerHTML;
    },
    previewHtml() {
        if (this.descriptionFormat === 'html') {
            return this.description || `<p class="text-zinc-500">${TicketFlowLanguage.format({ en: 'No description yet', th: 'ยังไม่มีรายละเอียด' })}</p>`;
        }

        return this.escapeHtml(this.description)
            .replace(/^### (.*)$/gm, '<h3>$1</h3>')
            .replace(/^## (.*)$/gm, '<h2>$1</h2>')
            .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
            .replace(/\*(.*?)\*/g, '<em>$1</em>')
            .replace(/\[(.*?)\]\((https?:\/\/[^\s)]+)\)/g, '<a href="$2" target="_blank" rel="noopener">$1</a>')
            .replace(/(?:^|\n)- (.*)/g, '<br>• $1')
            .replace(/\n/g, '<br>');
    },
}));

document.addEventListener('change', (event) => {
    const start = event.target.closest('[data-date-start]');
    if (!start) {
        return;
    }

    const key = start.dataset.dateStart;
    const end = document.querySelector(`[data-date-end="${key}"]`);
    if (!end || !start.value) {
        return;
    }

    if (end.value && end.value >= start.value) {
        return;
    }

    const date = new Date(start.value);
    date.setHours(date.getHours() + Number(start.dataset.defaultHours || 0));
    date.setDate(date.getDate() + Number(start.dataset.defaultDays || 0));
    end.value = date.toISOString().slice(0, 16);
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
                this.message = TicketFlowLanguage.format({ en: 'LINE LIFF could not start. Please check the LIFF ID and endpoint URL.', th: 'ไม่สามารถเริ่ม LINE LIFF ได้ กรุณาตรวจสอบ LIFF ID และ Endpoint URL' });
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
                throw new Error(TicketFlowLanguage.format({ en: 'Missing LINE id token.', th: 'ไม่พบ LINE id token' }));
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
                throw new Error(errors.line?.[0] || payload.message || TicketFlowLanguage.format({ en: 'LINE login failed.', th: 'เข้าสู่ระบบ LINE ไม่สำเร็จ' }));
            }

            sessionStorage.removeItem('line_liff_login_pending');
            window.location.href = payload.redirect || config.profileUrl;
        } catch (error) {
            this.loading = false;
            this.message = error.message || TicketFlowLanguage.format({ en: 'LINE login failed. Please try again.', th: 'เข้าสู่ระบบ LINE ไม่สำเร็จ กรุณาลองอีกครั้ง' });
        }
    },
}));

Alpine.data('surveyBuilder', (initialQuestions = []) => ({
    questions: initialQuestions.length
        ? initialQuestions.map((question, index) => ({
            localId: index + 1,
            key: '',
            label: '',
            type: 'text',
            help: '',
            options: '',
            required: false,
            hasOther: false,
            otherLabel: '',
            searchable: false,
            ...question,
        }))
        : [],
    nextId: initialQuestions.length + 1,
    addQuestion() {
        this.questions.push({
            localId: this.nextId++,
            key: '',
            label: '',
            type: 'text',
            help: '',
            options: '',
            required: false,
            hasOther: false,
            otherLabel: '',
            searchable: false,
        });
    },
    removeQuestion(index) {
        this.questions.splice(index, 1);
    },
}));

if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/sw.js').catch(() => {});
    });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => uiLanguage.init(), { once: true });
} else {
    uiLanguage.init();
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
