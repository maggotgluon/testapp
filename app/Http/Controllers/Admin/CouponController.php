<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use App\Models\Event;
use App\Models\Promotion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CouponController extends Controller
{
    public function index(Request $request): View
    {
        $events = $this->eventsForUser($request);
        $coupons = $this->filteredCoupons($request, $events);
        $promotions = $this->filteredPromotions($request, $events);

        return view('admin.marketing.index', [
            'activeTab' => 'coupons',
            'coupons' => $coupons->paginate(20, ['*'], 'coupons_page')->withQueryString(),
            'promotions' => $promotions->paginate(20, ['*'], 'promotions_page')->withQueryString(),
            'events' => $events,
        ]);
    }

    public function create(Request $request): View
    {
        return view('admin.coupons.form', [
            'coupon' => new Coupon,
            'events' => $this->eventsForUser($request),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $this->authorizeCouponEvent($request, $data);

        Coupon::create($data);

        return redirect()->route('admin.coupons.index')->with('status', 'Coupon created.');
    }

    public function edit(Coupon $coupon): View
    {
        abort_unless($this->canManageCoupon($coupon), 403);

        return view('admin.coupons.form', [
            'coupon' => $coupon,
            'events' => $this->eventsForUser(request()),
        ]);
    }

    public function update(Request $request, Coupon $coupon): RedirectResponse
    {
        abort_unless($this->canManageCoupon($coupon), 403);

        $data = $this->validated($request);
        $this->authorizeCouponEvent($request, $data);

        $coupon->update($data);

        return redirect()->route('admin.coupons.index')->with('status', 'Coupon updated.');
    }

    public function destroy(Coupon $coupon): RedirectResponse
    {
        abort_unless($this->canManageCoupon($coupon), 403);
        $coupon->delete();

        return redirect()->route('admin.coupons.index')->with('status', 'Coupon deleted.');
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'event_id' => ['nullable', 'exists:events,id'],
            'ticket_type_id' => ['nullable', 'exists:ticket_types,id'],
            'name' => ['nullable', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:40'],
            'discount_type' => ['required', 'in:fixed,percent'],
            'discount_scope' => ['required', 'in:order,item'],
            'discount_value' => ['required', 'integer', 'min:1'],
            'usage_limit' => ['nullable', 'integer', 'min:1'],
            'starts_at' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'is_active' => ['nullable', 'boolean'],
            'show_on_checkout' => ['nullable', 'boolean'],
        ]);

        $data['code'] = strtoupper($data['code']);
        $data['is_active'] = $request->boolean('is_active');
        $data['show_on_checkout'] = $request->boolean('show_on_checkout');

        return $data;
    }

    private function eventsForUser(Request $request)
    {
        $events = Event::with('ticketTypes')->orderBy('starts_at');

        if ($request->user()->role !== 'super_admin') {
            $events->whereHas('assignedUsers', fn ($query) => $query->whereKey($request->user()->id));
        }

        return $events->get();
    }

    private function filteredCoupons(Request $request, $events)
    {
        $coupons = Coupon::with(['event', 'ticketType'])->latest();

        if ($request->user()->role !== 'super_admin') {
            $coupons->whereIn('event_id', $events->pluck('id'));
        }

        return $coupons
            ->when($request->filled('event_id'), fn ($query) => $query->where('event_id', $request->integer('event_id')))
            ->when($request->filled('type'), fn ($query) => $query->where('discount_type', $request->string('type')))
            ->when($request->filled('status'), fn ($query) => $query->where('is_active', $request->string('status') === 'active'));
    }

    private function filteredPromotions(Request $request, $events)
    {
        $promotions = Promotion::with(['event', 'ticketType'])->latest();

        if ($request->user()->role !== 'super_admin') {
            $promotions->whereIn('event_id', $events->pluck('id'));
        }

        return $promotions
            ->when($request->filled('event_id'), fn ($query) => $query->where('event_id', $request->integer('event_id')))
            ->when($request->filled('status'), fn ($query) => $query->where('is_active', $request->string('status') === 'active'));
    }

    private function canManageCoupon(Coupon $coupon): bool
    {
        return auth()->user()->role === 'super_admin'
            || ($coupon->event_id && auth()->user()->canManageEvent($coupon->event_id));
    }

    private function authorizeCouponEvent(Request $request, array $data): void
    {
        if ($request->user()->role === 'super_admin') {
            return;
        }

        abort_unless(! empty($data['event_id']) && $request->user()->canManageEvent((int) $data['event_id']), 403);
    }
}
