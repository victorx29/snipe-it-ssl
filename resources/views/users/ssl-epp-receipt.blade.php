<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Constancia SSL de entrega de EPP - {{ $worker->display_name }}</title>
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
        <div class="ssl-document-title">CONSTANCIA DE ENTREGA<br>DE EPP</div>
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

    <p>Por medio de la presente se deja constancia de los registros de entrega de equipos de protección personal (EPP) al trabajador identificado. Los elementos relacionados se destinan a la prevención de riesgos laborales y deben utilizarse y conservarse siguiendo las instrucciones y procedimientos aplicables.</p>

    <div class="ssl-document-section">Relación de dotaciones de EPP registradas</div>
    <table class="ssl-grid" aria-label="Equipos de protección personal entregados">
        <thead><tr>
            <th style="width: 5%">N.º</th>
            <th style="width: 18%">Fecha de entrega</th>
            <th style="width: 37%">Descripción del EPP / referencia</th>
            <th style="width: 9%">Cant.</th>
            <th style="width: 16%">Firma de recibido</th>
            <th style="width: 15%">Huella dactilar</th>
        </tr></thead>
        <tbody>
        @forelse ($sslEppDeliveries as $epp)
            <tr>
                <td class="ssl-center">{{ $loop->iteration }}</td>
                <td>{{ $epp->pivot?->created_at ? \Carbon\Carbon::parse($epp->pivot->created_at)->format('d/m/Y H:i') : 'Sin fecha' }}</td>
                <td><strong>{{ $epp->name }}</strong>
                    @if ($epp->model_number || $epp->item_no)
                        <div style="font-size: 8pt">{{ $epp->model_number ?: $epp->item_no }}</div>
                    @endif
                    @if ($epp->category?->name)
                        <div style="font-size: 8pt">{{ $epp->category->name }}</div>
                    @endif
                </td>
                <td class="ssl-center">1</td>
                <td style="height: 31px"></td>
                <td></td>
            </tr>
        @empty
            <tr><td colspan="6" class="ssl-muted">No existen entregas de EPP registradas para este trabajador.</td></tr>
        @endforelse
        </tbody>
        <tfoot><tr><td colspan="3">Total de unidades documentadas</td><td class="ssl-center">{{ $sslEppDeliveries->count() }}</td><td colspan="2"></td></tr></tfoot>
    </table>

    <p style="font-size: 8pt">Se muestra una fila por unidad entregada. Las fechas corresponden a los registros de distribución de Snipe-IT, incluidas entregas cuyo seguimiento ya fue marcado como consumido. Esa marca no equivale a una devolución física.</p>

    <section class="ssl-doc-terms ssl-keep">
        <strong>Compromiso de uso y cuidado del EPP:</strong>
        <ul>
            <li>Utilizar el EPP según los riesgos de la actividad, las instrucciones recibidas y las medidas de prevención establecidas.</li>
            <li>Conservarlo en condiciones adecuadas e informar oportunamente deterioro, pérdida o necesidad de reemplazo.</li>
            <li>Solicitar orientación sobre colocación, mantenimiento y reposición cuando corresponda.</li>
        </ul>
        <p>La firma de esta constancia acredita la recepción de los elementos efectivamente entregados; no sustituye la inducción ni el registro de capacitación sobre su uso.</p>
    </section>

    <section class="ssl-keep" aria-label="Identificación y firma de recibido">
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

    <section class="ssl-keep" aria-label="Observaciones de la entrega">
        <strong>Observaciones:</strong>
        <div id="ssl-observation-print" class="ssl-observation" aria-live="polite">Sin observaciones adicionales.</div>
    </section>

    <section class="ssl-signatures ssl-keep" aria-label="Firmas de responsables">
        <div>
            <strong>Jefe inmediato / Supervisor(a):</strong>
            <div class="ssl-line"></div>
            <div class="ssl-small">Nombre, cargo, firma y C.I.</div>
        </div>
        <div>
            <strong>Coordinación de Seguridad y Salud Laboral:</strong>
            <div class="ssl-line"></div>
            <div class="ssl-small">Nombre, cargo, firma y C.I.</div>
        </div>
    </section>
    <p class="ssl-doc-footer">Documento generado desde Snipe-SSL. Los registros de entrega constituyen el soporte del sistema; la recepción física requiere firma o evidencia correspondiente. La observación escrita para esta impresión no se guarda automáticamente en el sistema.</p>
</main>
</body>
</html>
