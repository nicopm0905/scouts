<?php

namespace App\Enums;

enum LeaderQualification: string
{
    case None = 'none';
    case InTraining = 'in_training';
    case Monitor = 'monitor';
    case Director = 'director';

    public function label(): string
    {
        return match ($this) {
            self::None => 'Sin titulación',
            self::InTraining => 'En prácticas',
            self::Monitor => 'Monitor',
            self::Director => 'Director',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
