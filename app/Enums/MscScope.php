<?php

namespace App\Enums;

/**
 * Ámbitos del plan de rama MSC: los tres bloques en los que se reparte
 * la programación trimestral y la ficha de salida de la delegación.
 */
enum MscScope: string
{
    case Responsabilidad = 'responsabilidad';
    case Pais = 'pais';
    case Fe = 'fe';

    public function label(): string
    {
        return match ($this) {
            self::Responsabilidad => 'Responsabilidad',
            self::Pais => 'País',
            self::Fe => 'Fe',
        };
    }

    /** Color de la cabecera en los impresos oficiales de la delegación. */
    public function printColor(): string
    {
        return match ($this) {
            self::Responsabilidad => '#4d7a1f',
            self::Pais => '#1f4e79',
            self::Fe => '#a01a1a',
        };
    }

    /** Clases Tailwind para la insignia del ámbito en la web. */
    public function badgeClasses(): string
    {
        return match ($this) {
            self::Responsabilidad => 'bg-lime-50 text-lime-800 border-lime-200',
            self::Pais => 'bg-blue-50 text-blue-800 border-blue-200',
            self::Fe => 'bg-red-50 text-red-800 border-red-200',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public static function options(): array
    {
        return array_map(
            fn (self $s) => ['value' => $s->value, 'label' => $s->label()],
            self::cases()
        );
    }
}
