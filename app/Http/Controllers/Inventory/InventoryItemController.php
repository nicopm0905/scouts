<?php

namespace App\Http\Controllers\Inventory;

use App\Enums\InventoryCategory;
use App\Enums\ItemCondition;
use App\Enums\MemberRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Inventory\StoreInventoryItemRequest;
use App\Http\Requests\Inventory\UpdateInventoryItemRequest;
use App\Models\Event;
use App\Models\InventoryItem;
use App\Models\Member;
use App\Services\Drive\DriveServiceInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class InventoryItemController extends Controller
{
    public function __construct(private readonly DriveServiceInterface $drive)
    {
    }

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', InventoryItem::class);

        $items = InventoryItem::query()
            ->when($request->string('category')->toString(), fn ($q, $category) => $q->where('category', $category))
            ->when($request->string('condition')->toString(), fn ($q, $condition) => $q->where('condition', $condition))
            ->withCount(['checkouts as outstanding_checkouts_count' => fn ($q) => $q->outstanding()])
            ->orderBy('name')
            ->get()
            ->map(fn (InventoryItem $item) => [
                'id' => $item->id,
                'name' => $item->name,
                'category' => $item->category->value,
                'category_label' => $item->category->label(),
                'quantity' => $item->quantity,
                'available_quantity' => $item->availableQuantity(),
                'condition' => $item->condition->value,
                'condition_label' => $item->condition->label(),
                'condition_color' => $item->condition->badgeColor(),
                'location' => $item->location,
                'next_review_at' => $item->next_review_at?->toDateString(),
                'photo_url' => $item->photo_file_id ? $this->drive->thumbnailUrl($item->photo_file_id) : null,
                'outstanding_checkouts_count' => $item->outstanding_checkouts_count,
            ]);

        $needingReview = InventoryItem::needingReview(30)
            ->orderBy('next_review_at')
            ->get(['id', 'name', 'category', 'next_review_at'])
            ->map(fn (InventoryItem $item) => [
                'id' => $item->id,
                'name' => $item->name,
                'category_label' => $item->category->label(),
                'next_review_at' => $item->next_review_at?->toDateString(),
            ]);

        $overdueCheckouts = \App\Models\Checkout::query()
            ->outstanding()
            ->with(['inventoryItem:id,name', 'event:id,title', 'member:id,first_name,last_name'])
            ->get()
            ->filter(fn ($checkout) => $checkout->isOverdue())
            ->map(fn ($checkout) => [
                'id' => $checkout->id,
                'item_name' => $checkout->inventoryItem?->name,
                'event_title' => $checkout->event?->title,
                'member_name' => $checkout->member?->full_name,
                'expected_return_at' => $checkout->expected_return_at?->toDateString(),
            ])
            ->values();

        return Inertia::render('Inventory/Index', [
            'items' => $items,
            'needingReview' => $needingReview,
            'overdueCheckouts' => $overdueCheckouts,
            'filters' => $request->only(['category', 'condition']),
            'categories' => collect(InventoryCategory::cases())->map(fn ($c) => ['value' => $c->value, 'label' => $c->label()]),
            'conditions' => collect(ItemCondition::cases())->map(fn ($c) => ['value' => $c->value, 'label' => $c->label(), 'color' => $c->badgeColor()]),
            'events' => Event::query()
                ->where(function ($q) {
                    $q->whereNull('end_at')->orWhere('end_at', '>=', now());
                })
                ->orderBy('start_at')
                ->get(['id', 'title', 'start_at', 'end_at'])
                ->map(fn (Event $event) => [
                    'id' => $event->id,
                    'title' => $event->title,
                    'start_at' => $event->start_at?->toDateString(),
                    'end_at' => $event->end_at?->toDateString(),
                ]),
            'members' => Member::query()
                ->where('role', MemberRole::Responsable->value)
                ->active()
                ->orderBy('first_name')
                ->get(['id', 'first_name', 'last_name'])
                ->map(fn (Member $member) => ['id' => $member->id, 'name' => $member->full_name]),
            'can' => [
                'manage' => $request->user()->can('inventory.manage'),
                'reserve' => $request->user()->can('inventory.reserve') || $request->user()->can('inventory.manage'),
            ],
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', InventoryItem::class);

        return Inertia::render('Inventory/Form', [
            'item' => null,
            'categories' => collect(InventoryCategory::cases())->map(fn ($c) => ['value' => $c->value, 'label' => $c->label()]),
            'conditions' => collect(ItemCondition::cases())->map(fn ($c) => ['value' => $c->value, 'label' => $c->label()]),
        ]);
    }

    public function store(StoreInventoryItemRequest $request): RedirectResponse
    {
        $data = $request->safe()->except('photo');

        if ($request->hasFile('photo')) {
            $data['photo_file_id'] = $this->drive->upload($request->file('photo'))->id;
        }

        InventoryItem::create($data);

        return redirect()->route('inventory.index')->with('success', 'Ítem creado correctamente.');
    }

    public function edit(InventoryItem $item): Response
    {
        $this->authorize('update', $item);

        return Inertia::render('Inventory/Form', [
            'item' => [
                'id' => $item->id,
                'name' => $item->name,
                'category' => $item->category->value,
                'quantity' => $item->quantity,
                'condition' => $item->condition->value,
                'location' => $item->location,
                'next_review_at' => $item->next_review_at?->toDateString(),
                'notes' => $item->notes,
                'photo_url' => $item->photo_file_id ? $this->drive->thumbnailUrl($item->photo_file_id) : null,
            ],
            'categories' => collect(InventoryCategory::cases())->map(fn ($c) => ['value' => $c->value, 'label' => $c->label()]),
            'conditions' => collect(ItemCondition::cases())->map(fn ($c) => ['value' => $c->value, 'label' => $c->label()]),
        ]);
    }

    public function update(UpdateInventoryItemRequest $request, InventoryItem $item): RedirectResponse
    {
        $data = $request->safe()->except(['photo', 'remove_photo']);

        if ($request->hasFile('photo')) {
            if ($item->photo_file_id) {
                $this->drive->delete($item->photo_file_id);
            }
            $data['photo_file_id'] = $this->drive->upload($request->file('photo'))->id;
        } elseif ($request->boolean('remove_photo') && $item->photo_file_id) {
            $this->drive->delete($item->photo_file_id);
            $data['photo_file_id'] = null;
        }

        $item->update($data);

        return redirect()->route('inventory.index')->with('success', 'Ítem actualizado correctamente.');
    }

    public function destroy(InventoryItem $item): RedirectResponse
    {
        $this->authorize('delete', $item);

        if ($item->photo_file_id) {
            $this->drive->delete($item->photo_file_id);
        }

        $item->delete();

        return redirect()->route('inventory.index')->with('success', 'Ítem eliminado.');
    }
}
