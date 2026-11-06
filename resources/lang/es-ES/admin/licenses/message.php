<?php

return [

    'does_not_exist' => 'El permiso no existe o no tiene autorización para verlo.',
    'user_does_not_exist' => 'El usuario no existe o no tiene permiso para verlos.',
    'asset_does_not_exist' => 'El activo que intenta asociar con esta licencia no existe.',
    'owner_doesnt_match_asset' => 'El activo que está intentando asignar con esta licencia está asignado a un usuario diferente al de la persona seleccionada de la lista.',
    'assoc_users' => 'Este permiso tiene asignaciones activas. Retire las asignaciones antes de intentar eliminarlo.',
    'select_asset_or_person' => 'Debe seleccionar un activo o un usuario, pero no ambos.',
    'not_found' => 'Permiso no encontrado',
    'seats_available' => ':seat_count cupos disponibles',

    'create' => [
        'error' => 'No se pudo crear el permiso. Inténtelo nuevamente.',
        'success' => 'Permiso creado correctamente.',
    ],

    'deletefile' => [
        'error' => 'Archivo no eliminado. Por favor, vuelva a intentarlo.',
        'success' => 'Archivo eliminado correctamente.',
    ],

    'upload' => [
        'error' => 'Archivo(s) no cargado(s). Por favor, inténtelo de nuevo.',
        'success' => 'Archivo(s) cargado correctamente.',
        'nofiles' => 'No seleccionó ningún archivo para ser cargado, o el archivo que seleccionó es demasiado grande',
        'invalidfiles' => 'Uno o más de sus archivos es demasiado grande o es un tipo de archivo que no está permitido. Los tipos de archivo permitidos son png, gif, jpg, jpeg, doc, docx, pdf, txt, zip, rar, rtf, xml y lic.',
    ],

    'update' => [
        'error' => 'No se pudo actualizar el permiso. Inténtelo nuevamente.',
        'success' => 'Permiso actualizado correctamente.',
    ],

    'delete' => [
        'confirm' => '¿Está seguro de que desea eliminar este permiso?',
        'error' => 'No se pudo eliminar el permiso. Inténtelo nuevamente.',
        'success' => 'El permiso se eliminó correctamente.',
        'bulk_success' => 'Los permisos seleccionados se eliminaron correctamente.',
        'partial_success' => 'Permiso eliminado correctamente. Revise el detalle. | :count permisos eliminados correctamente. Revise el detalle.',
        'bulk_checkout_warning' => ':license_name tiene cupos asignados y no se puede eliminar. Retire todas las asignaciones antes de eliminarlo.',
    ],

    'delete_with_checkin' => [
        'bulk_success' => ':count permisos eliminados correctamente después de retirar :seats asignaciones.',
        'partial_success' => ':count permisos eliminados después de retirar :seats asignaciones. Revise el detalle.',
    ],

    'checkout' => [
        'error' => 'No se pudo asignar el permiso. Inténtelo nuevamente.',
        'success' => 'El permiso se asignó correctamente.',
        'not_enough_seats' => 'No hay suficientes cupos disponibles para asignar.',
        'mismatch' => 'El permiso proporcionado no coincide con el permiso seleccionado.',
        'unavailable' => 'Este permiso no tiene cupos disponibles para asignar.',
        'license_is_inactive' => 'Permiso vencido o finalizado.',
    ],

    'checkin' => [
        'error' => 'No se pudo retirar la asignación del permiso. Inténtelo nuevamente.',
        'not_reassignable' => 'Este cupo ya fue utilizado y no es reasignable.',
        'success' => 'La asignación del permiso se retiró correctamente.',
    ],

    'import' => [
        'no_free_seats' => 'El permiso ":license" no tiene cupos disponibles. No se asignó un cupo a ":target".',
    ],

];
