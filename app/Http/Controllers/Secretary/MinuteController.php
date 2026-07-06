<?php

namespace App\Http\Controllers\Secretary;

use App\Enums\DocumentCategory;
use App\Enums\MemberRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Secretary\StoreMinuteRequest;
use App\Http\Requests\Secretary\UpdateMinuteRequest;
use App\Models\Member;
use App\Models\Minute;
use App\Services\Drive\DriveServiceInterface;
use App\Services\Secretary\MinutePdfService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class MinuteController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private readonly DriveServiceInterface $drive,
        private readonly MinutePdfService $pdfService,
    ) {
    }

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Minute::class);

        $minutes = Minute::query()
            ->with(['creator', 'attendees', 'items'])
            ->orderByDesc('held_on')
            ->get()
            ->map(fn (Minute $minute) => $this->present($minute));

        return Inertia::render('Minutes/Index', [
            'minutes' => $minutes,
            'attendeeOptions' => $this->attendeeOptions(),
            'types' => [
                ['value' => DocumentCategory::ActasConsejo->value, 'label' => DocumentCategory::ActasConsejo->label()],
                ['value' => DocumentCategory::ActasAsamblea->value, 'label' => DocumentCategory::ActasAsamblea->label()],
            ],
            'can' => [
                'manage' => $request->user()->can('create', Minute::class),
            ],
        ]);
    }

    public function store(StoreMinuteRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $minute = DB::transaction(function () use ($data, $request) {
            $minute = Minute::create([
                'title' => $data['title'],
                'type' => $data['type'],
                'held_on' => $data['held_on'],
                'location' => $data['location'] ?? null,
                'created_by' => $request->user()->id,
            ]);

            $minute->attendees()->sync($data['attendee_ids'] ?? []);

            foreach ($data['items'] ?? [] as $position => $item) {
                $minute->items()->create([
                    'position' => $position,
                    'topic' => $item['topic'],
                    'discussion' => $item['discussion'] ?? null,
                    'agreement' => $item['agreement'] ?? null,
                ]);
            }

            return $minute;
        });

        $this->generatePdf($minute);

        return back()->with('success', 'Acta creada y PDF generado correctamente.');
    }

    public function update(UpdateMinuteRequest $request, Minute $minute): RedirectResponse
    {
        $data = $request->validated();

        DB::transaction(function () use ($data, $minute) {
            $minute->update([
                'title' => $data['title'],
                'type' => $data['type'],
                'held_on' => $data['held_on'],
                'location' => $data['location'] ?? null,
            ]);

            $minute->attendees()->sync($data['attendee_ids'] ?? []);

            $minute->items()->delete();
            foreach ($data['items'] ?? [] as $position => $item) {
                $minute->items()->create([
                    'position' => $position,
                    'topic' => $item['topic'],
                    'discussion' => $item['discussion'] ?? null,
                    'agreement' => $item['agreement'] ?? null,
                ]);
            }
        });

        $this->generatePdf($minute);

        return back()->with('success', 'Acta actualizada y PDF regenerado correctamente.');
    }

    public function destroy(Minute $minute): RedirectResponse
    {
        $this->authorize('delete', $minute);

        if ($minute->drive_file_id) {
            $this->drive->delete($minute->drive_file_id);
        }

        $minute->delete();

        return back()->with('success', 'Acta eliminada correctamente.');
    }

    private function generatePdf(Minute $minute): void
    {
        $file = $this->pdfService->generateAndUpload($minute);
        $minute->update(['drive_file_id' => $file->id]);
    }

    private function attendeeOptions(): array
    {
        return Member::query()
            ->where('role', MemberRole::Responsable)
            ->orderBy('first_name')
            ->get()
            ->map(fn (Member $member) => [
                'value' => $member->id,
                'label' => $member->full_name,
            ])->all();
    }

    private function present(Minute $minute): array
    {
        return [
            'id' => $minute->id,
            'title' => $minute->title,
            'type' => $minute->type,
            'held_on' => $minute->held_on->format('Y-m-d'),
            'location' => $minute->location,
            'drive_file_id' => $minute->drive_file_id,
            'web_view_link' => $minute->drive_file_id ? $this->drive->webViewLink($minute->drive_file_id) : null,
            'attendee_ids' => $minute->attendees->pluck('id')->all(),
            'attendees' => $minute->attendees->pluck('full_name')->all(),
            'items' => $minute->items->map(fn ($item) => [
                'topic' => $item->topic,
                'discussion' => $item->discussion,
                'agreement' => $item->agreement,
            ])->values(),
            'creator' => $minute->creator?->name,
        ];
    }
}
