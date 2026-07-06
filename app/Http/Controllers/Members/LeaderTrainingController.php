<?php

namespace App\Http\Controllers\Members;

use App\Http\Controllers\Controller;
use App\Http\Requests\Members\StoreLeaderTrainingRequest;
use App\Models\LeaderTraining;
use App\Models\Member;
use App\Services\Drive\DriveServiceInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class LeaderTrainingController extends Controller
{
    public function __construct(private readonly DriveServiceInterface $drive)
    {
    }

    public function store(StoreLeaderTrainingRequest $request, Member $member): RedirectResponse
    {
        $data = $request->validated();

        if ($request->hasFile('attachment')) {
            $uploaded = $this->drive->upload($request->file('attachment'), null, "formacion_{$member->id}_{$data['name']}");
            $data['drive_file_id'] = $uploaded->id;
        }
        unset($data['attachment']);

        $profile = $member->leaderProfile()->firstOrCreate(['member_id' => $member->id]);
        $profile->trainings()->create($data);

        return back()->with('success', 'Formación añadida.');
    }

    public function destroy(LeaderTraining $leaderTraining): RedirectResponse
    {
        $member = $leaderTraining->leaderProfile->member;
        Gate::authorize('update', $member);

        $leaderTraining->delete();

        return back()->with('success', 'Formación eliminada.');
    }
}
