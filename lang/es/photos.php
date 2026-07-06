<?php

// Textos de UI del módulo de fotos e historia del grupo.
return [
    'albums' => [
        'title' => 'Álbumes de fotos',
        'subtitle' => 'Fotos de eventos y salidas alojadas en Google Drive.',
        'new' => 'Nuevo álbum',
        'form' => [
            'title' => 'Título',
            'description' => 'Descripción',
            'event' => 'Evento (opcional)',
            'no_event' => 'Álbum libre (sin evento)',
            'visibility' => 'Visibilidad',
        ],
        'visibility' => [
            'internal' => 'Solo interno',
            'publishable' => 'Publicable (con consentimiento de imagen)',
        ],
        'photos_count' => 'foto(s)',
        'empty' => 'Todavía no hay álbumes.',
        'upload_photo' => 'Subir foto',
        'caption' => 'Pie de foto (opcional)',
        'delete_confirm' => '¿Eliminar este álbum y todas sus fotos?',
        'delete_photo_confirm' => '¿Eliminar esta foto?',
    ],
    'public_gallery' => [
        'title' => 'Galería del grupo',
        'subtitle' => 'Fotos con consentimiento de imagen para difusión pública.',
        'empty' => 'Todavía no hay álbumes publicados.',
    ],
    'history' => [
        'title' => 'Historia del grupo',
        'subtitle' => 'Línea de tiempo editable para la página pública.',
        'new' => 'Nueva entrada',
        'form' => [
            'year' => 'Año',
            'entry_title' => 'Título',
            'body' => 'Texto',
            'photo' => 'Foto (opcional)',
            'published' => 'Publicada',
            'position' => 'Orden',
        ],
        'empty' => 'Todavía no hay entradas de historia.',
        'delete_confirm' => '¿Eliminar esta entrada de la historia?',
        'public_title' => 'Nuestra historia',
        'public_subtitle' => 'Un recorrido por los momentos más destacados del grupo.',
        'public_empty' => 'Próximamente la historia del grupo.',
    ],
];
