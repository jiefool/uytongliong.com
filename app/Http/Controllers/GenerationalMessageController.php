<?php

namespace App\Http\Controllers;

use App\Models\GenerationalMessage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

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

    public function video(GenerationalMessage $message): BinaryFileResponse
    {
        abort_unless($message->video_path && Storage::disk('local')->exists($message->video_path), 404);
        $contentType = match (strtolower(pathinfo($message->video_path, PATHINFO_EXTENSION))) {
            'mp4', 'm4v' => 'video/mp4',
            'webm' => 'video/webm',
            'mov' => 'video/quicktime',
            default => null,
        };
        abort_unless($contentType, 404);

        return response()->file(Storage::disk('local')->path($message->video_path), [
            'Content-Type' => $contentType,
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatedData($request);
        $this->requireContent($data, $request);
        $this->storeVideo($request, $data);
        $request->user()->generationalMessages()->create($data);

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
        $data = $this->validatedData($request);
        $this->requireContent($data, $request, $message);
        $oldVideo = $message->video_path;
        $this->storeVideo($request, $data);
        if ($request->boolean('remove_video') && ! $request->hasFile('video')) {
            $data['video_path'] = null;
        }
        $message->update($data);
        if ($oldVideo && $oldVideo !== $message->video_path) {
            Storage::disk('local')->delete($oldVideo);
        }

        return redirect()->route('message-to-next-generations')->with('status', 'The message has been updated.');
    }

    public function destroy(GenerationalMessage $message): RedirectResponse
    {
        $video = $message->video_path;
        $message->delete();
        if ($video) {
            Storage::disk('local')->delete($video);
        }

        return redirect()->route('message-to-next-generations')->with('status', 'The message has been removed.');
    }

    private function validatedData(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:120'],
            'from_name' => ['nullable', 'string', 'max:120'],
            'body' => ['nullable', 'string', 'max:5000'],
            'video' => ['nullable', 'file', 'mimetypes:video/mp4,video/webm,video/quicktime,video/x-m4v', 'max:10240'],
            'remove_video' => ['sometimes', 'boolean'],
        ]);
    }

    private function requireContent(array $data, Request $request, ?GenerationalMessage $message = null): void
    {
        $keepingVideo = $message?->video_path && ! $request->boolean('remove_video');
        $body = array_key_exists('body', $data) ? $data['body'] : $message?->body;
        if (blank($body) && ! $request->hasFile('video') && ! $keepingVideo) {
            throw ValidationException::withMessages(['body' => 'Write a message or attach a video.']);
        }
    }

    private function storeVideo(Request $request, array &$data): void
    {
        unset($data['video'], $data['remove_video']);
        if ($request->hasFile('video')) {
            $data['video_path'] = $request->file('video')->store('message-videos', 'local');
        }
    }
}
