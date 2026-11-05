@extends('layouts/default')

@section('title')
    {{ trans('general.ssl_trainings') }}
    @parent
@stop

@section('content')
    <x-container>
        {{-- SSL: etapa de consulta; el registro y asistencia son el siguiente desarrollo. --}}
        <p class="text-muted">
            <i class="fa-solid fa-graduation-cap fa-fw" aria-hidden="true"></i>
            Capacitaciones registradas bajo la categoría CAPACITACION_SSL.
            Esta pantalla es de consulta: el registro de sesiones, la asignación de participantes
            y la impresión de asistencia se habilitarán en la siguiente etapa.
        </p>

        <x-box name="ssl-trainings" sr_only_title>
            <x-table
                name="sslTrainings"
                api_url="{{ route('api.accessories.index', ['ssl_scope' => 'capacitaciones']) }}"
                :presenter="\App\Presenters\AccessoryPresenter::sslTrainingDataTableLayout()"
                show_column_search="true"
                export_filename="capacitaciones-{{ date('Y-m-d') }}"
            />
        </x-box>
    </x-container>
@stop

@section('moar_scripts')
    @include('partials.bootstrap-table')
@stop
