<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\UpdateFinanceSettingsRequest;
use App\Models\Setting;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

/** Ajustes del grupo: datos fiscales, % descuento hermanos, días de recordatorio. */
class SettingsController extends Controller
{
    use AuthorizesRequests;

    private const KEYS = [
        'group_name' => 'finance.group_name',
        'group_tax_id' => 'finance.group_tax_id',
        'group_address' => 'finance.group_address',
        'group_postal_code' => 'finance.group_postal_code',
        'group_city' => 'finance.group_city',
        'group_email' => 'finance.group_email',
        'group_phone' => 'finance.group_phone',
        'sibling_discount_percent' => 'finance.sibling_discount_percent',
        'reminder_days_before' => 'finance.reminder_days_before',
    ];

    public function edit(): Response
    {
        $this->authorize('settings.manage');

        $values = [];
        foreach (self::KEYS as $field => $settingKey) {
            $values[$field] = Setting::get($settingKey);
        }
        $values['sibling_discount_percent'] ??= 0;
        $values['reminder_days_before'] ??= 5;

        return Inertia::render('Settings/Edit', [
            'settings' => $values,
        ]);
    }

    public function update(UpdateFinanceSettingsRequest $request): RedirectResponse
    {
        foreach (self::KEYS as $field => $settingKey) {
            Setting::set($settingKey, $request->validated($field));
        }

        return back()->with('success', 'Ajustes guardados.');
    }
}
