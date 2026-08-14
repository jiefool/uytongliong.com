<?php

namespace App\Http\Controllers;

use App\Models\TreeAccessPin;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class PinAccessController extends Controller
{
    public function show(): View|RedirectResponse
    {
        if (session()->has('tree_pin_access')) return redirect()->route('pin.tree');

        return view('pin-entry');
    }

    public function verify(Request $request): RedirectResponse
    {
        $validated = $request->validate(['pin' => ['required', 'digits:6']]);
        $pin = TreeAccessPin::all()->first(fn (TreeAccessPin $accessPin) => Hash::check($validated['pin'], $accessPin->pin_hash));

        if (! $pin) return back()->withErrors(['pin' => 'That access PIN is not valid.']);

        $request->session()->put('tree_pin_access', $pin->id);
        $request->session()->regenerate();

        return redirect()->route('pin.tree');
    }

    public function leave(Request $request): RedirectResponse
    {
        $request->session()->forget('tree_pin_access');

        return redirect()->route('pin.entry');
    }
}
