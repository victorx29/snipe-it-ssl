<?php

return [
    'about_licenses_title' => 'Acerca de Permisología',
    'about_licenses' => 'Registre permisos o autorizaciones, sus fechas de vigencia y los cupos que pueden asignarse a personas o activos. La opción Reasignable determina si un cupo puede volver a utilizarse después de retirar una asignación.',
    'checkin' => 'Retirar asignación de permiso',
    'checkout_history' => 'Historial de asignaciones',
    'checkout' => 'Asignar permiso',
    'edit' => 'Editar permiso',
    'filetype_info' => 'Los tipos de archivo permitidos son png, gif, jpg, jpeg, doc, docx, pdf, txt, zip, y rar.',
    'clone' => 'Clonar permiso',
    'history_for' => 'Historial para ',
    'in_out' => 'Retirar / asignar',
    'info' => 'Información del permiso',
    'license_seats' => 'Cupos autorizados',
    'seat' => 'Cupo',
    'seat_count' => 'Cupo :count',
    'seats' => 'Cupos autorizados',
    'software_licenses' => 'Permisología',
    'user' => 'Usuario',
    'view' => 'Ver permiso',
    'permit_name' => 'Permiso o autorización',
    'inventory_description' => 'Registro de permisos y autorizaciones. Los cupos disponibles y las asignaciones mantienen el funcionamiento original de Snipe-IT.',
    'form_description' => 'Registre la vigencia y los cupos autorizados. La opción Reasignable controla si un cupo puede reutilizarse al retirar una asignación.',
    'bulk_checkin_title' => 'Retirar asignaciones de permisos',
    'delete_disabled' => 'Este permiso no se puede eliminar mientras tenga asignaciones activas.',
    'bulk' => [
        'checkin_all' => [
            'button' => 'Retirar todas las asignaciones',
            'modal' => 'Esta acción retirará una asignación de permiso. | Esta acción retirará las :checkedout_seats_count asignaciones de permiso.',
            'enabled_tooltip' => 'Retirar TODAS las asignaciones de permisos a usuarios y activos',
            'disabled_tooltip' => 'No hay cupos de este permiso asignados actualmente',
            'disabled_tooltip_reassignable' => 'Esta acción está deshabilitada porque el permiso no es reasignable',
            'success' => 'Asignación retirada correctamente. | Todas las asignaciones se retiraron correctamente.',
            'log_msg' => 'Asignación retirada mediante operación masiva en Permisología',
        ],

        'checkin_selected' => [
            'success' => ':count cupo desasignado correctamente. | :count cupos desasignados correctamente.',
            'no_seats_selected' => 'No se seleccionaron cupos.',
        ],

        'checkout_all' => [
            'button' => 'Asignar todos los cupos disponibles',
            'modal' => 'Se asignará un cupo al primer usuario elegible. | Se asignarán los :available_seats_count cupos disponibles a los primeros usuarios elegibles. Se considera elegible a un usuario que no tenga asignado este permiso y tenga habilitada la opción de autoasignación de licencias en su perfil.',
            'enabled_tooltip' => 'Asignar automáticamente los cupos disponibles a usuarios elegibles',
            'disabled_tooltip' => 'No hay cupos disponibles de este permiso',
            'success' => 'Permiso asignado correctamente. | :count cupos asignados correctamente.',
            'error_no_seats' => 'No quedan cupos disponibles.',
            'warn_not_enough_seats' => 'Se asignaron :count usuarios a este permiso y se agotaron los cupos disponibles.',
            'warn_no_avail_users' => 'No hay usuarios elegibles sin una asignación de este permiso.',
            'log_msg' => 'Permiso asignado mediante operación masiva en Permisología',

        ],

        'delete_with_checkin' => [
            'label' => 'Retirar asignaciones y eliminar permiso',
            'log_msg' => 'Asignaciones retiradas al eliminar el permiso mediante operación masiva',
        ],
    ],

    'below_threshold' => 'Solo quedan :remaining_count cupos disponibles y el mínimo configurado es :min_amt. Revise la disponibilidad de autorizaciones.',
    'below_threshold_short' => 'Este permiso tiene menos cupos disponibles que el mínimo configurado.',
];
