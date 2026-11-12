<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Planilla de asistencia SSL - {{ $training->name }}</title>
    @include('users.partials.ssl-document-styles')
    <style>
        /* La tabla de firmas de varios participantes necesita mas ancho. */
        @page { size: letter landscape; margin: 9mm 10mm 9mm; }
        .ssl-document-header { padding-bottom: 7px; margin-bottom: 10px; }
        .ssl-document-section { margin: 11px 0 6px; }
        .ssl-signatures { margin-top: 10px; }
        .ssl-signatures .ssl-line { margin-top: 24px; }
        .ssl-doc-footer { margin-top: 10px; }
        .ssl-attendance-grid { font-size: 8.2pt; }
        .ssl-attendance-grid th, .ssl-attendance-grid td { padding: 5px 5px; }
        .ssl-attendance-grid .ssl-sign-blank { height: 32px; }
        .ssl-attendance-grid .ssl-thumb-blank { min-height: 32px; }
        .ssl-attendance-data { display: grid; grid-template-columns: 1fr 1fr; gap: 3px 14px; margin: 8px 0; }
        .ssl-attendance-data > div { min-width: 0; }
        .ssl-attendance-muted { color: #535b61; font-size: 8pt; }
        .ssl-attendance-summary { border: 1px solid #555; padding: 7px 10px; margin: 10px 0; }
        .ssl-attendance-summary span { display: inline-block; margin-right: 22px; }
        @media screen {
            .ssl-doc-paper { max-width: 279mm; }
        }
        @media print {
            .ssl-doc-paper { max-width: none; }
            .ssl-attendance-grid thead { display: table-header-group; }
            .ssl-attendance-grid tr { break-inside: avoid; page-break-inside: avoid; }
        }
    </style>
</head>
<body>
@include('users.partials.ssl-document-toolbar')
<main class="ssl-doc-paper ssl-document-body">
    <header class="ssl-document-header">
        <div class="ssl-document-brand">
            @if (($snipeSettings?->logo_print_assets == '1') && ! empty($snipeSettings?->acceptance_pdf_logo))
                <img alt="Logotipo institucional" src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($snipeSettings->acceptance_pdf_logo) }}">
            @endif
            {{ $training->company?->name ?: ($snipeSettings->site_name ?? 'Gestión SSL') }}
        </div>
        <div class="ssl-document-title">PLANILLA INSTITUCIONAL<br>DE ASISTENCIA A CAPACITACIÓN</div>
        <div class="ssl-document-meta">
            <strong>Fecha de impresión:</strong> {{ $printedAt->format('d/m/Y') }}<br>
            <strong>Sesión n.º:</strong> {{ $training->id }}<br>
            <strong>Control:</strong> Snipe-SSL
        </div>
    </header>

    <section aria-label="Datos de la capacitación" class="ssl-attendance-data">
        <div><strong>Capacitación:</strong> {{ $training->name }}</div>
        <div><strong>Área responsable:</strong> {{ \App\Models\User::sslTrainingAreaLabel($training->category?->name) }}</div>
        <div><strong>Empresa:</strong> {{ $training->company?->name ?? 'Sin asignar' }}</div>
        <div><strong>Sede:</strong> {{ $training->location?->name ?? 'Sin asignar' }}</div>
        <div><strong>Fecha y hora de inicio:</strong> {{ $training->ssl_training_starts_at?->format('d/m/Y H:i') ?? 'No registrada' }}</div>
        <div><strong>Fecha y hora de finalización:</strong> {{ $training->ssl_training_ends_at?->format('d/m/Y H:i') ?? 'No registrada' }}</div>
        <div><strong>Duración efectiva:</strong> {{ $training->ssl_training_duration_minutes ? $training->ssl_training_duration_minutes.' minutos ('.number_format($training->ssl_training_duration_minutes / 60, 2, ',', '.').' h)' : 'No registrada' }}</div>
        <div><strong>Modalidad:</strong> {{ ucfirst($training->ssl_training_modality ?? 'No definida') }}</div>
        <div><strong>Facilitador(a):</strong> {{ $training->ssl_training_facilitator ?: 'No registrado' }}</div>
        <div><strong>Código / referencia:</strong> {{ $training->model_number ?: 'No registrado' }}</div>
        <div><strong>Estado:</strong>
            {{ ['planned' => 'Programada', 'completed' => 'Realizada', 'cancelled' => 'Cancelada'][$training->ssl_training_status] ?? 'Referencia anterior' }}
        </div>
    </section>

    @if ($training->notes)
        <p class="ssl-attendance-muted"><strong>Notas de la sesión:</strong> {{ $training->notes }}</p>
    @endif

    <div class="ssl-document-section">Control nominal de participantes</div>
    <table class="ssl-grid ssl-attendance-grid" aria-label="Participantes y estado de asistencia">
        <thead><tr>
            <th style="width: 4%">N.º</th>
            <th style="width: 22%">Nombre y apellido</th>
            <th style="width: 12%">Cédula / N.º empleado</th>
            <th style="width: 16%">Cargo</th>
            <th style="width: 13%">Estado</th>
            <th style="width: 8%">Min. acreditados</th>
            <th style="width: 16%">Firma</th>
            <th style="width: 9%">Huella</th>
        </tr></thead>
        <tbody>
        @forelse ($attendances as $attendance)
            @php
                // El mismo permiso de Usuarios que protege la impresión de datos personales.
                $worker = $attendance->sslAttendanceWorker;
                $workerVisible = $worker && \Illuminate\Support\Facades\Gate::allows('view', $worker);
                $statusNames = ['registered' => 'Inscrito', 'attended' => 'Asistió', 'absent' => 'Ausente', 'excused' => 'Justificado'];
                $credited = $training->ssl_training_status === 'completed' && $attendance->ssl_attendance_status === 'attended'
                    ? (int) $attendance->ssl_attended_minutes : 0;
            @endphp
            <tr>
                <td class="ssl-center">{{ $loop->iteration }}</td>
                <td>
                    @if ($workerVisible)
                        {{ $worker->display_name }}
                    @else
                        <span class="ssl-muted">No disponible o sin acceso (ID {{ $attendance->assigned_to }})</span>
                    @endif
                </td>
                <td>{{ $workerVisible ? ($worker->employee_num ?: 'No registrada') : '—' }}</td>
                <td>{{ $workerVisible ? ($worker->jobtitle ?: 'No registrado') : '—' }}</td>
                <td class="ssl-center">{{ $statusNames[$attendance->ssl_attendance_status] ?? 'No definido' }}</td>
                <td class="ssl-center">{{ $credited }}</td>
                <td class="ssl-sign-blank"></td>
                <td class="ssl-thumb-blank"></td>
            </tr>
        @empty
            <tr><td colspan="8" class="ssl-muted">No existen participantes inscritos en esta sesión. Esta planilla no acredita asistencia.</td></tr>
        @endforelse
        </tbody>
    </table>

    <div class="ssl-attendance-summary ssl-keep">
        <span><strong>Inscritos:</strong> {{ $attendances->count() }}</span>
        <span><strong>Asistieron:</strong> {{ $confirmedCount }}</span>
        <span><strong>Ausentes:</strong> {{ $training->ssl_training_status === 'completed' ? $attendances->where('ssl_attendance_status', 'absent')->count() : 0 }}</span>
        <span><strong>Justificados:</strong> {{ $training->ssl_training_status === 'completed' ? $attendances->where('ssl_attendance_status', 'excused')->count() : 0 }}</span>
        <span><strong>Horas-hombre:</strong> {{ number_format($creditedMinutes / 60, 2, ',', '.') }} h</span>
    </div>
    <p class="ssl-attendance-muted">Las horas-hombre se calculan solo con las asistencias confirmadas y los minutos acreditados en sesiones marcadas como «Realizada». Las casillas de firma y huella permanecen vacías hasta la recepción física de esa evidencia; un estado «Asistió» en el sistema no equivale por sí solo a una firma.</p>

    <section class="ssl-keep" aria-label="Observaciones de capacitación">
        <strong>Observaciones para esta planilla:</strong>
        <div id="ssl-observation-print" class="ssl-observation" aria-live="polite">Sin observaciones adicionales.</div>
    </section>

    <section class="ssl-signatures ssl-keep" aria-label="Responsables de la actividad">
        <div>
            <strong>Facilitador(a) / Instructor(a):</strong>
            <div class="ssl-line"></div>
            <div class="ssl-small">{{ $training->ssl_training_facilitator ?: 'Nombre, C.I. y firma' }}</div>
        </div>
        <div>
            <strong>Responsable del área organizadora:</strong>
            <div class="ssl-line"></div>
            <div class="ssl-small">Nombre, cargo, C.I. y firma</div>
        </div>
    </section>
    <section class="ssl-signatures ssl-keep" aria-label="Control institucional">
        <div>
            <strong>Testigo / Seguridad y Salud Laboral:</strong>
            <div class="ssl-line"></div>
            <div class="ssl-small">Nombre, cargo y firma</div>
        </div>
        <div>
            <strong>Fecha de firma de la planilla:</strong>
            <div class="ssl-line"></div>
            <div class="ssl-small">Día / Mes / Año (diferente de la fecha de la sesión, si corresponde)</div>
        </div>
    </section>
    <p class="ssl-doc-footer">Documento generado desde Snipe-SSL con los datos existentes de la sesión n.º {{ $training->id }}.
        Es una representación imprimible del registro administrativo; las firmas y huellas deben obtenerse físicamente.
        La observación añadida en pantalla no se guarda automáticamente en la base de datos.
        Se recomienda conservar una copia firmada para soporte documental.</p>
</main>
</body>
</html>
