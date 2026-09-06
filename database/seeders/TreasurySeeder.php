<?php

namespace Database\Seeders;

use App\Enums\EventType;
use App\Enums\InvoiceCategory;
use App\Enums\InvoiceDirection;
use App\Enums\InvoiceStatus;
use App\Models\Budget;
use App\Models\BudgetItem;
use App\Models\Event;
use App\Models\Invoice;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class TreasurySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Crear un Evento Dummy
        $event = Event::create([
            'title' => 'Campamento de Verano 2026',
            'type' => EventType::Campamento,
            'start_at' => Carbon::now()->addMonths(2),
            'end_at' => Carbon::now()->addMonths(2)->addDays(15),
            'location' => 'Lugar del Campamento',
        ]);

        // 2. Crear un Presupuesto para el evento
        $budget = Budget::create([
            'name' => 'Presupuesto Campamento Verano 2026',
            'description' => 'Presupuesto general para el campamento',
            'event_id' => $event->id,
        ]);

        // 3. Crear Líneas de Presupuesto
        $items = [
            ['description' => 'Autobús', 'type' => 'expense', 'amount' => 1500.00],
            ['description' => 'Comida', 'type' => 'expense', 'amount' => 3000.00],
            ['description' => 'Material Castores', 'type' => 'expense', 'amount' => 150.00],
            ['description' => 'Material Lobatos', 'type' => 'expense', 'amount' => 200.00],
            ['description' => 'Material Exploradores', 'type' => 'expense', 'amount' => 250.00],
            ['description' => 'Cuotas Inscripción', 'type' => 'income', 'amount' => 5000.00],
            ['description' => 'Subvención MSC', 'type' => 'income', 'amount' => 800.00],
        ];

        $budgetItems = [];
        foreach ($items as $item) {
            $budgetItems[] = BudgetItem::create(array_merge($item, ['budget_id' => $budget->id]));
        }

        // 4. Crear Facturas (Invoices) simuladas
        $user = User::first();
        $userId = $user ? $user->id : null;

        $invoicesData = [
            [
                'direction' => InvoiceDirection::Received,
                'number' => null,
                'date' => Carbon::now()->subDays(10),
                'supplier_or_client' => 'Autocares Paco',
                'concept' => 'Reserva Autobús',
                'amount' => 500.00,
                'vat' => 105.00,
                'category' => InvoiceCategory::Transporte,
                'status' => InvoiceStatus::Submitted,
                'budget_item_id' => $budgetItems[0]->id, // Autobús
                'created_by' => $userId,
            ],
            [
                'direction' => InvoiceDirection::Received,
                'number' => null,
                'date' => Carbon::now()->subDays(5),
                'supplier_or_client' => 'Supermercados Lupa',
                'concept' => 'Compra inicial comida',
                'amount' => 800.00,
                'vat' => 80.00,
                'category' => InvoiceCategory::Comida,
                'status' => InvoiceStatus::Draft,
                'budget_item_id' => $budgetItems[1]->id, // Comida
                'created_by' => $userId,
            ],
            [
                'direction' => InvoiceDirection::Received,
                'number' => null,
                'date' => Carbon::now()->subDays(2),
                'supplier_or_client' => 'Papelería Folder',
                'concept' => 'Cartulinas y tijeras',
                'amount' => 45.00,
                'vat' => 9.45,
                'category' => InvoiceCategory::Material,
                'status' => InvoiceStatus::Approved,
                'budget_item_id' => $budgetItems[2]->id, // Material Castores
                'created_by' => $userId,
            ],
            [
                'direction' => InvoiceDirection::Issued,
                'number' => 'F-2026-001',
                'date' => Carbon::now(),
                'supplier_or_client' => 'MSC Delegación',
                'concept' => 'Subvención anual parte 1',
                'amount' => 400.00,
                'vat' => 0,
                'category' => InvoiceCategory::Subvencion,
                'status' => InvoiceStatus::Submitted,
                'budget_item_id' => $budgetItems[6]->id, // Subvención
                'created_by' => $userId,
            ],
        ];

        foreach ($invoicesData as $data) {
            Invoice::create($data);
        }
    }
}
