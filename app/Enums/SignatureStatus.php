<?php

namespace App\Enums;

enum SignatureStatus: string
{
    case Pending = 'pending';
    case Sent = 'sent';
    case Signed = 'signed';
    case Expired = 'expired';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Sin enviar',
            self::Sent => 'Enviado',
            self::Signed => 'Firmado',
            self::Expired => 'Caducado',
            self::Cancelled => 'Cancelado',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
