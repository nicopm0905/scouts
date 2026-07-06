<?php

namespace App\Providers;

use App\Enums\UserRole;
use App\Models\Activity;
use App\Models\Album;
use App\Models\BranchPlan;
use App\Models\Charge;
use App\Models\Document;
use App\Models\Event;
use App\Models\HistoryEntry;
use App\Models\InventoryItem;
use App\Models\Invoice;
use App\Models\Member;
use App\Models\Minute;
use App\Policies\ActivityPolicy;
use App\Policies\AlbumPolicy;
use App\Policies\BranchPlanPolicy;
use App\Policies\ChargePolicy;
use App\Policies\DocumentPolicy;
use App\Policies\EventPolicy;
use App\Policies\HistoryEntryPolicy;
use App\Policies\InventoryItemPolicy;
use App\Policies\InvoicePolicy;
use App\Policies\MemberPolicy;
use App\Policies\MinutePolicy;
use App\Models\User;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        Member::class => MemberPolicy::class,
        Charge::class => ChargePolicy::class,
        Invoice::class => InvoicePolicy::class,
        Event::class => EventPolicy::class,
        Document::class => DocumentPolicy::class,
        Minute::class => MinutePolicy::class,
        BranchPlan::class => BranchPlanPolicy::class,
        Activity::class => ActivityPolicy::class,
        InventoryItem::class => InventoryItemPolicy::class,
        Album::class => AlbumPolicy::class,
        HistoryEntry::class => HistoryEntryPolicy::class,
    ];

    public function boot(): void
    {
        // El admin (coordinación de grupo) puede todo.
        Gate::before(function (User $user, string $ability) {
            return $user->hasRole(UserRole::Admin->value) ? true : null;
        });
    }
}
