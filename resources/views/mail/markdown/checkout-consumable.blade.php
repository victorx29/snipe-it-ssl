@component('mail::message')
# NOTIFICACIÓN DE ENTREGA DE EQUIPOS DE PROTECCIÓN PERSONAL

Estimado(a) **{{ $target?->display_name ?? 'trabajador' }}**:

Se informa que se ha registrado la siguiente dotación de equipos de protección personal (EPP) en Snipe-SSL. Este detalle es informativo y **no sustituye la constancia de recepción firmada**.

{{ $introduction_line }}

@include('mail.markdown.partials.ssl-worker-summary', ['worker' => $ssl_worker])

**Relación del EPP entregado**

@component('mail::table')
| Equipo / descripción | Referencia | Cantidad |
| :--- | :--- | ---: |
| {{ $item->name }} | {{ $item->model_number ?: ($item->item_no ?: 'No registrada') }} | {{ (int) ($qty ?? 1) }} |
@endcomponent

@component('mail::table')
| Dato de la entrega | Valor |
| :--- | :--- |
| Categoría | {{ $item->category?->name ?? 'No registrada' }} |
| Empresa del EPP | {{ $item->company?->name ?? 'No registrada' }} |
| Fecha de esta notificación | {{ $ssl_notice_at }} |
@if ($admin)
| Responsable del registro | {{ $admin->display_name }} |
@endif
@endcomponent

@if ($note)
**Observaciones registradas:** {{ $note }}
@endif

El EPP debe utilizarse conforme a los riesgos y procedimientos de trabajo, mantenerse en condiciones adecuadas y reemplazarse cuando corresponda. Registrar el consumo en Snipe-SSL no equivale a una devolución física.

@if (($req_accept == 1) && ($eula!='') && $accept_url)
{{ trans('mail.read_the_terms_and_click') }}
@elseif (($req_accept == 1) && ($eula=='') && $accept_url)
{{ trans('mail.click_on_the_link_asset') }}
@elseif (($req_accept == 0) && ($eula!=''))
{{ trans('mail.read_the_terms') }}
@endif

@if ($eula)
@component('mail::panel')
{!! $eula !!}
@endcomponent
@endif

@if ($req_accept == 1 && $accept_url)
**[✔ {{ trans('mail.i_have_read') }}]({{ $accept_url }})**
@endif

Atentamente,  
**{{ $snipeSettings->site_name ?? 'Gestión SSL' }}**

*El registro de entrega y la evidencia firmada se conservan por separado.*
@endcomponent
