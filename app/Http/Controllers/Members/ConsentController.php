<?php

namespace App\Http\Controllers\Members;

use App\Http\Controllers\Controller;
use App\Http\Requests\Members\UpdateConsentsRequest;
use App\Models\Member;
use Illuminate\Http\RedirectResponse;

class ConsentController extends Controller
{
    public function update(UpdateConsentsRequest $request, Member $member): RedirectResponse
    {
        foreach ($request->validated()['consents'] as $consent) {
            $member->consents()->updateOrCreate(
                ['member_id' => $member->id, 'type' => $consent['type']],
                [
                    'granted' => $consent['granted'] ?? false,
                    'signed_at' => $consent['signed_at'] ?? null,
                ]
            );
        }

        activity()->causedBy($request->user())->performedOn($member)->log('Actualización de consentimientos');

        return back()->with('success', 'Consentimientos actualizados.');
    }
}
