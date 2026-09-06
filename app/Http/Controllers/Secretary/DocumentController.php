<?php

namespace App\Http\Controllers\Secretary;

use App\Enums\DocumentCategory;
use App\Http\Controllers\Controller;
use App\Http\Requests\Secretary\StoreDocumentRequest;
use App\Http\Requests\Secretary\UpdateDocumentRequest;
use App\Models\Document;
use App\Models\Member;
use App\Services\Drive\DriveServiceInterface;
use App\Services\Drive\DriveStructureService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DocumentController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private readonly DriveServiceInterface $drive,
        private readonly DriveStructureService $driveStructure
    ) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Document::class);

        $documents = Document::query()
            ->with(['creator', 'signatures.member'])
            ->orderByDesc('created_at')
            ->get()
            ->groupBy(fn (Document $document) => $document->category->value);

        $categories = collect(DocumentCategory::cases())->map(fn (DocumentCategory $category) => [
            'value' => $category->value,
            'label' => $category->label(),
            'documents' => ($documents->get($category->value) ?? collect())->map(fn (Document $document) => $this->present($document))->values(),
        ])->values();

        $expiring = Document::expiringWithin(30)->orderBy('expires_at')->get()->map(fn (Document $document) => $this->present($document));

        return Inertia::render('Documents/Index', [
            'categories' => $categories,
            'expiring' => $expiring,
            'members' => Member::active()->orderBy('first_name')->get()->map(fn (Member $member) => [
                'id' => $member->id,
                'full_name' => $member->full_name,
            ]),
            'can' => [
                'manage' => $request->user()->can('create', Document::class),
            ],
        ]);
    }

    public function store(StoreDocumentRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $driveFileId = null;
        if ($request->hasFile('file')) {
            $folderId = null;
            try {
                $folderId = $this->driveStructure->getOrCreateSecretaryFolder();
            } catch (\Exception $e) {
                report($e);
            }
            $driveFileId = $this->drive->upload($request->file('file'), $folderId)->id;
        }

        Document::create([
            'title' => $data['title'],
            'category' => $data['category'],
            'drive_file_id' => $driveFileId,
            'external_url' => $data['external_url'] ?? null,
            'expires_at' => $data['expires_at'] ?? null,
            'notes' => $data['notes'] ?? null,
            'created_by' => $request->user()->id,
        ]);

        return back()->with('success', 'Documento subido correctamente.');
    }

    public function update(UpdateDocumentRequest $request, Document $document): RedirectResponse
    {
        $data = $request->validated();

        if ($request->hasFile('file')) {
            $folderId = null;
            try {
                $folderId = $this->driveStructure->getOrCreateSecretaryFolder();
            } catch (\Exception $e) {
                report($e);
            }
            $data['drive_file_id'] = $this->drive->upload($request->file('file'), $folderId)->id;
        }

        $document->update([
            'title' => $data['title'],
            'category' => $data['category'],
            'drive_file_id' => $data['drive_file_id'] ?? $document->drive_file_id,
            'external_url' => $data['external_url'] ?? null,
            'expires_at' => $data['expires_at'] ?? null,
            'notes' => $data['notes'] ?? null,
        ]);

        return back()->with('success', 'Documento actualizado correctamente.');
    }

    public function destroy(Document $document): RedirectResponse
    {
        $this->authorize('delete', $document);

        if ($document->drive_file_id) {
            $this->drive->delete($document->drive_file_id);
        }

        $document->delete();

        return back()->with('success', 'Documento eliminado correctamente.');
    }

    private function present(Document $document): array
    {
        return [
            'id' => $document->id,
            'title' => $document->title,
            'category' => $document->category->value,
            'drive_file_id' => $document->drive_file_id,
            'external_url' => $document->external_url,
            'web_view_link' => $document->drive_file_id ? $this->drive->webViewLink($document->drive_file_id) : null,
            'expires_at' => $document->expires_at?->format('Y-m-d'),
            'is_expired' => $document->isExpired(),
            'notes' => $document->notes,
            'creator' => $document->creator?->name,
            'created_at' => $document->created_at->format('Y-m-d'),
            'signatures' => $document->signatures->map(fn ($signature) => [
                'member_id' => $signature->member_id,
                'member_name' => $signature->member?->full_name,
                'status' => $signature->status->value,
                'status_label' => $signature->status->label(),
                'signed_document_url' => $signature->signed_drive_file_id
                    ? $this->drive->webViewLink($signature->signed_drive_file_id)
                    : null,
            ]),
        ];
    }
}
