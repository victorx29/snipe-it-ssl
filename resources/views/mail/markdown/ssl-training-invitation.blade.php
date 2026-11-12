@component('mail::message')
# CONFIRMACIÓN DE INSCRIPCIÓN A CAPACITACIÓN

Estimado(a) **{{ $worker->display_name }}**:

Por medio de la presente le informamos que ha sido inscrito(a) en la siguiente actividad de formación organizada por **{{ $training->company?->name ?? ($sslSettings?->site_name ?? 'la empresa') }}**.

@include('mail.markdown.partials.ssl-worker-summary', ['worker' => $worker])

**Información de la capacitación**

@component('mail::table')
| Campo | Información |
| :--- | :--- |
| Capacitación | {{ $training->name }} |
| Código / referencia | {{ $training->model_number ?: 'No registrado' }} |
| Área responsable | {{ $ssl_area_name }} |
| Fecha y hora de inicio | {{ $training->ssl_training_starts_at?->format('d/m/Y H:i') ?? 'Por definir' }} |
| Fecha y hora de finalización | {{ $training->ssl_training_ends_at?->format('d/m/Y H:i') ?? 'Por definir' }} |
| Duración efectiva | {{ (int) $training->ssl_training_duration_minutes }} minutos |
| Modalidad | {{ ucfirst($training->ssl_training_modality ?: 'Por definir') }} |
| Sede / ubicación | {{ $training->location?->name ?? 'Por confirmar' }} |
| Facilitador / instructor | {{ $training->ssl_training_facilitator ?: 'Por confirmar' }} |
| Estado de la sesión | {{ $ssl_status_name }} |
| Fecha de esta notificación | {{ $ssl_notice_at }} |
@endcomponent

@if ($training->notes)
**Información adicional de la sesión:** {{ $training->notes }}
@endif

@if ($registrar)
**Registro realizado por:** {{ $registrar->display_name }}
@endif

@if ($training->ssl_training_status === 'planned')
Agradecemos su puntualidad y participación. Si no puede asistir, comuníquelo oportunamente al área responsable. Para sesiones virtuales, confirme con el organizador el enlace o instrucciones de acceso.
@else
Esta comunicación confirma que se registró su inscripción en la sesión indicada; no implica que su asistencia haya sido confirmada.
@endif

**Importante:** La inscripción no acredita asistencia ni horas de formación. Los minutos y las horas-hombre se calculan únicamente después de confirmar la asistencia en una sesión realizada.

Atentamente,  
**{{ $sslSettings?->site_name ?? 'Gestión de Capacitaciones' }}**

*Correo informativo generado desde Snipe-SSL. No es una constancia de asistencia firmada.*
@endcomponent
