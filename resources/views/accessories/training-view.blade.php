@extends('layouts/default')
@section('title')
    {{ $training->name }} — Capacitación
    @parent
@stop
@section('content')
<x-container>
    <div class="row" style="margin-bottom: 15px;">
        <div class="col-md-8"><h2><i class="fa-solid fa-graduation-cap" aria-hidden="true"></i> {{ $training->name }}</h2></div>
        <div class="col-md-4 text-right">
            @can('update', $training)
                <a class="btn btn-primary" href="{{ route('ssl.trainings.edit', $training->id) }}"><i class="fa fa-edit" aria-hidden="true"></i> Editar sesión</a>
            @endcan
            @can('view', \App\Models\User::class)
                <a class="btn btn-default" href="{{ route('ssl.trainings.attendance-sheet', $training->id) }}" target="_blank" rel="noopener"><i class="fa fa-print" aria-hidden="true"></i> Planilla de asistencia SSL</a>
            @endcan
            <a class="btn btn-default" href="{{ route('ssl.trainings.reports') }}"><i class="fa fa-bar-chart" aria-hidden="true"></i> Reporte consolidado</a>
            <a class="btn btn-default" href="{{ route('ssl.trainings.index') }}">Volver</a>
        </div>
    </div>
    @if (session('success')) <div class="alert alert-success">{{ session('success') }}</div> @endif
    @if (session('warning')) <div class="alert alert-warning">{{ session('warning') }}</div> @endif
    @if (session('error')) <div class="alert alert-danger">{{ session('error') }}</div> @endif
    @if ($errors->any())
        <div class="alert alert-danger"><strong>Corrige los datos:</strong><ul>
            @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
        </ul></div>
    @endif
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
                        @case('completed') Realizada @break
                        @case('cancelled') Cancelada @break
                        @default Referencia anterior
                    @endswitch
                </dd>
                <dt>Observaciones</dt><dd>{!! nl2br(e($training->notes ?: '—')) !!}</dd>
            </dl>
        </div>
    </div>
    <div class="row">
        <div class="col-sm-4">
            <div class="small-box bg-aqua" style="padding: 10px 18px;">
                <h3>{{ $attendances->count() }}</h3><p>Participantes registrados</p>
            </div>
        </div>
        <div class="col-sm-4">
            <div class="small-box bg-green" style="padding: 10px 18px;">
                <h3>{{ $attendances->where('ssl_attendance_status', 'attended')->count() }}</h3><p>Asistencias confirmadas</p>
            </div>
        </div>
        <div class="col-sm-4">
            <div class="small-box bg-blue" style="padding: 10px 18px;">
                <h3>{{ number_format($personMinutes / 60, 2, ',', '.') }}</h3><p>Horas-hombre acreditadas</p>
            </div>
        </div>
    </div>
    @if ($training->ssl_training_status !== 'completed')
        <div class="alert alert-info">Las horas-hombre solo se acreditan cuando la sesión figura como <strong>Realizada</strong> y la asistencia individual ha sido confirmada. Una inscripción no equivale a participación.</div>
    @endif
    <div class="box box-default">
        <div class="box-header with-border"><h3 class="box-title">Trabajadores y asistencia</h3></div>
        <div class="box-body">
            @can('update', $training)
                @if ($training->ssl_training_status !== 'cancelled' && $training->ssl_training_starts_at && $training->ssl_training_duration_minutes)
                    <form method="POST" action="{{ route('ssl.trainings.participants.store', $training->id) }}" class="form-horizontal" style="margin-bottom: 20px;">
                        @csrf
                        <fieldset>
                            <legend style="font-size: 16px;">Inscribir trabajador</legend>
                            <x-input.user-select
                                label="Trabajador"
                                name="assigned_user"
                                :selected="old('assigned_user')"
                                :companyId="$training->company_id"
                            />
                            <div class="form-group">
                                <label class="col-sm-3 control-label" for="ssl-new-participant-note">Observaciones</label>
                                <div class="col-sm-7"><input id="ssl-new-participant-note" class="form-control" name="note" maxlength="2000" value="{{ old('note') }}" placeholder="Opcional"></div>
                            </div>
                            <div class="form-group"><div class="col-sm-offset-3 col-sm-7">
                                <button class="btn btn-primary" type="submit"><i class="fa fa-user-plus" aria-hidden="true"></i> Inscribir participante</button>
                            </div></div>
                        </fieldset>
                    </form>
                    <p class="text-muted">Si aún no existen trabajadores, crea primero un usuario de prueba desde Personas. No se requieren datos reales para validar este módulo.</p>
                @endif
            @endcan
            <div class="table-responsive"><table class="table table-striped table-bordered">
                <thead><tr><th>Trabajador</th><th>Cédula / registro</th><th>Control de asistencia</th></tr></thead>
                <tbody>
                @forelse ($attendances as $attendance)
                    <tr>
                        <td>
                            @if ($attendance->sslAttendanceWorker)
                                <a href="{{ route('users.show', $attendance->sslAttendanceWorker->id) }}">{{ $attendance->sslAttendanceWorker->display_name }}</a>
                            @else
                                <span class="text-muted">Trabajador no disponible (ID {{ $attendance->assigned_to }})</span>
                            @endif
                        </td>
                        <td>
                            {{ $attendance->sslAttendanceWorker?->employee_num ?: '—' }}
                            <div class="text-muted">Registro #{{ $attendance->id }} · {{ $attendance->created_at ? \Carbon\Carbon::parse($attendance->created_at)->format('d/m/Y H:i') : '—' }}</div>
                        </td>
                        <td>
                            @can('update', $training)
                                <form method="POST" action="{{ route('ssl.trainings.participants.update', [$training->id, $attendance->id]) }}" class="form-inline">
                                    @csrf @method('PATCH')
                                    <label class="sr-only" for="ssl-status-{{ $attendance->id }}">Estado</label>
                                    <select name="ssl_attendance_status" id="ssl-status-{{ $attendance->id }}" class="form-control" style="max-width: 190px; margin-bottom: 3px;">
                                        @foreach (['registered' => 'Inscrito', 'attended' => 'Asistió', 'absent' => 'Ausente', 'excused' => 'Justificado'] as $value => $label)
                                            <option value="{{ $value }}" @selected($attendance->ssl_attendance_status === $value)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                    <label class="sr-only" for="ssl-minutes-{{ $attendance->id }}">Minutos acreditados</label>
                                    <input type="number" id="ssl-minutes-{{ $attendance->id }}" class="form-control" name="ssl_attended_minutes" value="{{ $attendance->ssl_attendance_status === 'attended' ? $attendance->ssl_attended_minutes : $training->ssl_training_duration_minutes }}" min="0" max="{{ $training->ssl_training_duration_minutes }}" style="width: 100px; margin-bottom: 3px;" title="Minutos acreditados solo si asistió">
                                    <label class="sr-only" for="ssl-note-{{ $attendance->id }}">Observación</label>
                                    <input id="ssl-note-{{ $attendance->id }}" name="note" class="form-control" value="{{ $attendance->note }}" maxlength="2000" placeholder="Observaciones" style="width: 180px; margin-bottom: 3px;">
                                    <button type="submit" class="btn btn-success btn-sm"><i class="fa fa-save" aria-hidden="true"></i> Guardar</button>
                                </form>
                                @if ($attendance->ssl_attendance_status === 'registered')
                                    <form method="POST" action="{{ route('ssl.trainings.participants.destroy', [$training->id, $attendance->id]) }}" onsubmit="return confirm('¿Retirar esta inscripción no confirmada?');" style="display: inline-block; margin-top: 4px;">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-default btn-xs" type="submit">Retirar inscripción</button>
                                    </form>
                                @endif
                            @else
                                {{ ['registered' => 'Inscrito', 'attended' => 'Asistió', 'absent' => 'Ausente', 'excused' => 'Justificado'][$attendance->ssl_attendance_status] ?? '—' }}
                                @if ($attendance->ssl_attendance_status === 'attended')
                                    · {{ $attendance->ssl_attended_minutes }} min
                                @endif
                                @if ($attendance->note)<div class="text-muted">{{ $attendance->note }}</div>@endif
                            @endcan
                            @if ($attendance->ssl_attendance_confirmed_at)
                                <div class="text-muted"><small>Confirmado: {{ \Carbon\Carbon::parse($attendance->ssl_attendance_confirmed_at)->format('d/m/Y H:i') }}</small></div>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="text-center text-muted">Todavía no hay trabajadores inscritos en esta sesión. El total de horas-hombre es 0.</td></tr>
                @endforelse
                </tbody>
            </table></div>
        </div>
    </div>

    {{-- SSL 5.5: anexos firmados por sesion. La carga y descarga usan las rutas
         nativas de Snipe-IT para Accesorios: no se modifica la asistencia. --}}
    @can('files', $training)
        <div class="box box-default" id="ssl-training-signed-documents">
            <div class="box-header with-border">
                <h3 class="box-title"><i class="fa fa-paperclip" aria-hidden="true"></i> Planillas firmadas y evidencias de la sesión</h3>
            </div>
            <div class="box-body">
                <p class="text-muted">
                    Adjunta aquí el PDF escaneado o las fotografías de la planilla firmada por los participantes
                    y responsables. Cada documento queda vinculado únicamente a esta sesión y conserva
                    el registro de su carga. Subir un archivo no modifica estados de asistencia ni horas-hombre.
                </p>
                @can('manageFiles', $training)
                    <form method="POST"
                          action="{{ route('ui.files.store', ['object_type' => 'accessories', 'id' => $training->id]) }}"
                          enctype="multipart/form-data"
                          accept-charset="UTF-8"
                          style="margin-bottom: 18px;">
                        @csrf
                        <div class="form-group">
                            <label for="ssl-signed-attendance-files">Planillas firmadas (PDF o fotografías)</label>
                            <input id="ssl-signed-attendance-files" type="file" name="file[]"
                                   class="form-control" accept=".pdf,.jpg,.jpeg,.png" multiple required>
                            <p class="help-block">
                                Puedes seleccionar varias páginas. La extensión y el tamaño se validan
                                también con la configuración de archivos permitidos de Snipe-IT.
                            </p>
                        </div>
                        <div class="form-group">
                            <label for="ssl-signed-attendance-notes">Descripción del respaldo</label>
                            <textarea id="ssl-signed-attendance-notes" name="notes" rows="2"
                                      class="form-control" maxlength="1000">{{ old('notes', '[SSL-ASISTENCIA-FIRMADA] Planilla de asistencia firmada') }}</textarea>
                        </div>
                        <button type="submit" class="btn btn-primary">
                            <i class="fa fa-upload" aria-hidden="true"></i> Adjuntar evidencia firmada
                        </button>
                    </form>
                @endcan

                <h4>Historial de anexos ({{ $documents->count() }})</h4>
                <div class="table-responsive">
                    <table class="table table-striped table-bordered">
                        <thead>
                            <tr><th>Documento</th><th>Descripción / tipo</th><th>Fecha de carga</th><th>Cargado por</th></tr>
                        </thead>
                        <tbody>
                            @forelse ($documents as $document)
                                <tr>
                                    <td>
                                        <a href="{{ route('ui.files.show', ['object_type' => 'accessories', 'id' => $training->id, 'file_id' => $document->id]) }}">
                                            <i class="fa fa-download" aria-hidden="true"></i>
                                            {{ $document->filename ?: 'Archivo #'.$document->id }}
                                        </a>
                                        <small class="text-muted">#{{ $document->id }}</small>
                                    </td>
                                    <td>
                                        @if (str_contains((string) $document->note, '[SSL-ASISTENCIA-FIRMADA]'))
                                            <strong>Asistencia firmada</strong>
                                        @else
                                            <span class="text-muted">Otro anexo de la capacitación</span>
                                        @endif
                                        @if ($document->note)
                                            <div class="text-muted">{{ $document->note }}</div>
                                        @endif
                                    </td>
                                    <td>{{ $document->created_at ? \Carbon\Carbon::parse($document->created_at)->format('d/m/Y H:i') : '—' }}</td>
                                    <td>{{ $document->adminuser?->display_name ?? 'No disponible' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="text-center text-muted">Todavía no se han adjuntado evidencias a esta capacitación.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <p class="text-muted"><small>Esta pantalla no incluye eliminación de anexos para priorizar
                    su conservación histórica. El expediente de la sesión debe incluirse en las copias de seguridad
                    de la base de datos y de los archivos privados del servidor.</small></p>
            </div>
        </div>
    @endcan
</x-container>
@stop
