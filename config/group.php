<?php

/*
|--------------------------------------------------------------------------
| Datos públicos del grupo scout
|--------------------------------------------------------------------------
|
| Todo el contenido editable de la web pública (landing) vive aquí, para que
| coordinación/secretaría pueda actualizarlo sin tocar componentes Vue.
| Los textos van en español (UI pública); las claves, en inglés.
|
| Las fotos se colocan en public/images/landing/<fichero>. Si un fichero no
| existe, la web muestra automáticamente un fondo ilustrado de respaldo.
|
*/

return [

    'name' => 'Grupo Scout San José',
    'short_name' => 'Scouts de San José',
    'claim' => 'Aventura, amistad y compromiso',
    'tagline' => 'Educamos en el tiempo libre a niñas, niños y jóvenes a través del juego, la naturaleza y el servicio a los demás.',
    'federation' => 'Movimiento Scout Católico · Scouts de Andalucía',
    'diocese' => 'Asidonia-Jerez',
    'founded_year' => 1975,

    'contact' => [
        'email' => 'sanjose@mscjerez.es',
        'phone' => '',
        'whatsapp' => '',           // Ej: '34600000000' (solo dígitos)
        'address' => 'C. Porvera, 21',
        'locality' => 'Jerez de la Frontera',
        'province' => 'Cádiz',
        'region' => 'Andalucía',
        'postal_code' => '11403',
        'map_url' => 'https://maps.google.com/?q=C.+Porvera,+21,+11403+Jerez+de+la+Frontera,+C%C3%A1diz',
        'schedule' => 'Sábados de 10:30 a 13:00 en el local del grupo',
    ],

    'social' => [
        'instagram' => '',
        'facebook' => '',
        'youtube' => '',
    ],

    // Cifras del grupo mostradas en portada (edítalas cada curso).
    'stats' => [
        ['value' => '50+', 'label' => 'Años de escultismo'],
        ['value' => '120', 'label' => 'Chavales cada curso'],
        ['value' => '5', 'label' => 'Secciones educativas'],
        ['value' => '20+', 'label' => 'Responsables voluntarios'],
    ],

    // Secciones educativas. 'key' coincide con App\Enums\MemberRole.
    'sections' => [
        [
            'key' => 'castor',
            'name' => 'Castores',
            'unit' => 'Colonia',
            'ages' => '6 - 8 años',
            'motto' => 'Compartir',
            'color' => '#f97316',
            'description' => 'Los más pequeños descubren el grupo jugando. Aprenden a compartir, a cuidar el entorno y a hacer las cosas juntos.',
            'photo' => 'castores.jpg',
        ],
        [
            'key' => 'lobato',
            'name' => 'Lobatos',
            'unit' => 'Manada',
            'ages' => '8 - 11 años',
            'motto' => 'Haremos lo mejor',
            'color' => '#eab308',
            'description' => 'La manada vive la aventura del Libro de la Selva: juegos, talleres, pistas y las primeras acampadas fuera de casa.',
            'photo' => 'lobatos.jpg',
        ],
        [
            'key' => 'ranger',
            'name' => 'Rangers',
            'unit' => 'Tropa',
            'ages' => '11 - 14 años',
            'motto' => 'Siempre listos',
            'color' => '#1e3a8a',
            'description' => 'En equipos pequeños aprenden técnicas scouts, se organizan por sí mismos y descubren la naturaleza en rutas y campamentos.',
            'photo' => 'rangers.jpg',
        ],
        [
            'key' => 'pionero',
            'name' => 'Pioneros',
            'unit' => 'Posta',
            'ages' => '14 - 17 años',
            'motto' => 'Unidad',
            'color' => '#e11d48',
            'description' => 'Diseñan y sacan adelante sus propios proyectos: empresas de sección, campos de trabajo y voluntariado en el barrio.',
            'photo' => 'pioneros.jpg',
        ],
        [
            'key' => 'ruta',
            'name' => 'Rutas',
            'unit' => 'Clan',
            'ages' => '17 - 21 años',
            'motto' => 'Servicio',
            'color' => '#16a34a',
            'description' => 'La última etapa: compromiso personal, servicio a la comunidad y grandes rutas que se preparan durante todo el curso.',
            'photo' => 'rutas.jpg',
        ],
    ],

    // Qué hacemos: pilares del método scout.
    'pillars' => [
        [
            'icon' => 'compass',
            'title' => 'Educación en valores',
            'text' => 'Un proyecto educativo con más de un siglo de recorrido: aprender haciendo, en pequeños equipos y con progresión personal.',
        ],
        [
            'icon' => 'tent',
            'title' => 'Naturaleza y aventura',
            'text' => 'Salidas de fin de semana, rutas de montaña y un campamento de verano donde vivir la vida al aire libre.',
        ],
        [
            'icon' => 'heart',
            'title' => 'Servicio a los demás',
            'text' => 'Proyectos solidarios con la parroquia y el barrio: dejar el mundo un poco mejor de como lo encontramos.',
        ],
        [
            'icon' => 'users',
            'title' => 'Comunidad y familia',
            'text' => 'Las familias forman parte del grupo. Detrás de cada actividad hay un equipo de responsables voluntarios y titulados.',
        ],
    ],

    // Pasos para apuntarse.
    'join_steps' => [
        ['title' => 'Escríbenos', 'text' => 'Cuéntanos la edad de tu hijo o hija y resolvemos todas tus dudas por email o teléfono.'],
        ['title' => 'Ven a conocernos', 'text' => 'Os invitamos a una reunión de sección para que veáis cómo trabajamos, sin ningún compromiso.'],
        ['title' => 'Formalizad la inscripción', 'text' => 'Rellenáis la ficha del curso y la autorización. Desde ese día ya sois parte del grupo.'],
    ],

    'faq' => [
        [
            'q' => '¿Hay que ser creyente para apuntarse?',
            'a' => 'Somos un grupo del Movimiento Scout Católico y la dimensión de fe forma parte de nuestro proyecto educativo, pero acogemos a cualquier familia que respete ese carácter propio.',
        ],
        [
            'q' => '¿Cuánto cuesta?',
            'a' => 'La cuota del curso cubre el seguro, el material y la formación de los responsables. Las salidas y el campamento se abonan aparte. Escríbenos y te damos las cifras del curso actual.',
        ],
        [
            'q' => '¿Se puede entrar a mitad de curso?',
            'a' => 'Sí, siempre que quede plaza en la sección que corresponde por edad. Escríbenos y lo miramos contigo.',
        ],
        [
            'q' => '¿Quién se encarga de los chavales?',
            'a' => 'Un equipo de responsables voluntarios, mayores de edad, con formación scout y certificado de delitos de naturaleza sexual en vigor, respetando siempre las ratios legales por sección.',
        ],
    ],

    // Fotos de portada (public/images/landing/…). Si faltan, hay respaldo visual.
    'photos' => [
        'hero' => 'hero.jpg',
        'about' => 'grupo.jpg',
        'camp' => 'campamento.jpg',
    ],
];
