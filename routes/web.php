<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\CouponController;
use App\Http\Controllers\Admin\EventController as AdminEventController;
use App\Http\Controllers\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Admin\PromotionController;
use App\Http\Controllers\Admin\ScannerController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CrmController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\LegalDocumentController;
use App\Http\Controllers\LineWebhookController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PushSubscriptionController;
use App\Http\Controllers\SeoController;
use Illuminate\Support\Facades\Route;

Route::get('/robots.txt', [SeoController::class, 'robots'])->name('seo.robots');
Route::get('/sitemap.xml', [SeoController::class, 'sitemap'])->name('seo.sitemap');

Route::get('/', [EventController::class, 'index'])->name('events.index');
Route::view('/guides/how-to-buy-ticket', 'guides.how-to-buy-ticket')->name('guides.buy-ticket');
Route::view('/guides/gate-check-in', 'guides.gate-check-in')->name('guides.gate-check-in');
Route::get('/legal/terms', [LegalDocumentController::class, 'show'])->defaults('document', 'terms')->name('legal.terms');
Route::get('/legal/privacy', [LegalDocumentController::class, 'show'])->defaults('document', 'privacy')->name('legal.privacy');
Route::get('/legal/refund-policy', [LegalDocumentController::class, 'show'])->defaults('document', 'refund')->name('legal.refund');
Route::get('/legal/event-admission-policy', [LegalDocumentController::class, 'show'])->defaults('document', 'event-admission')->name('legal.event-admission');
Route::get('/legal/cookie-policy', [LegalDocumentController::class, 'show'])->defaults('document', 'cookies')->name('legal.cookies');
Route::get('/events/{event}', [EventController::class, 'show'])->name('events.show');

Route::get('/login', [AuthController::class, 'show'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.store');
Route::get('/admin/login', [AuthController::class, 'adminShow'])->name('admin.login');
Route::post('/admin/login', [AuthController::class, 'adminLogin'])->name('admin.login.store');
Route::post('/auth/line/liff', [AuthController::class, 'lineLiff'])->name('auth.line.liff');
Route::post('/line/webhook', LineWebhookController::class)->name('line.webhook');
Route::get('/auth/{provider}', [AuthController::class, 'social'])->name('auth.social');
Route::get('/auth/{provider}/callback', [AuthController::class, 'socialCallback'])->name('auth.social.callback');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::prefix('crm')->name('crm.')->group(function () {
    Route::get('/customers/lookup', [CrmController::class, 'lookupCustomer'])->name('customers.lookup');
    Route::post('/customers/upsert', [CrmController::class, 'upsertCustomer'])->name('customers.upsert');
    Route::get('/orders/{order}', [CrmController::class, 'showOrder'])->name('orders.show');
});

Route::post('/orders', [OrderController::class, 'store'])->name('orders.store');
Route::get('/payments/events/{event}/qr', [OrderController::class, 'paymentQr'])->name('payments.qr');
Route::get('/orders/lookup', [EventController::class, 'lookup'])->name('orders.lookup');
Route::get('/orders/{order}', [OrderController::class, 'show'])->name('orders.show');
Route::get('/tickets/{uuid}', [OrderController::class, 'ticket'])->name('tickets.show');
Route::get('/tickets/{uuid}/qr', [OrderController::class, 'ticketQr'])->name('tickets.qr');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [EventController::class, 'profile'])->name('profile');
    Route::post('/push-subscriptions', [PushSubscriptionController::class, 'store'])->name('push-subscriptions.store');
    Route::delete('/push-subscriptions', [PushSubscriptionController::class, 'destroy'])->name('push-subscriptions.destroy');
});

Route::middleware(['auth', 'role:super_admin,event_admin,gate_scanner'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', DashboardController::class)->name('dashboard');
    Route::get('/scanner', [ScannerController::class, 'index'])->name('scanner');
    Route::post('/scanner', [ScannerController::class, 'scan'])->name('scanner.scan');
});

Route::middleware(['auth', 'role:super_admin,event_admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/events/{event}/overview', [AdminEventController::class, 'overview'])->name('events.overview');
    Route::post('/events/{event}/email-attendees', [AdminEventController::class, 'emailAttendees'])->name('events.email-attendees');
    Route::post('/events/{event}/message-attendees', [AdminEventController::class, 'messageAttendees'])->name('events.message-attendees');
    Route::patch('/events/{event}/tickets/{ticket}/status', [AdminEventController::class, 'updateTicketStatus'])->name('events.tickets.status');
    Route::delete('/events/{event}/tickets/{ticket}', [AdminEventController::class, 'destroyTicket'])->name('events.tickets.destroy');
    Route::resource('events', AdminEventController::class)->except(['show']);
    Route::resource('coupons', CouponController::class)->except(['show']);
    Route::resource('promotions', PromotionController::class)->except(['show']);
    Route::get('/orders', [AdminOrderController::class, 'index'])->name('orders.index');
    Route::get('/orders/{order}', [AdminOrderController::class, 'show'])->name('orders.show');
    Route::post('/orders/{order}/approve', [AdminOrderController::class, 'approve'])->name('orders.approve');
    Route::post('/orders/{order}/reject', [AdminOrderController::class, 'reject'])->name('orders.reject');
    Route::post('/orders/{order}/refund', [AdminOrderController::class, 'refund'])->name('orders.refund');
    Route::post('/orders/{order}/check-slip-qr', [AdminOrderController::class, 'checkSlipQr'])->name('orders.check-slip-qr');
    Route::delete('/orders/{order}', [AdminOrderController::class, 'destroy'])->name('orders.destroy');
});

Route::middleware(['auth', 'role:super_admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::resource('users', AdminUserController::class)->only(['index', 'edit', 'update', 'destroy']);
});
