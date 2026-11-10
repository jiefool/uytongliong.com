<?php

namespace App\Http\Controllers;

use App\Models\Newsletter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class NewsletterController extends Controller
{
    public function publicIndex(Request $request): View
    {
        $filters = $this->filters($request);

        return view('newsletters.public-index', [
            'newsletters' => $this->filtered(Newsletter::published()->with('author'), $filters)->paginate(12)->withQueryString(),
            'filters' => $filters,
        ]);
    }

    public function index(Request $request): View
    {
        $filters = $this->filters($request, true);
        $query = Newsletter::query()->when(! $request->user()->isAdmin(), fn ($query) => $query->where('user_id', $request->user()->id));
        $stats = [
            'total' => (clone $query)->count(),
            'draft' => (clone $query)->where('status', 'draft')->count(),
            'published' => (clone $query)->published()->count(),
            'archived' => (clone $query)->where('status', 'archived')->count(),
        ];

        return view('newsletters.index', [
            'newsletters' => $this->filtered($query->with('author'), $filters)->paginate(12)->withQueryString(),
            'filters' => $filters,
            'stats' => $stats,
        ]);
    }

    public function create(): View
    {
        return view('newsletters.form', ['newsletter' => new Newsletter]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatedData($request);
        $this->storeCover($request, $data);
        $newsletter = $request->user()->newsletters()->create($data);

        return redirect()->route('newsletters.edit', $newsletter)->with('status', 'Newsletter created.');
    }

    public function show(Request $request, Newsletter $newsletter): View
    {
        abort_unless($newsletter->status === 'published' || $newsletter->canManage($request->user()), 404);

        return view('newsletters.show', ['newsletter' => $newsletter->load('author')]);
    }

    public function cover(Request $request, Newsletter $newsletter): StreamedResponse
    {
        abort_unless($newsletter->status === 'published' || $newsletter->canManage($request->user()), 404);
        abort_unless($newsletter->cover_image_path && Storage::disk('local')->exists($newsletter->cover_image_path), 404);

        return Storage::disk('local')->response($newsletter->cover_image_path, null, ['Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff']);
    }

    public function edit(Request $request, Newsletter $newsletter): View
    {
        abort_unless($newsletter->canManage($request->user()), 403);

        return view('newsletters.form', compact('newsletter'));
    }

    public function update(Request $request, Newsletter $newsletter): RedirectResponse
    {
        abort_unless($newsletter->canManage($request->user()), 403);
        $data = $this->validatedData($request);
        $oldCover = $newsletter->cover_image_path;
        $this->storeCover($request, $data);
        if ($request->boolean('remove_cover') && ! $request->hasFile('cover_image')) {
            $data['cover_image_path'] = null;
        }
        $newsletter->update($data);
        if ($oldCover && $oldCover !== $newsletter->cover_image_path) {
            Storage::disk('local')->delete($oldCover);
        }

        return redirect()->route('newsletters.edit', $newsletter)->with('status', 'Newsletter saved.');
    }

    public function destroy(Request $request, Newsletter $newsletter): RedirectResponse
    {
        abort_unless($newsletter->canManage($request->user()), 403);
        $newsletter->delete();
        if ($newsletter->cover_image_path) {
            Storage::disk('local')->delete($newsletter->cover_image_path);
        }

        return redirect()->route('newsletters.index')->with('status', 'Newsletter deleted.');
    }

    private function filters(Request $request, bool $managing = false): array
    {
        return array_merge(['q' => '', 'status' => 'all'], $request->validate([
            'q' => ['sometimes', 'nullable', 'string', 'max:160'],
            'status' => ['sometimes', Rule::in($managing ? ['all', 'draft', 'published', 'archived'] : ['all', 'published'])],
        ]));
    }

    private function filtered(Builder $query, array $filters): Builder
    {
        return $query->when($filters['q'] !== null && $filters['q'] !== '', fn ($query) => $query->where(function ($query) use ($filters) {
            $query->where('title', 'like', '%'.$filters['q'].'%')->orWhere('summary', 'like', '%'.$filters['q'].'%');
        }))->when($filters['status'] !== 'all', fn ($query) => $query->where('status', $filters['status']))
            ->orderByDesc('issue_date')->orderByDesc('id');
    }

    private function validatedData(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:160'],
            'issue_date' => ['required', 'date_format:Y-m-d'],
            'summary' => ['nullable', 'string', 'max:500'],
            'body' => ['required', 'string', 'max:50000'],
            'status' => ['required', Rule::in(['draft', 'published', 'archived'])],
            'cover_image' => ['nullable', 'image', 'max:5120'],
            'remove_cover' => ['sometimes', 'boolean'],
        ]);
    }

    private function storeCover(Request $request, array &$data): void
    {
        unset($data['cover_image'], $data['remove_cover']);
        if ($request->hasFile('cover_image')) {
            $data['cover_image_path'] = $request->file('cover_image')->store('newsletter-covers', 'local');
        }
    }
}
