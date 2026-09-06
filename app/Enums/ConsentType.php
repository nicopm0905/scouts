<?php

namespace App\Enums;

enum ConsentType: string
{
    case Rgpd = 'rgpd';
    case Imagen = 'imagen';
    case SalidasPeriodicas = 'salidas_periodicas';

    public function label(): string
    {
        return match ($this) {
            self::Rgpd => 'Protección de datos (RGPD)',
            self::Imagen => 'Derechos de imagen',
            self::SalidasPeriodicas => 'Salidas periódicas',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /** Texto legal fijo mostrado a la familia para firmar este consentimiento. */
    public function legalText(): string
    {
        return match ($this) {
            self::Rgpd => 'Autorizo al grupo scout al tratamiento de los datos personales de mi hijo/a '
                .'con la finalidad exclusiva de gestionar su participación en las actividades del grupo, '
                .'conforme al Reglamento (UE) 2016/679 (RGPD) y la Ley Orgánica 3/2018 de Protección de Datos.',
            self::Imagen => 'Autorizo al grupo scout a captar y publicar imágenes y vídeos de mi hijo/a '
                .'durante las actividades del grupo (reuniones, salidas y campamentos) en los canales de '
                .'comunicación internos y redes sociales del grupo, sin fines comerciales.',
            self::SalidasPeriodicas => 'Autorizo a mi hijo/a a participar en las salidas periódicas de corta '
                .'duración organizadas por el grupo scout dentro de la programación habitual de su rama, '
                .'sin necesidad de autorización individual para cada una de ellas.',
        };
    }
}
