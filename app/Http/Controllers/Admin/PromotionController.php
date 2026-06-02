<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Promotion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PromotionController extends Controller
{
    public function index(): View
    {
        $promotions = Promotion::with(['event', 'ticketType'])->latest();

        if (auth()->user()->role !== 'super_admin') {
            $promotions->whereHas('event.assignedUsers', fn ($query) => $query->whereKey(auth()->id()));
        }

        return view('admin.promotions.index', [
            'promotions' => $promotions->paginate(20),
        ]);
    }

    public function create(Request $request): View
    {
        return view('admin.promotions.form', [
            'promotion' => new Promotion(['promotion_type' => 'buy_x_get_y', 'discount_scope' => 'order', 'combines_with_coupons' => true, 'is_active' => true]),
            'events' => $this->eventsForUser($request),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $this->authorizePromotionEvent($request, $data);

        Promotion::create($data);

        return redirect()->route('admin.promotions.index')->with('status', 'Promotion created.');
    }

    public function edit(Promotion $promotion): View
    {
        abort_unless($this->canManagePromotion($promotion), 403);

        return view('admin.promotions.form', [
            'promotion' => $promotion,
            'events' => $this->eventsForUser(request()),
        ]);
    }

    public function update(Request $request, Promotion $promotion): RedirectResponse
    {
        abort_unless($this->canManagePromotion($promotion), 403);

        $data = $this->validated($request);
        $this->authorizePromotionEvent($request, $data);

        $promotion->update($data);

        return redirect()->route('admin.promotions.index')->with('status', 'Promotion updated.');
    }

    public function destroy(Promotion $promotion): RedirectResponse
    {
        abort_unless($this->canManagePromotion($promotion), 403);
        $promotion->delete();

        return redirect()->route('admin.promotions.index')->with('status', 'Promotion deleted.');
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'event_id' => ['nullable', 'exists:events,id'],
            'ticket_type_id' => ['nullable', 'exists:ticket_types,id'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'promotion_type' => ['required', 'in:buy_x_get_y,fixed,percent'],
            'discount_scope' => ['required', 'in:order,item'],
            'buy_quantity' => ['nullable', 'integer', 'min:1'],
            'get_quantity' => ['nullable', 'integer', 'min:1'],
            'min_quantity' => ['nullable', 'integer', 'min:1'],
            'discount_value' => ['nullable', 'integer', 'min:1'],
            'max_discount_thb' => ['nullable', 'integer', 'min:1'],
            'usage_limit' => ['nullable', 'integer', 'min:1'],
            'starts_at' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'combines_with_coupons' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
            'show_on_event_page' => ['nullable', 'boolean'],
        ]);

        $data['combines_with_coupons'] = $request->boolean('combines_with_coupons');
        $data['is_active'] = $request->boolean('is_active');
        $data['show_on_event_page'] = $request->boolean('show_on_event_page');

        if ($data['promotion_type'] === 'buy_x_get_y') {
            $data['buy_quantity'] = $data['buy_quantity'] ?: 2;
            $data['get_quantity'] = $data['get_quantity'] ?: 1;
            $data['discount_value'] = null;
            $data['discount_scope'] = 'order';
        } else {
            $data['buy_quantity'] = null;
            $data['get_quantity'] = null;
            $data['discount_value'] = $data['discount_value'] ?: 1;
        }

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

    private function canManagePromotion(Promotion $promotion): bool
    {
        return auth()->user()->role === 'super_admin'
            || ($promotion->event_id && auth()->user()->canManageEvent($promotion->event_id));
    }

    private function authorizePromotionEvent(Request $request, array $data): void
    {
        if ($request->user()->role === 'super_admin') {
            return;
        }

        abort_unless(! empty($data['event_id']) && $request->user()->canManageEvent((int) $data['event_id']), 403);
    }
}
