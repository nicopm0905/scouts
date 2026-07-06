<?php

namespace App\Enums;

enum EventType: string
{
    case Reunion = 'reunion';
    case Salida = 'salida';
    case Acampada = 'acampada';
    case Campamento = 'campamento';
    case ConsejoGrupo = 'consejo_grupo';
    case Asamblea = 'asamblea';
    case Otro = 'otro';

    public function label(): string
    {
        return match ($this) {
            self::Reunion => 'Reunión',
            self::Salida => 'Salida',
            self::Acampada => 'Acampada',
            self::Campamento => 'Campamento',
            self::ConsejoGrupo => 'Consejo de grupo',
            self::Asamblea => 'Asamblea',
            self::Otro => 'Otro',
        };
    }

    /** Eventos que requieren inscripciones, autorizaciones y validación legal. */
    public function requiresEnrollment(): bool
    {
        return in_array($this, [self::Salida, self::Acampada, self::Campamento], true);
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
