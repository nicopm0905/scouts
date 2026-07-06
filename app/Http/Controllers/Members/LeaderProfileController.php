<?php

namespace App\Http\Controllers\Members;

use App\Http\Controllers\Controller;
use App\Http\Requests\Members\UpdateLeaderProfileRequest;
use App\Models\Member;
use Illuminate\Http\RedirectResponse;

class LeaderProfileController extends Controller
{
    public function update(UpdateLeaderProfileRequest $request, Member $member): RedirectResponse
    {
        $member->leaderProfile()->updateOrCreate(
            ['member_id' => $member->id],
            $request->validated()
        );

        return back()->with('success', 'Perfil de responsable actualizado.');
    }
}
