@component('mail::message')
# RESUMEN INSTITUCIONAL DE ENTREGAS Y DEVOLUCIONES

Estimado(a) **{{ $worker->display_name }}**:

Le informamos que se han registrado los siguientes movimientos a su nombre en Snipe-SSL. El resumen agrupa únicamente avisos informativos; las aceptaciones y condiciones de uso requeridas se comunican por separado.

**Datos del trabajador al registrar el primer movimiento del resumen**

@component('mail::table')
| Campo | Información |
| :--- | :--- |
| Nombre y apellido | {{ $movements->first()->recipient_name }} |
| C.I. / número de empleado | {{ $movements->first()->employee_num ?: 'No registrado' }} |
| Cargo | {{ $movements->first()->jobtitle ?: 'No registrado' }} |
| Sede de adscripción | {{ $movements->first()->work_location ?: 'No registrada' }} |
@endcomponent

**Relación de movimientos registrados**

@component('mail::table')
| Movimiento | Tipo | Descripción | Placa / talla / referencia | Cant. | Empresa | Fecha de movimiento |
| :--- | :--- | :--- | :--- | ---: | :--- | :--- |
@foreach ($movements as $row)
| {{ $row->movement === 'checkin' ? 'Devolución' : 'Entrega' }} | {{ ['asset' => 'Activo', 'accessory' => 'Uniforme / accesorio', 'consumable' => 'EPP'][$row->item_type] ?? $row->item_type }} | {{ str_replace('|', '/', $row->item_name) }} | {{ str_replace('|', '/', $row->item_reference ?: '—') }} | {{ $row->quantity }} | {{ $row->company_name ?: 'No registrada' }} | {{ \Illuminate\Support\Carbon::parse($row->occurred_at)->format('d/m/Y H:i') }} |
@endforeach
@endcomponent

**Información adicional**

@component('mail::table')
| Campo | Detalle |
| :--- | :--- |
| Total de movimientos | {{ $movements->count() }} |
| Entregas | {{ $movements->where('movement', 'checkout')->count() }} |
| Devoluciones | {{ $movements->where('movement', 'checkin')->count() }} |
| Referencia del resumen | {{ $batchReference }} |
@endcomponent

@if ($movements->contains(fn ($row) => filled($row->note)))
**Observaciones registradas**
@foreach ($movements as $row)
@if (filled($row->note))
- {{ $row->movement === 'checkin' ? 'Devolución' : 'Entrega' }} #{{ $row->id }} — {{ $row->item_name }}: {{ $row->note }}
@endif
@endforeach
@endif

Este mensaje es un **resumen de movimientos**, no una constancia de entrega firmada. Las firmas, huellas, aceptaciones, archivos adjuntos y fechas históricas originales permanecen en sus registros correspondientes de Snipe-SSL. El EPP consumido no equivale a una devolución física.

Atentamente,  
**{{ $sslSettings?->site_name ?? 'Gestión SSL' }}**
@endcomponent
