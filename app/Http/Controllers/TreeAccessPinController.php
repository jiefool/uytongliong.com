<?php

namespace App\Http\Controllers;

use App\Models\TreeAccessPin;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Crypt;
use Illuminate\View\View;

class TreeAccessPinController extends Controller
{
    public function index(): View
    {
        return view('access-pins.index', ['pins' => TreeAccessPin::with('creator')->latest()->get()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate(['label' => ['nullable', 'string', 'max:120']]);
        $pin = (string) random_int(100000, 999999);

        $request->user()->treeAccessPins()->create([
            'label' => $validated['label'] ?? null,
            'pin_hash' => Hash::make($pin),
            'pin_encrypted' => Crypt::encryptString($pin),
        ]);

        return redirect()->route('access-pins.index')->with('generatedPin', $pin)->with('status', 'A new view-access PIN has been generated.');
    }

    public function destroy(TreeAccessPin $accessPin): RedirectResponse
    {
        $accessPin->delete();

        return redirect()->route('access-pins.index')->with('status', 'View-access PIN revoked.');
    }
}
