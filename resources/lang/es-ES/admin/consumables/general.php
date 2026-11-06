<?php

return [
    // SSL: solo cambia el texto visible. Las claves y operaciones internas de Snipe-IT se conservan.
    'checkout' => 'Registrar entrega de EPP',
    'checkout_button' => 'Registrar entrega',
    'consumable_name' => 'Nombre del EPP',
    'create' => 'Crear EPP',
    'item_no' => 'Código del EPP',
    'remaining' => 'Disponibles para entrega',
    'total' => 'Existencias totales',
    'update' => 'Actualizar EPP',
    'inventory_warning' => 'Las existencias de este EPP están por debajo del mínimo establecido de :min_count.',
    'exclude_deleted' => 'Excluir EPP eliminados',
    'include_deleted' => 'Incluir EPP eliminados',
    'only_deleted' => 'Mostrar solo EPP eliminados',
    // IMPORTANTE: "consumido" cierra una entrega, NO equivale a una devolución física.
    'mark_consumed' => 'Marcar entrega como consumida',
    'bulk_consume' => 'Marcar como consumidas',
    'bulk_consume_warn' => 'Está a punto de marcar :count entrega de EPP como consumida.|Está a punto de marcar :count entregas de EPP como consumidas.',
    'bulk_consume_help' => 'Esta acción marca las entregas seleccionadas como consumidas y las excluye de las pendientes. No registra devoluciones ni repone existencias. No se puede deshacer.',
    'consumed_at' => 'Fecha de cierre por consumo',

    // Etiquetas propias de la presentación SSL; no crean campos nuevos en la base de datos.
    'inventory_description' => 'Inventario y dotación de equipos de protección personal (EPP).',
    'business_unit' => 'Empresa / unidad de negocio',
    'model_or_size' => 'Modelo / referencia / talla',
    'epp_notes' => 'Observaciones del EPP',
    'recipient' => 'Trabajador receptor',
    'delivery_quantity' => 'Cantidad a entregar',
    'delivery_notes' => 'Observaciones de entrega',
    'delivery_date' => 'Fecha de entrega',
    'outstanding_deliveries' => 'Entregas pendientes de cierre',
    'closed_consumed' => 'Entregas cerradas por consumo',
    'percent_available' => '% disponible',
];
