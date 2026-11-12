@component('mail::message')
# NOTIFICACIÓN DE ENTREGA DE UNIFORMES

Estimado(a) **{{ $target ?: 'responsable de recepción' }}**:

Se registra la entrega del siguiente uniforme o dotación. La relación corresponde **solo a este movimiento**, sin reemplazar la nota institucional firmada ni resumir otras entregas anteriores.

{{ $introduction_line }}

@include('mail.markdown.partials.ssl-worker-summary', ['worker' => $ssl_worker])

**Relación de uniformes entregados**

@component('mail::table')
| Prenda / descripción | Referencia / talla | Cantidad |
| :--- | :--- | ---: |
| {{ $item->name }} | {{ $item->model_number ?: 'No registrada' }} | {{ (int) ($checkout_qty ?? 1) }} |
@endcomponent

@component('mail::table')
| Dato de la entrega | Valor |
| :--- | :--- |
| Categoría | {{ $item->category?->name ?? 'No registrada' }} |
| Empresa del artículo | {{ $item->company?->name ?? 'No registrada' }} |
| Sede / destino | {{ $location ?: 'No registrado' }} |
| Fecha de esta notificación | {{ $ssl_notice_at }} |
@if ($admin)
| Responsable del registro | {{ $admin->display_name }} |
@endif
@endcomponent

@if ($note)
**Observaciones registradas:** {{ $note }}
@endif

Se solicita el uso adecuado de la dotación durante las actividades laborales, su conservación y la comunicación oportuna de deterioros o necesidades de reposición.

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

*Esta notificación no equivale a una firma de recepción.*
@endcomponent
