<?php

namespace App\Services\CampRatio;

use App\Enums\MemberRole;
use App\Models\Event;
use App\Models\Member;
use Illuminate\Support\Collection;

/**
 * Valida las ratios legales de responsables para una actividad con pernocta/salida
 * en Andalucía (Decreto 45/2000 y Decreto 89/2018).
 *
 * Reglas:
 *  - 1 responsable / 10 participantes si la mayoría es menor de 12 años; 1 / 15 si la
 *    mayoría tiene 12 o más (se calcula por edad a la fecha de inicio del evento).
 *  - Debe haber al menos un responsable con qualification = director.
 *  - Los responsables en prácticas (in_training) no pueden superar el 33% del total.
 *  - Aviso si algún responsable tiene el certificado de delitos sexuales caducado/ausente.
 */
class CampRatioValidator
{
    private const MAX_IN_TRAINING_RATIO = 1 / 3;

    public function validate(Event $event): CampRatioResult
    {
        $enrolled = $event->members()
            ->wherePivot('enrolled', true)
            ->with('leaderProfile')
            ->get();

        $reference = $event->start_at ?? now();

        $participants = $enrolled->filter(fn (Member $m) => $m->role !== MemberRole::Responsable);
        $leaders = $enrolled->filter(fn (Member $m) => $m->role === MemberRole::Responsable);

        $participantCount = $participants->count();
        $leaderCount = $leaders->count();

        // ¿Mayoría de participantes menor de 12 a fecha de inicio?
        $under12 = $participants->filter(
            fn (Member $m) => $m->birth_date && $m->birth_date->diffInYears($reference) < 12
        )->count();
        $majorityUnder12 = $participantCount > 0 && $under12 >= ($participantCount / 2);

        $ratio = $majorityUnder12 ? 10 : 15;
        $requiredLeaders = $participantCount > 0 ? (int) ceil($participantCount / $ratio) : 0;

        $checks = [];
        $checks[] = $this->checkRatio($leaderCount, $requiredLeaders, $ratio);
        $checks[] = $this->checkDirector($leaders);
        $checks[] = $this->checkInTraining($leaders, $leaderCount);
        $checks = array_merge($checks, $this->checkCertificates($leaders));

        return new CampRatioResult(
            status: $this->overallStatus($checks),
            checks: $checks,
            participants: $participantCount,
            leaders: $leaderCount,
            requiredLeaders: $requiredLeaders,
            ratio: $ratio,
            majorityUnder12: $majorityUnder12,
        );
    }

    private function checkRatio(int $leaders, int $required, int $ratio): array
    {
        if ($leaders >= $required) {
            return $this->pass('ratio', "Ratio cumplida: {$leaders} responsables para un mínimo de {$required} (1 por cada {$ratio}).");
        }

        return $this->fail('ratio', "Faltan responsables: hay {$leaders} y se necesitan al menos {$required} (1 por cada {$ratio} participantes).");
    }

    private function checkDirector(Collection $leaders): array
    {
        $hasDirector = $leaders->contains(
            fn (Member $m) => $m->leaderProfile?->isDirector() === true
        );

        return $hasDirector
            ? $this->pass('director', 'Hay al menos un responsable con titulación de director.')
            : $this->fail('director', 'No hay ningún responsable con titulación de director para dirigir la actividad.');
    }

    private function checkInTraining(Collection $leaders, int $leaderCount): array
    {
        if ($leaderCount === 0) {
            return $this->fail('in_training', 'No hay responsables inscritos.');
        }

        $inTraining = $leaders->filter(
            fn (Member $m) => $m->leaderProfile?->isInTraining() === true
        )->count();

        $maxAllowed = (int) floor($leaderCount * self::MAX_IN_TRAINING_RATIO);

        if ($inTraining <= $maxAllowed) {
            return $this->pass('in_training', "Responsables en prácticas dentro del límite ({$inTraining} de {$leaderCount}, máx. 33%).");
        }

        return $this->fail('in_training', "Demasiados responsables en prácticas: {$inTraining} de {$leaderCount} superan el 33% permitido (máx. {$maxAllowed}).");
    }

    private function checkCertificates(Collection $leaders): array
    {
        $problems = $leaders->filter(
            fn (Member $m) => ! ($m->leaderProfile?->hasValidSexualOffensesCertificate() ?? false)
        );

        if ($problems->isEmpty()) {
            return [$this->pass('certificates', 'Todos los responsables tienen el certificado de delitos sexuales vigente.')];
        }

        $names = $problems->map(fn (Member $m) => $m->full_name)->implode(', ');

        return [$this->warn('certificates', "Certificado de delitos sexuales caducado o ausente: {$names}.")];
    }

    /** @param array<int, array{status: string}> $checks */
    private function overallStatus(array $checks): string
    {
        $statuses = array_column($checks, 'status');

        if (in_array('fail', $statuses, true)) {
            return 'fail';
        }

        if (in_array('warning', $statuses, true)) {
            return 'warning';
        }

        return 'ok';
    }

    private function pass(string $key, string $message): array
    {
        return ['key' => $key, 'status' => 'ok', 'message' => $message];
    }

    private function warn(string $key, string $message): array
    {
        return ['key' => $key, 'status' => 'warning', 'message' => $message];
    }

    private function fail(string $key, string $message): array
    {
        return ['key' => $key, 'status' => 'fail', 'message' => $message];
    }
}
