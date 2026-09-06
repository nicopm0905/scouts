<?php

namespace App\Console\Commands;

use App\Enums\UserRole;
use App\Mail\ExpiryAlertMail;
use App\Models\Document;
use App\Models\InventoryItem;
use App\Models\LeaderProfile;
use App\Models\LeaderTraining;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Revisa caducidades próximas (documentos, certificados de delitos sexuales, titulaciones
 * de monitores y revisiones de inventario) y, si hay algo que atender, envía UN email
 * resumen diario a los usuarios activos con rol admin o secretaría (quienes gestionan
 * la documentación según RolesAndPermissionsSeeder). Pensado para el scheduler diario.
 * El dashboard muestra estas mismas cifras de forma visual.
 */
class SendExpiryAlertsCommand extends Command
{
    protected $signature = 'alerts:expiry {--days=30 : Ventana de días para considerar "próximo a caducar"}';

    protected $description = 'Envía por email las alertas de documentos, certificados, titulaciones e inventario próximos a caducar/revisar';

    public function handle(): int
    {
        $days = (int) $this->option('days');

        $documents = Document::expiringWithin($days)->orderBy('expires_at')->get();

        $certificates = LeaderProfile::query()
            ->with('member')
            ->whereNotNull('sexual_offenses_certificate_expires_at')
            ->where('sexual_offenses_certificate_expires_at', '<=', now()->addDays($days))
            ->orderBy('sexual_offenses_certificate_expires_at')
            ->get();

        $trainings = LeaderTraining::query()
            ->with('leaderProfile.member')
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now()->addDays($days))
            ->orderBy('expires_at')
            ->get();

        $inventory = InventoryItem::needingReview($days)->count();

        $summary = [
            'documents' => $documents->count(),
            'certificates' => $certificates->count(),
            'trainings' => $trainings->count(),
            'inventory' => $inventory,
        ];

        $this->table(
            ['Tipo', 'Nº próximos a caducar/revisar'],
            [
                ['Documentos', $summary['documents']],
                ['Certificados delitos sexuales', $summary['certificates']],
                ['Titulaciones de monitores', $summary['trainings']],
                ['Inventario a revisar', $summary['inventory']],
            ]
        );

        Log::info('Alertas de caducidad generadas', $summary);

        $total = array_sum($summary);

        if ($total === 0) {
            $this->info('Sin caducidades próximas: no se envía email.');

            return self::SUCCESS;
        }

        // Un único email resumen a quienes gestionan la documentación (admin + secretaría).
        $recipients = User::query()
            ->role([UserRole::Admin->value, UserRole::Secretaria->value])
            ->where('active', true)
            ->pluck('email')
            ->unique()
            ->values();

        if ($recipients->isEmpty()) {
            $this->warn('Hay caducidades pero ningún usuario admin/secretaría activo al que avisar.');

            return self::SUCCESS;
        }

        Mail::to($recipients->all())->send(
            new ExpiryAlertMail($documents, $certificates, $trainings, $inventory, $days)
        );

        $this->info("Email resumen enviado a {$recipients->count()} destinatario(s).");

        return self::SUCCESS;
    }
}
