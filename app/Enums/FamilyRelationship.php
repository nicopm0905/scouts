<?php

namespace App\Enums;

enum FamilyRelationship: string
{
    case Padre = 'padre';
    case Madre = 'madre';
    case Tutor = 'tutor';
    case Hermano = 'hermano';

    public function label(): string
    {
        return match ($this) {
            self::Padre => 'Padre',
            self::Madre => 'Madre',
            self::Tutor => 'Tutor/a legal',
            self::Hermano => 'Hermano/a',
        };
    }

    /** ¿Es un adulto de contacto (frente a un hermano scout)? */
    public function isGuardian(): bool
    {
        return $this !== self::Hermano;
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
