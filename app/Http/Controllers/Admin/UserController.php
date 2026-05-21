<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
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
        return view('admin.users.edit', compact('user'));
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['nullable', 'string', 'max:80', 'unique:users,username,'.$user->id],
            'phone' => ['nullable', 'string', 'max:40'],
            'email' => ['nullable', 'email'],
            'role' => ['required', 'in:customer,super_admin,event_admin,gate_scanner'],
        ]);

        if ($user->id === $request->user()->id && $data['role'] !== 'super_admin') {
            return back()->withErrors(['role' => 'You cannot remove your own super admin role.'])->withInput();
        }

        $user->update($data);

        return redirect()->route('admin.users.index')->with('status', 'User updated.');
    }
}
