<?php

// Textos de la UI: miembros, familias, asistencia e importación.
return [
    'title' => 'Miembros',
    'new' => 'Nuevo miembro',
    'edit' => 'Editar miembro',
    'first_name' => 'Nombre',
    'last_name' => 'Apellidos',
    'full_name' => 'Nombre completo',
    'phone' => 'Teléfono',
    'email' => 'Correo electrónico',
    'role' => 'Rama',
    'birth_date' => 'Fecha de nacimiento',
    'age' => 'Edad',
    'active' => 'Activo',
    'inactive' => 'Baja',
    'joined_at' => 'Fecha de alta',
    'notes' => 'Notas',
    'no_members' => 'No se han encontrado miembros.',
    'filter_branch' => 'Rama',
    'filter_active' => 'Solo activos',
    'all_branches' => 'Todas las ramas',

    'sections' => [
        'general' => 'Datos generales',
        'health' => 'Ficha sanitaria',
        'consents' => 'Consentimientos',
        'leader_profile' => 'Perfil de responsable',
        'trainings' => 'Formaciones',
        'families' => 'Familiares',
    ],

    'health' => [
        'allergies' => 'Alergias',
        'intolerances' => 'Intolerancias',
        'medication' => 'Medicación',
        'observations' => 'Observaciones',
        'health_card_number' => 'Nº tarjeta sanitaria',
        'attachment' => 'Adjuntar documento',
        'view_attachment' => 'Ver adjunto',
    ],

    'consents' => [
        'granted' => 'Concedido',
        'not_granted' => 'No concedido',
        'signed_at' => 'Fecha de firma',
    ],

    'leader_profile' => [
        'qualification' => 'Titulación',
        'certificate_date' => 'Fecha certificado delitos sexuales',
        'certificate_expires' => 'Caducidad del certificado',
        'certificate_valid' => 'Certificado vigente',
        'certificate_expired' => 'Certificado caducado o ausente',
        'branches' => 'Ramas a cargo',
        'add_training' => 'Añadir formación',
        'training_name' => 'Nombre de la formación',
        'obtained_at' => 'Fecha de obtención',
        'expires_at' => 'Fecha de caducidad',
        'expired' => 'Caducada',
    ],

    'families' => [
        'title' => 'Familias',
        'new' => 'Nueva familia',
        'name' => 'Nombre de familia',
        'contact_phone' => 'Teléfono de contacto',
        'contact_email' => 'Correo de contacto',
        'members' => 'Miembros',
        'members_count' => 'Nº de miembros',
        'link_member' => 'Vincular familiar',
        'relationship' => 'Parentesco',
        'no_families' => 'Todavía no hay familias creadas.',
        'unlink' => 'Desvincular',
    ],

    'import' => [
        'title' => 'Importar miembros',
        'description' => 'Sube un fichero CSV con los miembros a dar de alta.',
        'download_template' => 'Descargar plantilla',
        'file' => 'Fichero CSV',
        'submit' => 'Importar',
    ],

    'attendance' => [
        'title' => 'Control de asistencia',
        'branch' => 'Rama',
        'date' => 'Fecha',
        'present' => 'Presente',
        'absent' => 'Ausente',
        'save' => 'Guardar asistencia',
        'no_members' => 'No hay miembros en esta rama.',
    ],
];
