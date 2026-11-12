@extends('layouts/default')

@section('title')
    Reporte de Capacitaciones SSL
    @parent
@stop

@section('content')
<x-container>
    <div class="row" style="margin-bottom: 15px;">
        <div class="col-md-8">
            <h2><i class="fa-solid fa-chart-column" aria-hidden="true"></i> Reporte consolidado de Capacitaciones</h2>
            <p class="text-muted">Seguridad y Salud Laboral, Gestión Humana y Tecnología. Solo las sesiones realizadas con asistencia confirmada acreditan horas-hombre.</p>
        </div>
        <div class="col-md-4 text-right">
            <a href="{{ route('ssl.trainings.index') }}" class="btn btn-default"><i class="fa fa-arrow-left" aria-hidden="true"></i> Volver a Capacitaciones</a>
        </div>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger">
            <strong>Revisa los filtros:</strong>
            <ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    <div class="box box-default">
        <div class="box-header with-border"><h3 class="box-title">Filtros del reporte</h3></div>
        <div class="box-body">
            <form method="GET" action="{{ route('ssl.trainings.reports') }}" class="row">
                <div class="form-group col-md-3 col-sm-6">
                    <label for="ssl-report-area">Área responsable</label>
                    <select name="area" id="ssl-report-area" class="form-control">
                        <option value="">Todas las áreas</option>
                        <option value="CAPACITACION_SSL" @selected(($filters['area'] ?? '') === 'CAPACITACION_SSL')>Seguridad y Salud Laboral</option>
                        <option value="CAPACITACION_GH" @selected(($filters['area'] ?? '') === 'CAPACITACION_GH')>Gestión Humana</option>
                        <option value="CAPACITACION_TI" @selected(($filters['area'] ?? '') === 'CAPACITACION_TI')>Tecnología</option>
                    </select>
                </div>
                <div class="form-group col-md-3 col-sm-6">
                    <label for="ssl-report-company">Empresa</label>
                    <select name="company_id" id="ssl-report-company" class="form-control">
                        <option value="">Todas las empresas visibles</option>
                        @foreach ($companies as $company)
                            <option value="{{ $company->id }}" @selected((string) ($filters['company_id'] ?? '') === (string) $company->id)>{{ $company->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group col-md-3 col-sm-6">
                    <label for="ssl-report-location">Sede</label>
                    <select name="location_id" id="ssl-report-location" class="form-control">
                        <option value="">Todas las sedes visibles</option>
                        @foreach ($locations as $location)
                            <option value="{{ $location->id }}" @selected((string) ($filters['location_id'] ?? '') === (string) $location->id)>{{ $location->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group col-md-3 col-sm-6">
                    <label for="ssl-report-status">Estado de la sesión</label>
                    <select name="status" id="ssl-report-status" class="form-control">
                        <option value="">Todos</option>
                        <option value="planned" @selected(($filters['status'] ?? '') === 'planned')>Programada</option>
                        <option value="completed" @selected(($filters['status'] ?? '') === 'completed')>Realizada</option>
                        <option value="cancelled" @selected(($filters['status'] ?? '') === 'cancelled')>Cancelada</option>
                        <option value="legacy" @selected(($filters['status'] ?? '') === 'legacy')>Referencia anterior</option>
                    </select>
                </div>
                <div class="form-group col-md-3 col-sm-6">
                    <label for="ssl-report-from">Inicio desde</label>
                    <input type="date" name="from" id="ssl-report-from" class="form-control" value="{{ $filters['from'] ?? '' }}">
                </div>
                <div class="form-group col-md-3 col-sm-6">
                    <label for="ssl-report-to">Inicio hasta</label>
                    <input type="date" name="to" id="ssl-report-to" class="form-control" value="{{ $filters['to'] ?? '' }}">
                </div>
                <div class="form-group col-md-6 col-sm-12">
                    <label for="ssl-report-search">Nombre o código de capacitación</label>
                    <input type="text" name="search" id="ssl-report-search" maxlength="191" class="form-control" value="{{ $filters['search'] ?? '' }}" placeholder="Buscar sesiones">
                </div>
                <div class="col-md-12" style="margin-bottom: 12px;">
                    <button type="submit" class="btn btn-primary"><i class="fa fa-filter" aria-hidden="true"></i> Aplicar filtros</button>
                    <a href="{{ route('ssl.trainings.reports') }}" class="btn btn-default">Limpiar</a>
                    <a href="{{ route('ssl.trainings.reports.export', array_merge(request()->except(['page', 'level']), ['level' => 'areas'])) }}" class="btn btn-default"><i class="fa fa-download" aria-hidden="true"></i> CSV por área</a>
                    <a href="{{ route('ssl.trainings.reports.export', array_merge(request()->except(['page', 'level']), ['level' => 'sessions'])) }}" class="btn btn-default"><i class="fa fa-download" aria-hidden="true"></i> CSV por sesión</a>
                </div>
            </form>
            <p class="help-block">Las fechas filtran por <strong>inicio de la sesión</strong>. Las referencias antiguas sin fecha aparecen al consultar sin período. Los filtros también se aplican a ambos CSV.</p>
        </div>
    </div>

    <div class="row">
        @foreach ([
            ['Sesiones', $totals['sessions'], 'bg-aqua'],
            ['Realizadas', $totals['completed_sessions'], 'bg-green'],
            ['Inscripciones', $totals['registered'], 'bg-blue'],
            ['Asistieron', $totals['attended'], 'bg-green'],
            ['Ausentes', $totals['absent'], 'bg-yellow'],
            ['Horas-hombre', number_format($totals['credited_minutes'] / 60, 2, ',', '.'), 'bg-aqua'],
        ] as $indicator)
            <div class="col-lg-2 col-md-4 col-sm-6">
                <div class="small-box {{ $indicator[2] }}" style="padding: 10px 15px; min-height: 95px;">
                    <h3 style="font-size: 23px; margin-top: 3px;">{{ $indicator[1] }}</h3>
                    <p>{{ $indicator[0] }}</p>
                </div>
            </div>
        @endforeach
    </div>

    <div class="box box-default">
        <div class="box-header with-border"><h3 class="box-title">Consolidado por área responsable</h3></div>
        <div class="box-body table-responsive">
            <table class="table table-bordered table-striped">
                <thead><tr>
                    <th>Área</th><th class="text-right">Sesiones</th><th class="text-right">Realizadas</th>
                    <th class="text-right">Inscritos</th><th class="text-right">Asistieron</th>
                    <th class="text-right">Ausentes</th><th class="text-right">Justificados</th>
                    <th class="text-right">Pendientes</th><th class="text-right">Horas-hombre</th>
                    <th class="text-right">Asistencia</th>
                </tr></thead>
                <tbody>
                @foreach ($areas as $summary)
                    <tr>
                        <td>{{ \App\Models\User::sslTrainingAreaLabel($summary['area']) }}</td>
                        <td class="text-right">{{ $summary['sessions'] }}</td>
                        <td class="text-right">{{ $summary['completed_sessions'] }}</td>
                        <td class="text-right">{{ $summary['registered'] }}</td>
                        <td class="text-right">{{ $summary['attended'] }}</td>
                        <td class="text-right">{{ $summary['absent'] }}</td>
                        <td class="text-right">{{ $summary['excused'] }}</td>
                        <td class="text-right">{{ $summary['pending'] }}</td>
                        <td class="text-right">{{ number_format($summary['credited_minutes'] / 60, 2, ',', '.') }}</td>
                        <td class="text-right">{{ $summary['completed_registrations'] ? number_format(100 * $summary['attended'] / $summary['completed_registrations'], 1, ',', '.') . '%' : '—' }}</td>
                    </tr>
                @endforeach
                </tbody>
                <tfoot><tr>
                    <th>TOTAL</th><th class="text-right">{{ $totals['sessions'] }}</th>
                    <th class="text-right">{{ $totals['completed_sessions'] }}</th>
                    <th class="text-right">{{ $totals['registered'] }}</th>
                    <th class="text-right">{{ $totals['attended'] }}</th>
                    <th class="text-right">{{ $totals['absent'] }}</th>
                    <th class="text-right">{{ $totals['excused'] }}</th>
                    <th class="text-right">{{ $totals['pending'] }}</th>
                    <th class="text-right">{{ number_format($totals['credited_minutes'] / 60, 2, ',', '.') }}</th>
                    <th class="text-right">{{ $totals['completed_registrations'] ? number_format(100 * $totals['attended'] / $totals['completed_registrations'], 1, ',', '.') . '%' : '—' }}</th>
                </tr></tfoot>
            </table>
            <p class="help-block">«Inscritos» cuenta registros de todas las sesiones filtradas. La tasa de asistencia usa únicamente inscritos en sesiones <strong>realizadas</strong>; Ausente y Justificado no acreditan horas-hombre. Las horas de un mismo trabajador en sesiones distintas sí se suman.</p>
        </div>
    </div>

    <div class="box box-default">
        <div class="box-header with-border"><h3 class="box-title">Detalle por sesión</h3></div>
        <div class="box-body table-responsive">
            <table class="table table-bordered table-striped">
                <thead><tr><th>Sesión</th><th>Área</th><th>Inicio</th><th>Empresa / Sede</th>
                    <th>Estado</th><th class="text-right">Inscritos</th><th class="text-right">Asistieron</th>
                    <th class="text-right">Ausentes</th><th class="text-right">Justificados</th>
                    <th class="text-right">Pendientes</th><th class="text-right">Horas-hombre</th></tr></thead>
                <tbody>
                @forelse ($trainings as $training)
                    @php
                        $completed = $training->ssl_training_status === 'completed';
                    @endphp
                    <tr>
                        <td><a href="{{ route('ssl.trainings.show', $training->id) }}">{{ $training->name }}</a>
                            @if ($training->model_number)<small class="text-muted">{{ $training->model_number }}</small>@endif
                        </td>
                        <td>{{ \App\Models\User::sslTrainingAreaLabel($training->category?->name) }}</td>
                        <td>{{ $training->ssl_training_starts_at?->format('d/m/Y H:i') ?? 'Sin estructurar' }}</td>
                        <td>{{ $training->company?->name ?? '—' }} / {{ $training->location?->name ?? '—' }}</td>
                        <td>@switch ($training->ssl_training_status)
                            @case('planned') Programada @break
                            @case('completed') Realizada @break
                            @case('cancelled') Cancelada @break
                            @default Referencia anterior
                        @endswitch</td>
                        <td class="text-right">{{ $training->ssl_report_registered_count ?? 0 }}</td>
                        <td class="text-right">{{ $completed ? ($training->ssl_report_attended_count ?? 0) : 0 }}</td>
                        <td class="text-right">{{ $completed ? ($training->ssl_report_absent_count ?? 0) : 0 }}</td>
                        <td class="text-right">{{ $completed ? ($training->ssl_report_excused_count ?? 0) : 0 }}</td>
                        <td class="text-right">{{ $training->ssl_report_pending_count ?? 0 }}</td>
                        <td class="text-right">{{ $completed ? number_format(($training->ssl_report_minutes_sum ?? 0) / 60, 2, ',', '.') : '0,00' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="11" class="text-center">No hay sesiones que coincidan con los filtros.</td></tr>
                @endforelse
                </tbody>
            </table>
            {{ $trainings->links() }}
        </div>
    </div>
</x-container>
@stop
