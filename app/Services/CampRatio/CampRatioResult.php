<?php

namespace App\Services\CampRatio;

/**
 * Resultado de la validación legal de un evento (tipo semáforo).
 * status global: 'ok' (✅), 'warning' (⚠️), 'fail' (❌).
 */
class CampRatioResult
{
    /** @param array<int, array{key: string, status: string, message: string}> $checks */
    public function __construct(
        public readonly string $status,
        public readonly array $checks,
        public readonly int $participants,
        public readonly int $leaders,
        public readonly int $requiredLeaders,
        public readonly int $ratio,
        public readonly bool $majorityUnder12,
    ) {
    }

    public function passes(): bool
    {
        return $this->status === 'ok';
    }

    public function toArray(): array
    {
        return [
            'status' => $this->status,
            'checks' => $this->checks,
            'participants' => $this->participants,
            'leaders' => $this->leaders,
            'required_leaders' => $this->requiredLeaders,
            'ratio' => $this->ratio,
            'majority_under_12' => $this->majorityUnder12,
        ];
    }
}
