<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\CouponController;
use App\Http\Controllers\Admin\EventController as AdminEventController;
use App\Http\Controllers\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Admin\ScannerController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\OrderController;
use Illuminate\Support\Facades\Route;

Route::get('/', [EventController::class, 'index'])->name('events.index');
Route::get('/events/{event}', [EventController::class, 'show'])->name('events.show');

Route::get('/login', [AuthController::class, 'show'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.store');
Route::get('/admin/login', [AuthController::class, 'adminShow'])->name('admin.login');
Route::post('/admin/login', [AuthController::class, 'adminLogin'])->name('admin.login.store');
Route::get('/auth/{provider}', [AuthController::class, 'social'])->name('auth.social');
Route::get('/auth/{provider}/callback', [AuthController::class, 'socialCallback'])->name('auth.social.callback');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::post('/orders', [OrderController::class, 'store'])->name('orders.store');
Route::get('/payments/events/{event}/qr', [OrderController::class, 'paymentQr'])->name('payments.qr');
Route::get('/orders/lookup', [EventController::class, 'lookup'])->name('orders.lookup');
Route::get('/orders/{order}', [OrderController::class, 'show'])->name('orders.show');
Route::get('/tickets/{uuid}', [OrderController::class, 'ticket'])->name('tickets.show');
Route::get('/tickets/{uuid}/qr', [OrderController::class, 'ticketQr'])->name('tickets.qr');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [EventController::class, 'profile'])->name('profile');
});

Route::middleware(['auth', 'role:super_admin,event_admin,gate_scanner'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', DashboardController::class)->name('dashboard');
    Route::get('/scanner', [ScannerController::class, 'index'])->name('scanner');
    Route::post('/scanner', [ScannerController::class, 'scan'])->name('scanner.scan');
});

Route::middleware(['auth', 'role:super_admin,event_admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/events/{event}/overview', [AdminEventController::class, 'overview'])->name('events.overview');
    Route::patch('/events/{event}/tickets/{ticket}/status', [AdminEventController::class, 'updateTicketStatus'])->name('events.tickets.status');
    Route::resource('events', AdminEventController::class)->except(['show', 'destroy']);
    Route::resource('coupons', CouponController::class)->except(['show', 'destroy']);
    Route::get('/orders', [AdminOrderController::class, 'index'])->name('orders.index');
    Route::get('/orders/{order}', [AdminOrderController::class, 'show'])->name('orders.show');
    Route::post('/orders/{order}/approve', [AdminOrderController::class, 'approve'])->name('orders.approve');
    Route::post('/orders/{order}/reject', [AdminOrderController::class, 'reject'])->name('orders.reject');
    Route::post('/orders/{order}/refund', [AdminOrderController::class, 'refund'])->name('orders.refund');
});

Route::middleware(['auth', 'role:super_admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::resource('users', AdminUserController::class)->only(['index', 'edit', 'update']);
});
