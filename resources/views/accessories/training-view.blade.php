@extends('layouts/default')
@section('title')
    {{ $training->name }} — Capacitación
    @parent
@stop
@section('content')
<x-container>
    <div class="row" style="margin-bottom: 15px;">
        <div class="col-md-9"><h2><i class="fa-solid fa-graduation-cap" aria-hidden="true"></i> {{ $training->name }}</h2></div>
        <div class="col-md-3 text-right">
            @can('update', $training)
                <a class="btn btn-primary" href="{{ route('ssl.trainings.edit', $training->id) }}"><i class="fa fa-edit" aria-hidden="true"></i> Editar sesión</a>
            @endcan
            <a class="btn btn-default" href="{{ route('ssl.trainings.index') }}">Volver</a>
        </div>
    </div>
    <div class="box box-default">
        <div class="box-header with-border"><h3 class="box-title">Ficha de capacitación</h3></div>
        <div class="box-body">
            <dl class="dl-horizontal">
                <dt>Área responsable</dt><dd>{{ \App\Models\User::sslTrainingAreaLabel($training->category?->name) }}</dd>
                <dt>Empresa</dt><dd>{{ $training->company?->name ?? 'Sin asignar' }}</dd>
                <dt>Sede</dt><dd>{{ $training->location?->name ?? 'Sin asignar' }}</dd>
                <dt>Código</dt><dd>{{ $training->model_number ?: '—' }}</dd>
                <dt>Inicio</dt><dd>{{ $training->ssl_training_starts_at?->format('d/m/Y H:i') ?? 'Sin estructurar' }}</dd>
                <dt>Finalización</dt><dd>{{ $training->ssl_training_ends_at?->format('d/m/Y H:i') ?? 'Sin estructurar' }}</dd>
                <dt>Duración efectiva</dt><dd>{{ $training->ssl_training_duration_minutes ? number_format($training->ssl_training_duration_minutes / 60, 2, ',', '.') . ' horas' : 'Sin estructurar' }}</dd>
                <dt>Modalidad</dt><dd>{{ ucfirst($training->ssl_training_modality ?? 'No definida') }}</dd>
                <dt>Facilitador</dt><dd>{{ $training->ssl_training_facilitator ?: '—' }}</dd>
                <dt>Estado</dt><dd>
                    @switch($training->ssl_training_status)
                        @case('planned') Programada @break
                        @case('completed') Realizada (asistencia por confirmar) @break
                        @case('cancelled') Cancelada @break
                        @default Referencia anterior
                    @endswitch
                </dd>
                <dt>Observaciones</dt><dd>{!! nl2br(e($training->notes ?: '—')) !!}</dd>
            </dl>
            <div class="alert alert-info">
                <strong>Asistencia y horas-hombre:</strong> pendientes de la etapa 4.2. No se consideran asistentes confirmados los registros antiguos de asignación de accesorios. Se registrarán de forma individual antes de calcular indicadores.
            </div>
        </div>
    </div>
</x-container>
@stop
