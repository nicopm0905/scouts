<?php

namespace App\Enums;

/** Estado de una solicitud de revisión de datos enviada por una familia desde el portal. */
enum ChangeRequestStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pendiente de revisar',
            self::Approved => 'Aplicada',
            self::Rejected => 'Rechazada',
        };
    }

    public function badgeColor(): string
    {
        return match ($this) {
            self::Pending => 'amber',
            self::Approved => 'green',
            self::Rejected => 'red',
        };
    }
}
