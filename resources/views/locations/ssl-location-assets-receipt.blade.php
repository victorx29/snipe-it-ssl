<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Nota de entrega SSL - Activos por sede - {{ $location->name }}</title>
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
            {{ $location->company?->name ?: ($snipeSettings->site_name ?? 'Gestión SSL') }}
        </div>
        <div class="ssl-document-title">NOTA DE ENTREGA<br>DE ACTIVOS A SEDE</div>
        <div class="ssl-document-meta">
            <strong>Fecha de impresión:</strong> {{ $printedAt->format('d/m/Y') }}<br>
            <strong>Documento:</strong> Constancia SSL<br>
            <strong>Control:</strong> Snipe-SSL
        </div>
    </header>

    <section class="ssl-document-recipient" aria-label="Datos de sede receptora">
        <div><strong>Sede receptora:</strong> {{ $location->name }}</div>
        <div><strong>Empresa:</strong> {{ $location->company?->name ?: 'No registrada en la sede' }}</div>
        <div><strong>Responsable de sede:</strong> {{ $location->manager?->display_name ?: 'No registrado' }}</div>
        <div><strong>Dirección:</strong>
            {{ collect([$location->address, $location->address2, $location->city, $location->state, $location->country])->filter()->join(', ') ?: 'No registrada' }}
        </div>
    </section>

    <p>Por medio de la presente se relacionan los activos <strong>actualmente asignados directamente</strong>
        a la sede indicada en Snipe-SSL. Cada fila representa un activo físico registrado.
        Las fechas de asignación son las existentes en el sistema y no corresponden necesariamente
        a la fecha de impresión o de firma de esta constancia.</p>

    <div class="ssl-document-section">Relación de activos entregados a la sede</div>
    <table class="ssl-grid" aria-label="Activos asignados directamente a la sede">
        <thead><tr>
            <th style="width: 5%">N.º</th>
            <th style="width: 15%">Placa</th>
            <th style="width: 19%">Categoría</th>
            <th style="width: 24%">Equipo / modelo</th>
            <th style="width: 20%">N.º de serie</th>
            <th style="width: 17%">Fecha de asignación</th>
        </tr></thead>
        <tbody>
        @forelse ($assets as $asset)
            <tr>
                <td class="ssl-center">{{ $loop->iteration }}</td>
                <td>{{ $asset->asset_tag ?: '—' }}</td>
                <td>{{ $asset->model?->category?->name ?? 'Sin categoría' }}</td>
                <td>{{ $asset->model?->name ?? $asset->name ?? 'Sin descripción' }}</td>
                <td>{{ $asset->serial ?: '—' }}</td>
                <td>{{ $asset->last_checkout ? \Carbon\Carbon::parse($asset->last_checkout)->format('d/m/Y H:i') : 'No registrada' }}</td>
            </tr>
        @empty
            <tr><td colspan="6" class="ssl-muted">No constan activos directamente asignados a esta sede.</td></tr>
        @endforelse
        </tbody>
        <tfoot><tr><td colspan="5">Total de activos documentados</td><td class="ssl-center">{{ $assets->count() }}</td></tr></tfoot>
    </table>

    <section class="ssl-doc-terms ssl-keep">
        <strong>Compromisos de recepción y custodia:</strong>
        <ul>
            <li>Verificar los activos y sus identificadores físicos en el momento de la entrega.</li>
            <li>Destinar los bienes a las actividades autorizadas y mantener su cuidado y resguardo.</li>
            <li>Informar daños, pérdidas, movimientos y cambios de responsable mediante los procedimientos establecidos.</li>
        </ul>
        <p>Este documento refleja la asignación registrada al momento de generarlo. La firma acredita
            la recepción física únicamente cuando las partes firman la constancia.</p>
    </section>

    <section class="ssl-keep" aria-label="Observaciones de la entrega">
        <strong>Observaciones:</strong>
        <div id="ssl-observation-print" class="ssl-observation" aria-live="polite">Sin observaciones adicionales.</div>
    </section>

    <section class="ssl-signatures ssl-keep" aria-label="Firmas de responsables">
        <div>
            <strong>Responsable que entrega:</strong>
            <div class="ssl-line"></div>
            <div class="ssl-small">Nombre, cargo, C.I. y firma</div>
        </div>
        <div>
            <strong>Responsable de sede que recibe:</strong>
            <div class="ssl-line"></div>
            <div class="ssl-small">{{ $location->manager?->display_name ?: 'Nombre, cargo, C.I. y firma' }}</div>
        </div>
    </section>

    <section class="ssl-signatures ssl-keep" aria-label="Control institucional">
        <div>
            <strong>Testigo / Seguridad y Salud Laboral:</strong>
            <div class="ssl-line"></div>
            <div class="ssl-small">Nombre, cargo y firma</div>
        </div>
        <div>
            <strong>Fecha de recepción física:</strong>
            <div class="ssl-line"></div>
            <div class="ssl-small">Día / Mes / Año</div>
        </div>
    </section>

    <p class="ssl-doc-footer">Documento generado en Snipe-SSL con datos de asignación actuales.
        No incluye activos asignados a trabajadores aunque se encuentren en la sede.
        Las observaciones escritas para esta impresión no se guardan automáticamente en la base de datos.
        Guarda el PDF firmado si requieres evidencia permanente.</p>
</main>
</body>
</html>
