<?php

namespace App\Http\Controllers\Members;

use App\Http\Controllers\Controller;
use App\Http\Requests\Members\AttachFamilyMemberRequest;
use App\Http\Requests\Members\StoreFamilyRequest;
use App\Models\Family;
use App\Models\Member;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class FamilyController extends Controller
{
    public function index(): Response
    {
        Gate::authorize('viewAny', Member::class);

        $families = Family::withCount('members')
            ->orderBy('name')
            ->get()
            ->map(fn (Family $f) => [
                'id' => $f->id,
                'name' => $f->name,
                'contact_phone' => $f->contact_phone,
                'contact_email' => $f->contact_email,
                'members_count' => $f->members_count,
            ]);

        return Inertia::render('Members/Families/Index', [
            'families' => $families,
            'canManage' => request()->user()->can('members.manage'),
        ]);
    }

    public function create(): Response
    {
        Gate::authorize('create', Member::class);

        return Inertia::render('Members/Families/Create');
    }

    public function store(StoreFamilyRequest $request): RedirectResponse
    {
        Family::create($request->validated());

        return redirect()->route('families.index')->with('success', 'Familia creada correctamente.');
    }

    public function edit(Family $family): Response
    {
        Gate::authorize('create', Member::class);

        $family->load('members');

        return Inertia::render('Members/Families/Edit', [
            'family' => [
                'id' => $family->id,
                'name' => $family->name,
                'contact_phone' => $family->contact_phone,
                'contact_email' => $family->contact_email,
                'notes' => $family->notes,
                'members' => $family->members->map(fn ($m) => [
                    'id' => $m->id,
                    'full_name' => $m->full_name,
                    'relationship' => $m->pivot->relationship,
                ]),
            ],
        ]);
    }

    public function update(StoreFamilyRequest $request, Family $family): RedirectResponse
    {
        $family->update($request->validated());

        return back()->with('success', 'Familia actualizada.');
    }

    public function destroy(Family $family): RedirectResponse
    {
        Gate::authorize('create', Member::class);

        $family->delete();

        return redirect()->route('families.index')->with('success', 'Familia eliminada.');
    }

    /** Vincula un miembro a una familia con un parentesco. */
    public function attach(AttachFamilyMemberRequest $request, Member $member): RedirectResponse
    {
        $data = $request->validated();

        $member->families()->syncWithoutDetaching([
            $data['family_id'] => ['relationship' => $data['relationship']],
        ]);

        return back()->with('success', 'Familiar vinculado.');
    }

    public function detach(Member $member, Family $family): RedirectResponse
    {
        Gate::authorize('update', $member);

        $member->families()->detach($family->id);

        return back()->with('success', 'Vínculo familiar eliminado.');
    }
}
