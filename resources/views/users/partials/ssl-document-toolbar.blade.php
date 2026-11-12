<section class="ssl-doc-toolbar" aria-label="Controles de impresión SSL">
    <strong>Constancia institucional · Preparar impresión</strong>
    <p>El documento se genera como HTML. Escribe una observación opcional y pulsa «Imprimir / Guardar como PDF» cuando esté listo.</p>
    <label for="ssl-observation-input">Observación para esta constancia</label>
    <textarea id="ssl-observation-input" maxlength="3000" placeholder="Escribe aquí condiciones de la entrega, novedades o aclaraciones..."></textarea>
    <button id="ssl-print-button" type="button">Imprimir / Guardar como PDF</button>
    <p class="notice">Esta observación se incorpora a la impresión actual; no se guarda en la base de datos. Conserva el PDF firmado si necesitas evidencia permanente. En la impresión desactiva encabezados y pies de página del navegador.</p>
</section>
<script>
(function () {
    function bindReceiptTools() {
        var input = document.getElementById('ssl-observation-input');
        var output = document.getElementById('ssl-observation-print');
        var button = document.getElementById('ssl-print-button');
        if (!input || !output || !button) return;
        function syncObservation() {
            output.textContent = input.value.trim() || 'Sin observaciones adicionales.';
        }
        input.addEventListener('input', syncObservation);
        window.addEventListener('beforeprint', syncObservation);
        button.addEventListener('click', function () {
            syncObservation();
            window.print();
        });
        syncObservation();
    }
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', bindReceiptTools);
    } else {
        bindReceiptTools();
    }
})();
</script>
