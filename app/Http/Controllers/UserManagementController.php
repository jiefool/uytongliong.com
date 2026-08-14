<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class UserManagementController extends Controller
{
    public function index(): View
    {
        return $this->view();
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validatedData($request, true);
        $validated['password'] = Hash::make($validated['password']);
        User::create($validated);

        return redirect()->route('users.index')->with('status', 'User account created.');
    }

    public function edit(User $user): View
    {
        return $this->view($user);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $validated = $this->validatedData($request, false, $user);

        if ($user->is($request->user()) && $validated['role'] !== 'admin') {
            return back()->withErrors(['role' => 'You cannot remove your own Admin role.']);
        }

        if (filled($validated['password'] ?? null)) $validated['password'] = Hash::make($validated['password']);
        else unset($validated['password']);
        User::whereKey($user->id)->update($validated);

        return redirect()->route('users.index')->with('status', 'User account updated.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ($user->is($request->user())) return back()->withErrors(['user' => 'You cannot delete your own account.']);
        if ($user->isAdmin() && User::where('role', 'admin')->count() <= 1) return back()->withErrors(['user' => 'Keep at least one Admin account.']);

        $user->delete();

        return redirect()->route('users.index')->with('status', 'User account removed.');
    }

    private function view(?User $editingUser = null): View
    {
        return view('users.index', ['users' => User::orderBy('name')->get(), 'editingUser' => $editingUser]);
    }

    private function validatedData(Request $request, bool $creating, ?User $user = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user)],
            'role' => ['required', Rule::in(['admin', 'secretariat'])],
            'password' => [$creating ? 'required' : 'nullable', 'confirmed', Password::min(8)],
        ]);
    }
}
