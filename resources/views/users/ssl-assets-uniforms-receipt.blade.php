<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Constancia SSL de Activos y Uniformes - {{ $worker->display_name }}</title>
    <style>
        @page { size: letter portrait; margin: 11mm 13mm 12mm; }
        * { box-sizing: border-box; }
        body { margin: 0; color: #182a36; font-family: Arial, Helvetica, sans-serif; font-size: 9pt; line-height: 1.35; }
        .toolbar { padding: 14px 20px; background: #edf4f8; color: #315064; margin-bottom: 16px; border-bottom: 1px solid #cbdbe3; }
        .paper { max-width: 194mm; margin: 0 auto; }
        header { display: flex; gap: 16px; align-items: center; border-bottom: 2px solid #243d4e; padding-bottom: 8px; margin-bottom: 10px; }
        .brand-logo { max-width: 43mm; max-height: 18mm; object-fit: contain; }
        .brand { flex: 1; font-size: 11pt; font-weight: 700; }
        .meta { text-align: right; color: #486071; font-size: 8.7pt; min-width: 34mm; }
        h1 { text-align: center; font-size: 13.5pt; letter-spacing: .04em; color: #1f4054; line-height: 1.25; margin: 0 0 6px; }
        .subtitle { text-align: center; color: #526572; margin-bottom: 9px; font-size: 8.5pt; }
        h2 { font-size: 10.5pt; color: #24485d; background: #eff4f7; padding: 5px 8px; margin: 11px 0 6px; border-left: 3px solid #3a6881; }
        table { width: 100%; border-collapse: collapse; table-layout: fixed; }
        .identity td { padding: 4px 6px; border-bottom: 1px solid #e1e8eb; vertical-align: top; overflow-wrap: anywhere; }
        .label { color: #526673; font-size: 8.5pt; font-weight: bold; display: block; }
        .value { font-weight: 600; }
        p { margin: 8px 0; }
        .intro { line-height: 1.55; margin: 8px 0 10px; }
        .inventory { font-size: 8.7pt; }
        .inventory th { background: #eff3f6; border: 1px solid #acbdc7; padding: 5px 5px; font-weight: 700; text-align: left; }
        .inventory td { border: 1px solid #bdcbd3; padding: 5px 5px; vertical-align: middle; overflow-wrap: anywhere; }
        .inventory tbody tr:nth-child(even) td { background: #fafcfd; }
        .inventory .count, .inventory .index { text-align: center; }
        .inventory tfoot td { background: #f1f5f7; font-weight: bold; }
        .empty { font-style: italic; color: #6b7b85; }
        .legal { font-size: 8.6pt; line-height: 1.5; }
        .notes { border: 1px solid #bdcbd3; min-height: 28px; margin: 5px 0 9px; padding: 5px; color: #6c7b83; }
        .signatures { margin-top: 8px; }
        .signatures td { width: 33.333%; vertical-align: bottom; padding: 6px 8px; }
        .signature-space { height: 29px; border-bottom: 1px solid #536977; }
        .fingerprint-space { height: 48px; width: 75px; border: 1px solid #8b9ca6; margin: 7px auto 2px; }
        .signature-label { font-size: 8.2pt; text-align: center; color: #394f5d; }
        .disclaimer { margin-top: 10px; font-size: 7.2pt; color: #51626d; border-top: 1px solid #cbd7de; padding-top: 7px; }
        .break-safe, .signatures, .notes { break-inside: avoid; page-break-inside: avoid; }
        thead { display: table-header-group; }
        tr { break-inside: avoid; page-break-inside: avoid; }
        @media print {
            .toolbar { display: none; }
            .paper { max-width: none; }
            body { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        }
        @media screen {
            body { background: #e8eef1; padding-bottom: 28px; }
            .paper { background: white; padding: 18mm 14mm; box-shadow: 0 2px 15px rgba(25, 48, 62, .13); }
        }
    </style>
</head>
<body>
<div class="toolbar" role="note">
    Documento preparado para imprimir o guardar como PDF desde el navegador (Ctrl+P o Cmd+P).
    Desactiva encabezados y pies de página del navegador para conservar el formato institucional.
</div>
<main class="paper">
    <header>
        @if (($snipeSettings?->logo_print_assets == '1') && ! empty($snipeSettings?->acceptance_pdf_logo))
            <img class="brand-logo" alt="Logotipo institucional" src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($snipeSettings->acceptance_pdf_logo) }}">
        @endif
        <div class="brand">{{ $worker->companies->pluck('name')->join(', ') ?: ($snipeSettings->site_name ?? 'Gestión SSL') }}</div>
        <div class="meta">
            <strong>DOCUMENTO DE ENTREGA</strong><br>
            Generado: {{ $printedAt->format('d/m/Y H:i') }}<br>
            Control documental SSL
        </div>
    </header>

    <h1>CONSTANCIA DE ENTREGA DE ACTIVOS Y UNIFORMES</h1>
    <div class="subtitle">Relación de asignaciones vigentes · Trabajador(a)</div>

    <table class="identity" aria-label="Identificación del trabajador">
        <tbody>
        <tr>
            <td style="width: 58%"><span class="label">Nombre y apellido</span><span class="value">{{ $worker->display_name }}</span></td>
            <td><span class="label">C.I. / N.º de empleado</span><span class="value">{{ $worker->employee_num ?: 'No registrado' }}</span></td>
        </tr>
        <tr>
            <td><span class="label">Cargo</span>{{ $worker->jobtitle ?: 'No registrado' }}</td>
            <td><span class="label">Departamento</span>{{ $worker->department?->name ?? 'No registrado' }}</td>
        </tr>
        <tr>
            <td><span class="label">Empresa(s)</span>{{ $worker->companies->pluck('name')->join(', ') ?: 'No registrada' }}</td>
            <td><span class="label">Sede / ubicación del trabajador</span>{{ $worker->location?->name ?? 'No registrada' }}</td>
        </tr>
        </tbody>
    </table>

    <p class="intro">
        Esta constancia refleja las asignaciones actuales registradas al trabajador.
        La fecha de generación no sustituye las fechas de entrega indicadas en cada movimiento.
    </p>

    @if ($canViewAssets)
        <h2>1. Activos asignados</h2>
        <table class="inventory" aria-label="Activos asignados">
            <thead><tr>
                <th style="width: 5%">#</th>
                <th style="width: 16%">Placa</th>
                <th style="width: 32%">Categoría / modelo</th>
                <th style="width: 25%">N.º de serie</th>
                <th style="width: 22%">Fecha de asignación</th>
            </tr></thead>
            <tbody>
            @forelse ($sslAssets as $asset)
                <tr>
                    <td class="index">{{ $loop->iteration }}</td>
                    <td>{{ $asset->asset_tag ?: '—' }}</td>
                    <td>{{ $asset->model?->category?->name ?? 'Sin categoría' }}<br><strong>{{ $asset->model?->name ?? $asset->name ?? 'Sin descripción' }}</strong></td>
                    <td>{{ $asset->serial ?: '—' }}</td>
                    <td>{{ $asset->last_checkout ? \Carbon\Carbon::parse($asset->last_checkout)->format('d/m/Y H:i') : 'No registrada' }}</td>
                </tr>
            @empty
                <tr><td colspan="5" class="empty">Sin activos asignados para este trabajador.</td></tr>
            @endforelse
            </tbody>
            <tfoot><tr><td colspan="4">Total de activos</td><td class="count">{{ $sslAssets->count() }}</td></tr></tfoot>
        </table>
    @endif

    @if ($canViewAccessories)
        <h2>{{ $canViewAssets ? '2.' : '1.' }} Uniformes entregados / asignados</h2>
        <table class="inventory" aria-label="Uniformes asignados">
            <thead><tr>
                <th style="width: 5%">#</th>
                <th style="width: 31%">Prenda / descripción</th>
                <th style="width: 22%">Categoría</th>
                <th style="width: 20%">Referencia / talla</th>
                <th style="width: 7%">Cant.</th>
                <th style="width: 15%">Fecha registro</th>
            </tr></thead>
            <tbody>
            @forelse ($sslUniformCheckouts as $uniform)
                <tr>
                    <td class="index">{{ $loop->iteration }}</td>
                    <td>{{ $uniform->name }}</td>
                    <td>{{ $uniform->category?->name ?? 'Sin categoría' }}</td>
                    <td>{{ $uniform->model_number ?: 'No registrada' }}</td>
                    <td class="count">1</td>
                    <td>{{ $uniform->pivot?->created_at ? \Carbon\Carbon::parse($uniform->pivot->created_at)->format('d/m/Y H:i') : 'Sin fecha' }}</td>
                </tr>
            @empty
                <tr><td colspan="6" class="empty">Sin uniformes registrados para este trabajador.</td></tr>
            @endforelse
            </tbody>
            <tfoot><tr><td colspan="5">Total de unidades de Uniformes</td><td class="count">{{ $sslUniformCheckouts->count() }}</td></tr></tfoot>
        </table>
        <p class="legal">Cada fila representa una unidad. La talla figura en la referencia cuando ha sido registrada.</p>
    @endif

    <section class="break-safe" aria-label="Condiciones de entrega">
        <h2>Constancia y compromisos de uso</h2>
        <p class="legal">
            Quien firma reconoce la revisión y recepción de los bienes efectivamente entregados.
            Se compromete a su uso laboral, cuidado y notificación oportuna de daños o pérdidas,
            de acuerdo con las políticas internas y la normativa aplicable.
        </p>
        <strong style="font-size: 9pt">Observaciones de la entrega</strong>
        <div class="notes">______________________________________________________________</div>
    </section>

    <section class="signatures break-safe" aria-label="Firmas de entrega">
        <table>
            <tr>
                <td>
                    <div class="signature-space"></div>
                    <div class="signature-label">Trabajador(a) / receptor(a)<br>Nombre y firma</div>
                </td>
                <td>
                    <div class="signature-space"></div>
                    <div class="signature-label">Responsable de la entrega<br>Nombre y firma</div>
                </td>
                <td>
                    <div class="signature-space"></div>
                    <div class="signature-label">Supervisor / responsable SSL<br>Nombre y firma</div>
                </td>
            </tr>
        </table>
        <div style="display: flex; justify-content: space-between; align-items: end; font-size: 8.5pt;">
            <span>Fecha de firma: ______ / ______ / __________</span>
            <div style="text-align: center; margin-right: 16px;">
                <div class="fingerprint-space"></div>
                Huella dactilar del trabajador(a)
            </div>
        </div>
    </section>

    <div class="disclaimer">
        Relación de asignaciones visibles al usuario autorizado; no sustituye el historial de entregas y devoluciones.
        No constituye un comprobante de recepción hasta obtener las firmas correspondientes.
    </div>
</main>
</body>
</html>
