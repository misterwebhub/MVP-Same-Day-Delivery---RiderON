<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Profile\StoreSavedContactRequest;
use App\Http\Requests\Profile\UpdateProfileRequest;
use App\Http\Resources\ProfileResource;
use App\Http\Resources\SavedContactResource;
use App\Models\SavedContact;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $user = $request->user();
        $user->loadMissing('customerProfile');

        return $this->success(new ProfileResource($user));
    }

    public function update(UpdateProfileRequest $request): JsonResponse
    {
        $user = $request->user();

        $user->forceFill([
            'name' => $request->string('name')->toString(),
            'email' => $request->input('email'),
        ])->save();

        if ($request->has('preferred_language')) {
            $user->customerProfile()->updateOrCreate(
                ['user_id' => $user->id],
                ['preferred_language' => $request->string('preferred_language')->toString()],
            );
        }

        $user->refresh()->load('customerProfile');

        return $this->success(new ProfileResource($user), 'Profile updated.');
    }

    public function savedContacts(Request $request): JsonResponse
    {
        $contacts = SavedContact::query()
            ->where('customer_id', $request->user()->id)
            ->when($request->query('type'), fn ($query, $type) => $query->where('type', $type))
            ->orderByDesc('id')
            ->get();

        return $this->success(SavedContactResource::collection($contacts));
    }

    public function storeSavedContact(StoreSavedContactRequest $request): JsonResponse
    {
        $contact = SavedContact::create([
            'customer_id' => $request->user()->id,
            'type' => $request->string('type')->toString(),
            'label' => $request->input('label'),
            'name' => $request->string('name')->toString(),
            'phone' => $request->string('phone')->toString(),
            'station_id' => $request->input('station_id'),
            'landmark' => $request->input('landmark'),
        ]);

        return $this->success(new SavedContactResource($contact), 'Saved contact created.', 201);
    }

    public function destroySavedContact(Request $request, SavedContact $contact): JsonResponse
    {
        abort_unless($contact->customer_id === $request->user()->id, 403);

        $contact->delete();

        return $this->success(null, 'Saved contact deleted.');
    }
}
