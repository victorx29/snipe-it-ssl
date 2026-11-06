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
            Capacitaciones de Seguridad y Salud Laboral, Gestión Humana y Tecnología.
            Esta pantalla es solo de consulta de referencias. El registro de sesiones, asistencia
            y horas-hombre se habilitará en la etapa operativa de Capacitaciones.
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
