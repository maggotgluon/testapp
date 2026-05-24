<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        return view('admin.users.index', [
            'users' => User::query()
                ->when($request->filled('role'), fn ($query) => $query->where('role', $request->string('role')))
                ->orderBy('role')
                ->orderBy('name')
                ->paginate(20),
        ]);
    }

    public function edit(User $user): View
    {
        return view('admin.users.edit', [
            'user' => $user->load('assignedEvents'),
            'events' => Event::orderBy('starts_at')->get(),
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['nullable', 'string', 'max:80', 'unique:users,username,'.$user->id],
            'phone' => ['nullable', 'string', 'max:40'],
            'email' => ['nullable', 'email'],
            'role' => ['required', 'in:customer,super_admin,event_admin,gate_scanner'],
            'event_ids' => ['nullable', 'array'],
            'event_ids.*' => ['integer', 'exists:events,id'],
        ]);

        if ($user->id === $request->user()->id && $data['role'] !== 'super_admin') {
            return back()->withErrors(['role' => 'You cannot remove your own super admin role.'])->withInput();
        }

        $eventIds = $data['event_ids'] ?? [];
        unset($data['event_ids']);

        $user->update($data);
        $user->assignedEvents()->sync(
            in_array($data['role'], ['event_admin', 'gate_scanner'], true)
                ? $eventIds
                : []
        );

        return redirect()->route('admin.users.index')->with('status', 'User updated.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ($user->id === $request->user()->id) {
            return back()->withErrors(['user' => 'You cannot delete your own account.']);
        }

        $user->delete();

        return redirect()->route('admin.users.index')->with('status', 'User deleted.');
    }
}
