@extends('layouts/default')

@section('title')
    {{ trans('general.ssl_trainings') }}
    @parent
@stop

@section('content')
<x-container>
    <div class="row" style="margin-bottom: 15px;">
        <div class="col-md-8">
            <h2><i class="fa-solid fa-graduation-cap" aria-hidden="true"></i> Capacitaciones</h2>
            <p class="text-muted">Sesiones de SSL, Gestión Humana y Tecnología. La duración registrada es la duración efectiva de la sesión; las horas-hombre se calcularán al confirmar asistencia en la etapa 4.2.</p>
        </div>
        <div class="col-md-4 text-right">
            @can('create', \App\Models\Accessory::class)
                <a class="btn btn-primary" href="{{ route('ssl.trainings.create') }}">
                    <i class="fa fa-plus" aria-hidden="true"></i> Nueva capacitación
                </a>
            @endcan
        </div>
    </div>
    @if ($categories->isEmpty())
        <div class="alert alert-warning">
            No existen categorías de capacitación activas. Crea en <strong>Configuraciones → Categorías</strong> una o más de las siguientes categorías de tipo <strong>Accesorio</strong>: <code>CAPACITACION_SSL</code>, <code>CAPACITACION_GH</code>, <code>CAPACITACION_TI</code>.
        </div>
    @endif
    <div class="box box-default">
        <div class="box-header with-border"><h3 class="box-title">Sesiones registradas</h3></div>
        <div class="box-body">
            <form method="GET" action="{{ route('ssl.trainings.index') }}" class="form-inline" style="margin-bottom: 15px;">
                <div class="form-group">
                    <label for="ssl-area-filter">Área</label>
                    <select class="form-control" id="ssl-area-filter" name="area">
                        <option value="">Todas</option>
                        <option value="CAPACITACION_SSL" @selected(request('area') === 'CAPACITACION_SSL')>Seguridad y Salud Laboral</option>
                        <option value="CAPACITACION_GH" @selected(request('area') === 'CAPACITACION_GH')>Gestión Humana</option>
                        <option value="CAPACITACION_TI" @selected(request('area') === 'CAPACITACION_TI')>Tecnología</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="ssl-training-search">Buscar</label>
                    <input id="ssl-training-search" class="form-control" name="search" value="{{ request('search') }}" maxlength="255" placeholder="Nombre o código">
                </div>
                <button type="submit" class="btn btn-default"><i class="fa fa-search" aria-hidden="true"></i> Filtrar</button>
                <a href="{{ route('ssl.trainings.index') }}" class="btn btn-link">Limpiar</a>
            </form>
            <div class="table-responsive">
                <table class="table table-striped table-bordered">
                    <thead><tr>
                        <th>Sesión</th><th>Área</th><th>Fecha de inicio</th><th>Duración</th><th>Modalidad</th><th>Empresa</th><th>Sede</th><th>Estado</th>
                    </tr></thead>
                    <tbody>
                    @forelse ($trainings as $training)
                        <tr>
                            <td><a href="{{ route('ssl.trainings.show', $training->id) }}">{{ $training->name }}</a>
                                @if ($training->model_number)<small class="text-muted">({{ $training->model_number }})</small>@endif
                            </td>
                            <td>{{ \App\Models\User::sslTrainingAreaLabel($training->category?->name) }}</td>
                            <td>{{ $training->ssl_training_starts_at?->format('d/m/Y H:i') ?? 'Por estructurar' }}</td>
                            <td>{{ $training->ssl_training_duration_minutes ? number_format($training->ssl_training_duration_minutes / 60, 2, ',', '.') . ' h' : '—' }}</td>
                            <td>{{ ucfirst($training->ssl_training_modality ?? '—') }}</td>
                            <td>{{ $training->company?->name ?? '—' }}</td>
                            <td>{{ $training->location?->name ?? '—' }}</td>
                            <td>@switch($training->ssl_training_status)
                                @case('planned') Programada @break
                                @case('completed') Realizada (sin asistencia confirmada) @break
                                @case('cancelled') Cancelada @break
                                @default Referencia anterior
                            @endswitch</td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="text-center">No hay sesiones registradas con estos filtros.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            {{ $trainings->links() }}
        </div>
    </div>

    {{-- Se mantiene la tabla original de referencias con sus herramientas nativas
         de busqueda, columnas y exportacion mientras se adapta el reporte SSL. --}}
    <x-box name="ssl-trainings-references" sr_only_title>
        <h4>Consulta técnica y exportación de referencias</h4>
        <p class="text-muted">Se conserva la vista anterior de Capacitaciones. No equivale a un registro de asistencia confirmada ni incluye las horas-hombre.</p>
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
