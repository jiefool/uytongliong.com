<?php

namespace App\Http\Controllers;

use App\Models\GenerationalMessage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GenerationalMessageController extends Controller
{
    public function index(): View
    {
        return view('message-to-next-generations', [
            'messages' => GenerationalMessage::with('author')->latest()->get(),
            'editingMessage' => null,
        ]);
    }

    public function viewerIndex(): View
    {
        return view('message-to-next-generations', [
            'messages' => GenerationalMessage::with('author')->latest()->get(),
            'editingMessage' => null,
            'pinViewer' => true,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->user()->generationalMessages()->create($this->validatedData($request));

        return redirect()->route('message-to-next-generations')->with('status', 'Your message has been added to the family archive.');
    }

    public function edit(GenerationalMessage $message): View
    {
        return view('message-to-next-generations', [
            'messages' => GenerationalMessage::with('author')->latest()->get(),
            'editingMessage' => $message,
        ]);
    }

    public function update(Request $request, GenerationalMessage $message): RedirectResponse
    {
        $message->update($this->validatedData($request));

        return redirect()->route('message-to-next-generations')->with('status', 'The message has been updated.');
    }

    public function destroy(GenerationalMessage $message): RedirectResponse
    {
        $message->delete();

        return redirect()->route('message-to-next-generations')->with('status', 'The message has been removed.');
    }

    private function validatedData(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:120'],
            'from_name' => ['nullable', 'string', 'max:120'],
            'body' => ['required', 'string', 'max:5000'],
        ]);
    }
}
