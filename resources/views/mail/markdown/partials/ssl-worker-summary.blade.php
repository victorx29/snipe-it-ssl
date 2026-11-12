@if ($worker instanceof \App\Models\User)
**Datos del trabajador**

@component('mail::table')
| Campo | Información |
| :--- | :--- |
| Nombre y apellido | {{ $worker->display_name }} |
| C.I. / número de empleado | {{ $worker->employee_num ?: 'No registrado' }} |
| Cargo | {{ $worker->jobtitle ?: 'No registrado' }} |
| Departamento | {{ $worker->department?->name ?? 'No registrado' }} |
| Empresa | {{ $worker->companies->pluck('name')->join(', ') ?: 'No registrada' }} |
| Sede de adscripción | {{ $worker->location?->name ?? 'No registrada' }} |
@endcomponent
@endif
