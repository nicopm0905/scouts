<?php

namespace App\Http\Controllers\Members;

use App\Http\Controllers\Controller;
use App\Http\Requests\Members\UpdateHealthRecordRequest;
use App\Models\Member;
use App\Services\Drive\DriveServiceInterface;
use Illuminate\Http\RedirectResponse;

class HealthRecordController extends Controller
{
    public function __construct(private readonly DriveServiceInterface $drive)
    {
    }

    public function update(UpdateHealthRecordRequest $request, Member $member): RedirectResponse
    {
        $data = $request->validated();

        if ($request->hasFile('attachment')) {
            $uploaded = $this->drive->upload($request->file('attachment'), null, "ficha_sanitaria_{$member->id}");
            $data['drive_file_id'] = $uploaded->id;
        }
        unset($data['attachment']);

        $member->healthRecord()->updateOrCreate(['member_id' => $member->id], $data);

        activity()->causedBy($request->user())->performedOn($member)->log('Actualización de ficha sanitaria');

        return back()->with('success', 'Ficha sanitaria actualizada.');
    }
}
