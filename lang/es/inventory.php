<?php

return [
    'title' => 'Inventario',
    'subtitle' => 'Material del grupo: tiendas, cocina, botiquín y más.',
    'new_item' => 'Nuevo ítem',
    'edit_item' => 'Editar ítem',
    'search_placeholder' => 'Buscar por nombre o ubicación…',

    'fields' => [
        'name' => 'Nombre',
        'category' => 'Categoría',
        'quantity' => 'Cantidad total',
        'available_quantity' => 'Disponible',
        'condition' => 'Estado',
        'location' => 'Ubicación',
        'next_review_at' => 'Próxima revisión',
        'notes' => 'Notas',
        'photo' => 'Foto',
    ],

    'filters' => [
        'all_categories' => 'Todas las categorías',
        'all_conditions' => 'Todos los estados',
    ],

    'alerts' => [
        'needing_review' => 'Material pendiente de revisión',
        'overdue' => 'Material sin devolver (fuera de plazo)',
        'none' => 'Sin alertas por ahora.',
    ],

    'checkout' => [
        'reserve' => 'Reservar',
        'reserve_title' => 'Reservar / prestar material',
        'for_event' => 'Para un evento',
        'for_member' => 'Para un responsable',
        'checked_out_at' => 'Fecha de salida',
        'expected_return_at' => 'Retorno previsto',
        'returned_at' => 'Fecha de devolución',
        'mark_returned' => 'Marcar devuelto',
        'outstanding' => 'Pendientes de devolver',
        'available_now' => 'Disponible ahora',
        'not_enough' => 'No hay unidades suficientes disponibles en esas fechas.',
    ],

    'actions' => [
        'edit' => 'Editar',
        'delete' => 'Eliminar',
        'save' => 'Guardar',
        'cancel' => 'Cancelar',
    ],

    'confirm' => [
        'delete_title' => '¿Eliminar este ítem?',
        'delete_message' => 'Se eliminará del inventario junto con su historial de reservas.',
    ],
];
