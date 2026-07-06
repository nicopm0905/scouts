<?php

namespace App\Enums;

enum DocumentCategory: string
{
    case Censo = 'censo';
    case Seguros = 'seguros';
    case ActasConsejo = 'actas_consejo';
    case ActasAsamblea = 'actas_asamblea';
    case Estatutos = 'estatutos';
    case ProyectoEducativo = 'proyecto_educativo';
    case MemoriaAnual = 'memoria_anual';
    case Subvenciones = 'subvenciones';
    case Rgpd = 'rgpd';
    case Plantillas = 'plantillas';
    case Otro = 'otro';

    public function label(): string
    {
        return match ($this) {
            self::Censo => 'Censo',
            self::Seguros => 'Seguros',
            self::ActasConsejo => 'Actas de consejo',
            self::ActasAsamblea => 'Actas de asamblea',
            self::Estatutos => 'Estatutos',
            self::ProyectoEducativo => 'Proyecto educativo',
            self::MemoriaAnual => 'Memoria anual',
            self::Subvenciones => 'Subvenciones',
            self::Rgpd => 'RGPD',
            self::Plantillas => 'Plantillas',
            self::Otro => 'Otro',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
