# Snipe-SSL - Etapa 6.1 - Consolidacion segura de notificaciones

## Que incorpora

- Recopila movimientos informativos de entrega/devolucion de **Activos**, **Accesorios/Uniformes** y entrega de **EPP** por trabajador.
- Guarda datos del movimiento y del trabajador en una bandeja historica: `ssl_notification_movements`.
- Agrupa multiples movimientos por trabajador en **un correo HTML con tabla**.
- Conserva la regla original de categoria: solo entra al digest un aviso individual que Snipe-IT ya enviaria.
- **No consolida** entregas con `requireAcceptance()`, EULA, firma en sitio, licencias, componentes ni Capacitaciones. Sus correos/aceptaciones permanecen nativos.
- Los correos administrativos y los webhooks siguen su comportamiento nativo.
- Si falla guardar en la bandeja, Snipe-IT intenta el correo individual como antes.
- `MAIL_MAILER=log`: se puede previsualizar sin SMTP y sin marcar movimientos como enviados.
- No modifica movimientos de inventario, asistencias ni constancias.

## Archivos

- `app/Listeners/CheckoutableListener.php` (parte de la version exacta de `snipemail.zip`, con su captura de `\\Throwable` preservada).
- `app/Services/SslMovementDigestRecorder.php` (nuevo).
- `app/Console/Commands/SendSslMovementDigests.php` (nuevo).
- `app/Mail/SslMovementDigestMail.php` (nuevo).
- `resources/views/mail/markdown/ssl-movement-digest.blade.php` (nuevo).
- `config/ssl_notifications.php` (nuevo).
- `database/migrations/2026_11_12_000000_create_ssl_notification_movements_table.php` (nuevo).

## Despliegue inicial seguro (sin enviar nada nuevo)

1. Respaldar **base de datos y archivos** de EasyPanel.
2. Descomprimir en la raiz del repositorio y revisar el diff del listener (no sustituir cambios posteriores no incluidos en el ZIP).
3. Enviar a GitHub `master` y desplegar normalmente.
4. Ejecutar:

   ```bash
   php artisan migrate --force
   php artisan optimize:clear
   php artisan list | grep ssl:send-digests
   ```

5. **Dejar** `SSL_DIGEST_ENABLED=false` inicialmente. Asi no se modifican los envios actuales.

## Prueba con MAIL_MAILER=log

1. En variables de EasyPanel definir:

   ```env
   MAIL_MAILER=log
   SSL_DIGEST_ENABLED=true
   SSL_DIGEST_WINDOW_MINUTES=10
   ```

   Mantener el resto de variables de correo actuales; reiniciar el servicio o limpiar configuracion con `php artisan optimize:clear`.
2. Asignar dos articulos en una categoria que **ya habilita el correo de entrega**, al mismo usuario, sin requerir aceptacion y sin EULA. Si la categoria no pide correo, no entrara en el digest.
3. Inspeccionar pendientes en SQL:

   ```sql
   SELECT id, recipient_user_id, movement, item_type, item_name, quantity,
          occurred_at, sent_at, attempts
   FROM ssl_notification_movements ORDER BY id DESC LIMIT 20;
   ```

4. Para omitir el tiempo de espera solo en las pruebas:

   ```bash
   php artisan ssl:send-digests --minutes=0
   ```

   Esto **fuerza el transportador `log`**, incluso si el transportador configurado fuese SMTP: nunca envia a un destinatario real y no actualiza `sent_at`. Se puede repetir, pero generara nuevas copias de prueba en los logs. Los logs incluyen nombres, cargos y detalles de los movimientos: proteger su acceso.
5. Confirmar que aparece un correo tabulado por usuario en los logs, que los movimientos siguen pendientes y que no se duplican correos individuales para los movimientos agrupables.
6. Probar un activo con EULA o aceptacion: debe continuar llegando el correo individual con enlace. Probar devoluciones y comprobar que el historial sigue intacto.

## Envio real (solo cuando exista SMTP)

1. Configurar y probar SMTP u otro transporte **real**. Nunca usar `--send` con `MAIL_MAILER=log`.
2. Mantener `SSL_DIGEST_ENABLED=true` y ejecutar, inicialmente de forma controlada:

   ```bash
   php artisan ssl:send-digests --send --limit=10
   ```

3. Si se valida, crear **una unica tarea programada** en EasyPanel cada 5 minutos:

   ```bash
   php artisan ssl:send-digests --send
   ```

   El comando no necesita un trabajador de cola. Su envio no es automatico si no se programa el comando.

## Condiciones y limites importantes

- Los correos agrupan movimientos por trabajador y solo incluyen hechos registrados despues de activar el digest; no generan correos retroactivos de los historiales antiguos.
- Mientras el transporte sea `log`, la bandeja queda **pendiente**, y ese contenido podra enviarse cuando se habilite SMTP. Evaluar la antiguedad de los mensajes antes de hacerlo.
- Las cantidades y descripciones se fotografian al registrar el aviso; `action_logs` sigue siendo el historial operativo original. La fecha `occurred_at` conserva la fecha original expuesta por Snipe-IT cuando esta disponible; de lo contrario usa la fecha de captura. `created_at` indica cuando se guardo el aviso. El historial `action_logs` sigue siendo la referencia operativa.
- En un fallo excepcional del proceso **despues** de que SMTP acepte un correo y **antes** de marcarlo `sent_at`, puede haber reenvio. Se conserva un identificador de lote (`sent_batch_token`) para ayudar a rastrearlo; no se promete entrega exactamente una vez.
- Los movimientos cuyos usuarios se borren o queden sin email se conservan pero no se envian a direcciones historicas.
- **No ejecutar `migrate:rollback`** de la migracion de esta etapa: la migracion bloquea expresamente su reversa para preservar evidencia. Para detener el sistema usar `SSL_DIGEST_ENABLED=false` y dejar la tabla intacta.
- La funcion esta desactivada por defecto; con `false` se conserva la conducta original del listener.
- No incluye las convocatorias a Capacitaciones: su logica individual de la etapa 5.6 se conserva.

## Validacion antes de cerrar la etapa

- Repositorio limpio tras push; despliegue sin 500.
- Migracion y comando aparecen correctamente.
- Digest de dos entregas al mismo trabajador y una devolucion.
- Correos nativos de aceptacion/EULA intactos.
- Alertas administrativas/webhooks intactos.
- Sin alteraciones en inventario, horas-hombre, ni respaldos adjuntos.
- Reversion funcional con `SSL_DIGEST_ENABLED=false` sin eliminar historia.

## Alcance futuro

La auditoria posterior tendra prioridad de datos maestros, carga e integridad de historiales; **Power BI queda expresamente fuera de alcance** hasta que el usuario lo solicite.
