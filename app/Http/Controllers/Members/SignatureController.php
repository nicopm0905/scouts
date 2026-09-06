<?php

namespace App\Http\Controllers\Members;

use App\Enums\ConsentType;
use App\Enums\SignatureStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Members\SendSignatureRequest;
use App\Jobs\SendSignatureRequestJob;
use App\Models\Consent;
use App\Models\Document;
use App\Models\Member;
use App\Models\Signature;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

/** Disparado por la secretaría: envía a firma digital un Consent o un Document. */
class SignatureController extends Controller
{
    use AuthorizesRequests;

    public function sendConsent(Member $member, string $type): RedirectResponse
    {
        Gate::authorize('viewSensitive', $member);

        $consentType = ConsentType::from($type);

        $consent = $member->consents()->firstOrCreate(
            ['type' => $consentType->value],
            ['granted' => false]
        );

        $signature = $this->createOrRefreshSignature($consent, $member);

        SendSignatureRequestJob::dispatch($signature->id);

        return back()->with('success', 'Solicitud de firma enviada a la familia.');
    }

    public function sendDocument(SendSignatureRequest $request, Document $document): RedirectResponse
    {
        $this->authorize('update', $document);

        $members = Member::whereIn('id', $request->validated()['member_ids'])->get();

        foreach ($members as $member) {
            $signature = $this->createOrRefreshSignature($document, $member);
            SendSignatureRequestJob::dispatch($signature->id);
        }

        return back()->with('success', 'Solicitud de firma enviada a '.$members->count().' miembro(s).');
    }

    private function createOrRefreshSignature(Consent|Document $signable, Member $member): Signature
    {
        return Signature::updateOrCreate(
            [
                'signable_type' => $signable->getMorphClass(),
                'signable_id' => $signable->id,
                'member_id' => $member->id,
            ],
            [
                'public_token' => Str::random(48),
                'status' => SignatureStatus::Pending,
                'sent_at' => null,
                'created_by' => auth()->id(),
            ]
        );
    }
}
