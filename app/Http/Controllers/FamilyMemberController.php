<?php

namespace App\Http\Controllers;

use App\Models\FamilyMember;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class FamilyMemberController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $members = FamilyMember::query()
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();

        $members->each(function (FamilyMember $member) use ($user) {
            $member->setAttribute('can_edit', (bool) $user && (! $user->isFamilyAdmin() || $member->created_by_user_id === $user->id));
        });

        return response()->json($members);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $this->validatedData($request);
        $user = $request->user();

        if (isset($validated['spouse_id'])) {
            $spouse = FamilyMember::findOrFail($validated['spouse_id']);
            abort_if($user->isFamilyAdmin() && $spouse->created_by_user_id !== $user->id, 403);

            if ($spouse->spouse_id) {
                return response()->json(['message' => 'This family member already has a spouse selected.'], 422);
            }
        }

        $this->storeProfileImage($request, $validated);
        $validated['created_by_user_id'] = $user->id;

        $member = DB::transaction(function () use ($validated) {
            $member = FamilyMember::create($validated);

            if ($member->spouse_id) {
                FamilyMember::whereKey($member->spouse_id)->update(['spouse_id' => $member->id]);
            }

            return $member;
        });

        return response()->json($member->load('spouse'), 201);
    }

    public function update(Request $request, FamilyMember $member): JsonResponse
    {
        $validated = $this->validatedData($request);
        $user = $request->user();
        abort_if($user->isFamilyAdmin() && $member->created_by_user_id !== $user->id, 403);
        $oldSpouseId = (int) $member->spouse_id;
        $newSpouseId = array_key_exists('spouse_id', $validated) ? ($validated['spouse_id'] ? (int) $validated['spouse_id'] : null) : ($oldSpouseId ?: null);

        if ($user->isFamilyAdmin() && $newSpouseId !== ($oldSpouseId ?: null)) {
            foreach (array_filter([$oldSpouseId, $newSpouseId]) as $spouseId) {
                abort_unless(FamilyMember::whereKey($spouseId)->where('created_by_user_id', $user->id)->exists(), 403);
            }
        }

        if ($newSpouseId === $member->id) {
            return response()->json(['message' => 'A family member cannot be their own spouse.'], 422);
        }

        if (in_array($member->id, array_map('intval', [$validated['parent_id'] ?? 0, $validated['secondary_parent_id'] ?? 0]), true)) {
            return response()->json(['message' => 'A family member cannot be their own parent.'], 422);
        }

        if ($newSpouseId && $newSpouseId !== $oldSpouseId) {
            $newSpouse = FamilyMember::findOrFail($newSpouseId);
            if ($newSpouse->spouse_id && $newSpouse->spouse_id !== $member->id) {
                return response()->json(['message' => 'This family member already has a spouse selected.'], 422);
            }
        }

        $this->storeProfileImage($request, $validated);

        DB::transaction(function () use ($member, $validated, $newSpouseId, $oldSpouseId) {
            if ($oldSpouseId && $oldSpouseId !== $newSpouseId) {
                FamilyMember::whereKey($oldSpouseId)->where('spouse_id', $member->id)->update(['spouse_id' => null]);
            }

            $member->update($validated);

            if ($newSpouseId && $newSpouseId !== $oldSpouseId) {
                FamilyMember::whereKey($newSpouseId)->update(['spouse_id' => $member->id]);
            }
        });

        return response()->json($member->fresh()->load('spouse'));
    }

    public function destroy(FamilyMember $member): JsonResponse
    {
        $imagePath = $member->profile_image_path;

        DB::transaction(function () use ($member) {
            // Preserve the remaining family records while removing only relations
            // that point at the deleted person.
            FamilyMember::where('spouse_id', $member->id)->update(['spouse_id' => null]);
            FamilyMember::where('parent_id', $member->id)->update(['parent_id' => null]);
            FamilyMember::where('secondary_parent_id', $member->id)->update(['secondary_parent_id' => null]);
            $member->delete();
        });

        if ($imagePath) {
            Storage::disk('public')->delete($imagePath);
        }

        return response()->json(['message' => 'Family member deleted.']);
    }

    private function validatedData(Request $request): array
    {
        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:60'],
            'last_name' => ['nullable', 'string', 'max:60'],
            'birth_date' => ['nullable', 'date', 'before_or_equal:today'],
            'gender' => ['nullable', Rule::in(['female', 'male', 'other'])],
            'life_status' => ['nullable', Rule::in(['alive', 'deceased'])],
            'profile_image' => ['nullable', 'image', 'max:5120'],
            'parent_id' => ['nullable', 'integer', 'exists:family_members,id'],
            'secondary_parent_id' => ['nullable', 'integer', 'different:parent_id', 'exists:family_members,id'],
            'spouse_id' => ['nullable', 'integer', 'exists:family_members,id'],
            'address' => ['nullable', 'string', 'max:255'],
            'contact_number' => ['nullable', 'string', 'max:40'],
            'email' => ['nullable', 'email', 'max:255'],
            'google_maps_url' => ['nullable', 'url', 'max:2048'],
            'facebook_url' => ['nullable', 'url', 'max:2048'],
            'instagram_url' => ['nullable', 'url', 'max:2048'],
            'tiktok_url' => ['nullable', 'url', 'max:2048'],
            'x_url' => ['nullable', 'url', 'max:2048'],
        ]);

        if (($validated['parent_id'] ?? null) && ($validated['parent_id'] === ($validated['secondary_parent_id'] ?? null))) {
            abort(response()->json(['message' => 'Please select two different parents.'], 422));
        }

        return $validated;
    }

    private function storeProfileImage(Request $request, array &$validated): void
    {
        unset($validated['profile_image']);

        if ($request->hasFile('profile_image')) {
            $validated['profile_image_path'] = $request->file('profile_image')->store('family-members', 'public');
        }
    }
}
