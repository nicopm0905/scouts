<?php

namespace App\Enums;

/**
 * Roles de usuario del sistema (se sincronizan con spatie/laravel-permission).
 * Distinto de MemberRole, que es la rama educativa de un miembro.
 */
enum UserRole: string
{
    case Admin = 'admin';
    case Secretaria = 'secretaria';
    case Tesoreria = 'tesoreria';
    case Intendencia = 'intendencia';
    case Responsable = 'responsable';
    case Familia = 'familia';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Coordinación de grupo',
            self::Secretaria => 'Secretaría',
            self::Tesoreria => 'Tesorería',
            self::Intendencia => 'Intendencia',
            self::Responsable => 'Responsable',
            self::Familia => 'Familia',
        };
    }

    /** Roles del equipo de gestión (Kraal). La familia queda fuera. */
    public static function staff(): array
    {
        return [self::Admin, self::Secretaria, self::Tesoreria, self::Intendencia, self::Responsable];
    }

    /** Igual que staff() pero como strings (para scopes de spatie/permission). */
    public static function staffValues(): array
    {
        return array_map(fn (self $r) => $r->value, self::staff());
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
