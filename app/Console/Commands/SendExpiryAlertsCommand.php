<?php

namespace App\Console\Commands;

use App\Models\Document;
use App\Models\InventoryItem;
use App\Models\LeaderProfile;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Revisa caducidades próximas (documentos, certificados de delitos sexuales, revisiones
 * de inventario) y deja un aviso en el log. Pensado para ejecutarse a diario vía scheduler.
 * El dashboard muestra estas mismas cifras de forma visual.
 */
class SendExpiryAlertsCommand extends Command
{
    protected $signature = 'alerts:expiry {--days=30 : Ventana de días para considerar "próximo a caducar"}';

    protected $description = 'Genera alertas de documentos, certificados e inventario próximos a caducar/revisar';

    public function handle(): int
    {
        $days = (int) $this->option('days');

        $documents = Document::expiringWithin($days)->count();

        $certificates = LeaderProfile::query()
            ->whereNotNull('sexual_offenses_certificate_expires_at')
            ->where('sexual_offenses_certificate_expires_at', '<=', now()->addDays($days))
            ->count();

        $inventory = InventoryItem::needingReview($days)->count();

        $summary = compact('documents', 'certificates', 'inventory');

        $this->table(
            ['Tipo', 'Nº próximos a caducar/revisar'],
            [
                ['Documentos', $documents],
                ['Certificados delitos sexuales', $certificates],
                ['Inventario a revisar', $inventory],
            ]
        );

        Log::info('Alertas de caducidad generadas', $summary);

        // Punto de extensión: enviar un digest por email a admin/secretaría si hay avisos.
        // (Se deja preparado; el canal de correo se configura en producción.)

        return self::SUCCESS;
    }
}
