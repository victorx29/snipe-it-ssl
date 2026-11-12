@component('mail::message')
# NOTIFICACIÓN DE ENTREGA DE ACTIVO

Estimado(a) **{{ $target ?: 'responsable de recepción' }}**:

Se informa que se ha registrado la asignación del siguiente activo en Snipe-SSL. Esta comunicación presenta los datos de la entrega con un formato similar a la nota institucional. **No sustituye la constancia firmada de recepción.**

{{ $introduction_line }}

@include('mail.markdown.partials.ssl-worker-summary', ['worker' => $ssl_worker])

**Relación del activo registrado**

@component('mail::table')
| Descripción del activo | Placa / serie | Cantidad |
| :--- | :--- | ---: |
| {{ $item->name ?: ($item->model?->name ?? 'Activo') }} | {{ $item->asset_tag ?: 'Sin placa' }} / {{ $item->serial ?: 'Sin serie' }} | 1 |
@endcomponent

@component('mail::table')
| Dato de la entrega | Valor |
| :--- | :--- |
| Categoría | {{ $item->model?->category?->name ?? 'No registrada' }} |
| Modelo | {{ $item->model?->name ?? 'No registrado' }} |
| Número de modelo | {{ $item->model?->model_number ?: 'No registrado' }} |
| Empresa del activo | {{ $item->company?->name ?? 'No registrada' }} |
| Destino / ubicación | {{ $location ?: 'No registrado' }} |
| Fecha registrada de asignación | {{ $last_checkout ?: 'No disponible' }} |
| Fecha de esta notificación | {{ $ssl_notice_at }} |
@if (!empty($status))
| Estado del activo | {{ $status }} |
@endif
@if (!empty($expected_checkin))
| Devolución prevista | {{ $expected_checkin }} |
@endif
@if ($admin)
| Responsable del registro | {{ $admin->display_name }} |
@endif
@endcomponent

@if (!empty($custom_fields))
**Otros datos del activo**

@component('mail::table')
| Campo | Valor |
| :--- | :--- |
@foreach ($custom_fields as $customField)
@if (!empty($customField['label']) && array_key_exists('value', $customField))
| {{ $customField['label'] }} | {{ $customField['value'] }} |
@endif
@endforeach
@endcomponent
@endif

@if ($note)
**Observaciones registradas:** {{ $note }}
@endif

El activo debe utilizarse para las funciones autorizadas, mantenerse bajo cuidado adecuado y reportarse cualquier daño, pérdida o novedad siguiendo los procedimientos de la empresa.

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

*Este mensaje informa de un movimiento registrado. La evidencia firmada y el historial de asignaciones se gestionan por separado en Snipe-SSL.*
@endcomponent
