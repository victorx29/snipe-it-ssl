@extends('layouts/default')

@section('title0')
    @if (request('status') === 'Archived')
        {{ trans('general.archived') }}
    @elseif (request('status') === 'Deleted')
        {{ ucfirst(trans('general.deleted')) }}
    @endif
    {{ trans('general.consumables') }}
@stop

{{-- Page title --}}
@section('title')
    @yield('title0')  @parent
@stop

{{-- Page content --}}
@section('content')
    <x-container>
        {{-- SSL: leyenda visual. Las consultas, permisos y estados siguen siendo los originales de Snipe-IT. --}}
        <p class="text-muted">
            <i class="fa-solid fa-helmet-safety fa-fw" aria-hidden="true"></i>
            {{ trans('admin/consumables/general.inventory_description') }}
        </p>
        {{-- SSL 3.2: la talla se reconoce en model_number (M o ...-M).
             Los filtros de empresa y ubicacion existentes siguen disponibles
             en la busqueda avanzada; no se altera el alcance FMCS. --}}
        @php
            $sslSizeFilter = request()->query('ssl_size');
            $sslSizeFilter = is_string($sslSizeFilter) ? trim($sslSizeFilter) : '';
            $sslReferenceFilter = request()->query('ssl_reference');
            $sslReferenceFilter = is_string($sslReferenceFilter) ? trim($sslReferenceFilter) : '';
        @endphp
        <form method="GET" action="{{ route('consumables.index') }}" class="form-inline" style="margin-bottom: 12px;">
            @if (in_array(request('status'), ['Archived', 'Deleted'], true))
                <input type="hidden" name="status" value="{{ request('status') }}">
            @endif
            <div class="form-group" style="margin-right: 10px; margin-bottom: 6px;">
                <label for="ssl_size_filter">Talla</label>
                <input type="text" name="ssl_size" id="ssl_size_filter" class="form-control input-sm"
                       value="{{ $sslSizeFilter }}" placeholder="M, XL, 42" maxlength="16" style="width: 105px;">
            </div>
            <div class="form-group" style="margin-right: 10px; margin-bottom: 6px;">
                <label for="ssl_reference_filter">Referencia o codigo</label>
                <input type="text" name="ssl_reference" id="ssl_reference_filter" class="form-control input-sm"
                       value="{{ $sslReferenceFilter }}" placeholder="Ej.: CAM-CORP-MAS-M" maxlength="191" style="min-width: 210px;">
            </div>
            <button type="submit" class="btn btn-primary btn-sm" style="margin-bottom: 6px;">
                <x-icon type="search" class="fa-fw" /> Filtrar
            </button>
            <a class="btn btn-default btn-sm" style="margin-bottom: 6px;"
               href="{{ route('consumables.index', in_array(request('status'), ['Archived', 'Deleted'], true) ? ['status' => request('status')] : []) }}">Limpiar</a>
            <div class="help-block" style="margin-top: 4px;">
                La talla se busca como valor unico (M) o sufijo de referencia (-M).
                Para filtrar por empresa y sede, usa la busqueda avanzada de la tabla.
            </div>
        </form>
        <x-box name="consumables" sr_only_title>
            {{-- Remaining and % Remaining measure "available to give out,"
                 which has no meaning for archived (retired) or deleted
                 rows. Drop both columns on those two filter views. --}}
            <x-table.consumables
                name="consumables"
                export_name="epp"
                :route="route('api.consumables.index', ['status' => e(request('status')), 'ssl_size' => $sslSizeFilter, 'ssl_reference' => $sslReferenceFilter])"
                :presenter="\App\Presenters\ConsumablePresenter::dataTableLayout(
                    request('status') === 'Deleted',
                    in_array(request('status'), ['Archived', 'Deleted'], true) ? ['remaining', 'percent_remaining'] : [],
                )"
            />
        </x-box>
        <x-shiftclick/>
    </x-container>
@can('update', \App\Models\Consumable::class)
    <x-modals.adjust-quantity />
@endcan
@stop

@section('moar_scripts')
@include ('partials.bootstrap-table', ['exportFile' => 'epp-export', 'search' => true,'showFooter' => true, 'columns' => \App\Presenters\ConsumablePresenter::dataTableLayout()])
@stop
