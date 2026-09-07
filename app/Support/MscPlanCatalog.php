<?php

namespace App\Support;

use App\Enums\ActivityType;
use App\Enums\MscScope;

/**
 * Catálogo oficial del plan de rama MSC: para cada ámbito, sus líneas y, para
 * cada línea, los contenidos entre los que elige el kraal al programar el
 * trimestre o una salida.
 *
 * Es el mismo desplegable que usa la hoja de programación de la delegación
 * (Ámbitos → Líneas → Contenido). Se mantiene aquí, no en base de datos,
 * porque lo fija la federación y cambia de curso en curso, no por grupo.
 */
class MscPlanCatalog
{
    /** @var array<string, array<string, list<string>>> */
    private const LINES = [
        'responsabilidad' => [
            'Salud' => [
                'Deporte',
                'Sexualidad',
                'Higiene, alimentación y hábitos saludables',
            ],
            'Destreza' => [
                'Formación de habilidades técnicas',
                'Expresión artística: plástica, escrita, musical',
                'Imaginación, ingenio y creatividad',
            ],
            'Carácter' => [
                'Desarrollo emocional, afectividad',
                'Autoestima, autoimagen',
                'Responsabilidad, compromiso, el compromiso personal',
            ],
            'Habilidades sociales' => [
                'Convivencia, cooperación, confianza, trabajo en grupo',
                'Resolución de conflictos: asertividad, empatía, debate',
            ],
            'Interiorización' => [
                'Vivencias de fe',
            ],
            'Autonomía' => [
                'Superación, autonomía, autogestión',
            ],
        ],
        'pais' => [
            'Compromiso' => [
                'Conocimiento de otros colectivos ajenos al escultismo',
                'Acción local',
            ],
            'Diversidad' => [
                'Inclusión, diversidad funcional',
            ],
            'Ciudadanía' => [
                'Educación ambiental',
                'Cooperación para el desarrollo, desigualdad social',
            ],
            'Comunidad' => [
                'Relación con la rama y el grupo scout',
                'Relación con la familia',
                'Nuestra rama y nuestro grupo scout',
                'Hermandad scout',
            ],
            'Servicio' => [
                'Conocimiento de problemáticas sociales',
            ],
            'Comunión' => [
                'Relación con la parroquia y grupos pastorales',
            ],
        ],
        'fe' => [
            'Sabiduría' => [
                'Celebraciones y Eucaristía',
                'Evangelio, vida de Jesús y otros personajes bíblicos',
            ],
            'Moral cristiana' => [
                'Hermandad',
                'Alegría',
            ],
            'Fraternidad' => [
                'Comunidad cristiana, convivencia, ayuda',
            ],
            'Oración' => [
                'Fe en Dios, Palabra, Experiencia de Dios',
            ],
            'Vocación' => [
                'Búsqueda de la fe',
            ],
            'Corporeidad' => [
                'Vivencia en la naturaleza',
            ],
        ],
    ];

    /** Verbos con los que arranca la columna "¿Qué queremos conseguir?". */
    private const GOAL_VERBS = [
        'Conseguir', 'Aprender', 'Conocer', 'Crear', 'Mejorar', 'Desarrollar',
        'Descubrir', 'Fomentar', 'Valorar', 'Compartir', 'Vivir', 'Potenciar',
    ];

    /**
     * Franjas de la rejilla ESTRUCTURA de la ficha de salida, con las horas
     * por defecto del impreso de la delegación.
     *
     * @var array<string, array{label: string, from: string, to: string}>
     */
    private const TIME_SLOTS = [
        'manana' => ['label' => 'Mañana', 'from' => '10', 'to' => '14'],
        'tarde_1' => ['label' => 'Tarde I', 'from' => '16', 'to' => '18'],
        'tarde_2' => ['label' => 'Tarde II', 'from' => '19', 'to' => '21'],
        'noche' => ['label' => 'Noche', 'from' => '22', 'to' => '23'],
    ];

    /** @return array<string, array<string, list<string>>> */
    public static function lines(): array
    {
        return self::LINES;
    }

    /** Líneas de un ámbito. @return list<string> */
    public static function linesFor(MscScope|string $scope): array
    {
        $key = $scope instanceof MscScope ? $scope->value : $scope;

        return array_keys(self::LINES[$key] ?? []);
    }

    /** Contenidos de una línea dentro de un ámbito. @return list<string> */
    public static function contentsFor(MscScope|string $scope, string $line): array
    {
        $key = $scope instanceof MscScope ? $scope->value : $scope;

        return self::LINES[$key][$line] ?? [];
    }

    /** @return list<string> */
    public static function goalVerbs(): array
    {
        return self::GOAL_VERBS;
    }

    /** @return array<string, array{label: string, from: string, to: string}> */
    public static function timeSlots(): array
    {
        return self::TIME_SLOTS;
    }

    public static function timeSlotLabel(?string $key): ?string
    {
        return self::TIME_SLOTS[$key]['label'] ?? $key;
    }

    /**
     * Todo lo que necesita el formulario del navegador para los desplegables
     * encadenados Ámbito → Línea → Contenido.
     */
    public static function forFrontend(): array
    {
        return [
            'scopes' => MscScope::options(),
            'lines' => self::LINES,
            'goal_verbs' => self::GOAL_VERBS,
            'time_slots' => array_map(
                fn (string $key, array $slot) => [
                    'value' => $key,
                    'label' => $slot['label'],
                    'from' => $slot['from'],
                    'to' => $slot['to'],
                ],
                array_keys(self::TIME_SLOTS),
                self::TIME_SLOTS
            ),
            'activity_types' => ActivityType::options(),
        ];
    }
}
