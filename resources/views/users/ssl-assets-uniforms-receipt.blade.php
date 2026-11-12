<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Nota de entrega SSL - Activos y Uniformes - {{ $worker->display_name }}</title>
    @include('users.partials.ssl-document-styles')
</head>
<body>
@include('users.partials.ssl-document-toolbar')
<main class="ssl-doc-paper ssl-document-body">
    <header class="ssl-document-header">
        <div class="ssl-document-brand">
            @if (($snipeSettings?->logo_print_assets == '1') && ! empty($snipeSettings?->acceptance_pdf_logo))
                <img alt="Logotipo institucional" src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($snipeSettings->acceptance_pdf_logo) }}">
            @endif
            {{ $worker->companies->pluck('name')->join(', ') ?: ($snipeSettings->site_name ?? 'Gestión SSL') }}
        </div>
        <div class="ssl-document-title">NOTA DE ENTREGA<br>ACTIVOS Y UNIFORMES</div>
        <div class="ssl-document-meta">
            <strong>Fecha de impresión:</strong> {{ $printedAt->format('d/m/Y') }}<br>
            <strong>Documento:</strong> Constancia SSL<br>
            <strong>Control:</strong> Snipe-SSL
        </div>
    </header>

    <section class="ssl-document-recipient" aria-label="Datos del trabajador">
        <div><strong>Empresa:</strong> {{ $worker->companies->pluck('name')->join(', ') ?: 'No registrada' }}</div>
        <div><strong>Estimado(a):</strong> {{ $worker->display_name }}</div>
        <div><strong>C.I. / empleado:</strong> {{ $worker->employee_num ?: 'No registrado' }}
            &nbsp;&nbsp; <strong>Cargo:</strong> {{ $worker->jobtitle ?: 'No registrado' }}</div>
        <div><strong>Departamento:</strong> {{ $worker->department?->name ?? 'No registrado' }}
            &nbsp;&nbsp; <strong>Sede:</strong> {{ $worker->location?->name ?? 'No registrada' }}</div>
    </section>

    <p>Por medio de la presente se deja constancia de los activos y uniformes registrados a nombre del trabajador indicado. La información se obtiene de las asignaciones visibles en Snipe-SSL; las fechas de entrega son las registradas en cada movimiento y no la fecha de impresión.</p>

    @if ($canViewAssets)
        <div class="ssl-document-section">Relación de activos asignados</div>
        <table class="ssl-grid" aria-label="Activos asignados">
            <thead><tr>
                <th style="width: 5%">#</th>
                <th style="width: 16%">Placa</th>
                <th style="width: 19%">Categoría</th>
                <th style="width: 24%">Modelo</th>
                <th style="width: 20%">N.º de serie</th>
                <th style="width: 16%">Fecha de entrega</th>
            </tr></thead>
            <tbody>
            @forelse ($sslAssets as $asset)
                <tr>
                    <td class="ssl-center">{{ $loop->iteration }}</td>
                    <td>{{ $asset->asset_tag ?: '—' }}</td>
                    <td>{{ $asset->model?->category?->name ?? 'Sin categoría' }}</td>
                    <td>{{ $asset->model?->name ?? $asset->name ?? 'Sin descripción' }}</td>
                    <td>{{ $asset->serial ?: '—' }}</td>
                    <td>{{ $asset->last_checkout ? \Carbon\Carbon::parse($asset->last_checkout)->format('d/m/Y H:i') : 'No registrada' }}</td>
                </tr>
            @empty
                <tr><td colspan="6" class="ssl-muted">No constan activos asignados al trabajador.</td></tr>
            @endforelse
            </tbody>
            <tfoot><tr><td colspan="5">Total de activos</td><td class="ssl-center">{{ $sslAssets->count() }}</td></tr></tfoot>
        </table>
    @endif

    @if ($canViewAccessories)
        <div class="ssl-document-section">Relación de uniformes entregados</div>
        <table class="ssl-grid" aria-label="Uniformes asignados">
            <thead><tr>
                <th style="width: 5%">#</th>
                <th style="width: 30%">Descripción / prenda</th>
                <th style="width: 20%">Categoría</th>
                <th style="width: 23%">Referencia / talla</th>
                <th style="width: 7%">Cant.</th>
                <th style="width: 15%">Fecha de entrega</th>
            </tr></thead>
            <tbody>
            @forelse ($sslUniformCheckouts as $uniform)
                <tr>
                    <td class="ssl-center">{{ $loop->iteration }}</td>
                    <td>{{ $uniform->name }}</td>
                    <td>{{ $uniform->category?->name ?? 'Sin categoría' }}</td>
                    <td>{{ $uniform->model_number ?: 'No registrada' }}</td>
                    <td class="ssl-center">1</td>
                    <td>{{ $uniform->pivot?->created_at ? \Carbon\Carbon::parse($uniform->pivot->created_at)->format('d/m/Y H:i') : 'Sin fecha' }}</td>
                </tr>
            @empty
                <tr><td colspan="6" class="ssl-muted">No constan uniformes actualmente asignados.</td></tr>
            @endforelse
            </tbody>
            <tfoot><tr><td colspan="5">Total de unidades de Uniformes</td><td class="ssl-center">{{ $sslUniformCheckouts->count() }}</td></tr></tfoot>
        </table>
    @endif

    <section class="ssl-doc-terms ssl-keep">
        <strong>Normas de uso y responsabilidad:</strong>
        <ul>
            <li>Utilizar los bienes recibidos para las actividades laborales autorizadas y seguir las indicaciones de uso.</li>
            <li>Velar por su conservación e informar oportunamente daños, deterioros, extravíos o necesidades de reposición.</li>
            <li>Observar las políticas internas y los procedimientos de entrega, mantenimiento y devolución que correspondan.</li>
        </ul>
    </section>

    <section class="ssl-keep" aria-label="Firma y huella de recibido">
        <table class="ssl-grid ssl-signature-table">
            <thead><tr>
                <th style="width: 36%">Nombre y apellido del trabajador</th>
                <th style="width: 16%">C.I.</th>
                <th style="width: 23%">Cargo</th>
                <th style="width: 25%">Firma y huella</th>
            </tr></thead>
            <tbody><tr>
                <td>{{ $worker->display_name }}</td>
                <td>{{ $worker->employee_num ?: '—' }}</td>
                <td>{{ $worker->jobtitle ?: '—' }}</td>
                <td class="ssl-sign-cell"></td>
            </tr></tbody>
        </table>
    </section>

    <section class="ssl-keep" aria-label="Observaciones de la constancia">
        <strong>Observaciones:</strong>
        <div id="ssl-observation-print" class="ssl-observation" aria-live="polite">Sin observaciones adicionales.</div>
    </section>

    <section class="ssl-signatures ssl-keep" aria-label="Responsables de entrega">
        <div>
            <strong>Quien hace entrega:</strong>
            <div class="ssl-line"></div>
            <div class="ssl-small">Nombre, cargo y firma</div>
        </div>
        <div>
            <strong>Supervisor(a) / Responsable SSL:</strong>
            <div class="ssl-line"></div>
            <div class="ssl-small">Nombre, cargo y firma</div>
        </div>
    </section>
    <p class="ssl-doc-footer">Documento generado desde Snipe-SSL. Refleja asignaciones actualmente visibles. No sustituye el historial de movimientos ni constituye constancia de recibido hasta que se firmen los espacios correspondientes. La observación añadida aquí no se almacena automáticamente.</p>
</main>
</body>
</html>
