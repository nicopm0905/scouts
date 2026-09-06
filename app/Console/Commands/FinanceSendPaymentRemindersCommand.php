<?php

namespace App\Console\Commands;

use App\Enums\ChargeStatus;
use App\Jobs\SendPaymentReminderJob;
use App\Models\ChargeMember;
use App\Models\Setting;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

/**
 * Encola los recordatorios de pago pendientes de enviar.
 *
 * Envía un recordatorio (una sola vez, vía reminder_sent_at) a los cobros
 * pendientes cuya fecha de vencimiento esté a X días vista o ya haya vencido,
 * siendo X configurable en Ajustes (Setting::finance.reminder_days_before,
 * por defecto 5 días).
 *
 * Este comando NO está cableado en el scheduler (lo hace el orquestador de
 * Fase 2). Para probarlo manualmente:
 *   php artisan charges:send-reminders
 *
 * Para automatizarlo, en routes/console.php o App\Console\Kernel añadir:
 *   Schedule::command('charges:send-reminders')->dailyAt('08:00');
 */
class FinanceSendPaymentRemindersCommand extends Command
{
    protected $signature = 'charges:send-reminders';

    protected $description = 'Encola los recordatorios de pago de cobros pendientes próximos a vencer o vencidos';

    public function handle(): int
    {
        $daysBefore = (int) Setting::get('finance.reminder_days_before', 5);
        $today = Carbon::today();
        $limit = $today->copy()->addDays($daysBefore);

        $pending = ChargeMember::query()
            ->with(['charge', 'member.families'])
            ->where('status', ChargeStatus::Pending->value)
            ->whereNull('reminder_sent_at')
            ->whereHas('charge', function ($query) use ($limit) {
                $query->whereNotNull('due_date')->whereDate('due_date', '<=', $limit);
            })
            ->get();

        // Separamos los cobros sin ningún email de contacto (ni familia ni miembro):
        // no tiene sentido encolarlos y el tesorero debe saber que hay que avisarles a mano.
        [$notifiable, $withoutEmail] = $pending->partition(
            fn (ChargeMember $cm) => $cm->member?->contactEmail() !== null
        );

        foreach ($notifiable as $chargeMember) {
            SendPaymentReminderJob::dispatch($chargeMember->id);
        }

        $this->info("Recordatorios encolados: {$notifiable->count()}.");

        if ($withoutEmail->isNotEmpty()) {
            $this->warn("Sin email de contacto (avisar por otro medio): {$withoutEmail->count()}.");
            foreach ($withoutEmail as $chargeMember) {
                $memberName = $chargeMember->member?->full_name ?? "Miembro #{$chargeMember->member_id}";
                $chargeTitle = $chargeMember->charge?->title ?? "Cobro #{$chargeMember->charge_id}";
                $this->line("  - {$memberName} · {$chargeTitle}");
            }
        }

        return self::SUCCESS;
    }
}
