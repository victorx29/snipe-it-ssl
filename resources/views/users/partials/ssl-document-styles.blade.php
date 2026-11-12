<style>
    @page { size: letter portrait; margin: 13mm 13mm 12mm; }
    * { box-sizing: border-box; }
    html, body { margin: 0; }
    body { font-family: Arial, Helvetica, sans-serif; font-size: 9pt; line-height: 1.44; color: #171717; }
    .ssl-doc-paper { width: 100%; margin: 0 auto; }
    .ssl-doc-toolbar { padding: 16px max(18px, calc((100vw - 206mm) / 2)); background: #eaf1f5; border-bottom: 1px solid #bdccd4; margin-bottom: 14px; }
    .ssl-doc-toolbar strong { font-size: 12pt; display: block; margin-bottom: 7px; }
    .ssl-doc-toolbar p { margin: 4px 0 8px; color: #344a57; font-size: 9pt; }
    .ssl-doc-toolbar label { display: block; font-size: 9pt; font-weight: bold; margin-top: 8px; margin-bottom: 5px; }
    .ssl-doc-toolbar textarea { display: block; width: 100%; min-height: 68px; font: 10pt Arial, sans-serif; padding: 9px; resize: vertical; border: 1px solid #8396a2; border-radius: 4px; background: #fff; }
    .ssl-doc-toolbar button { background: #09668d; border: 0; border-radius: 4px; color: #fff; padding: 10px 18px; margin-top: 10px; cursor: pointer; font-weight: 700; font-size: 10pt; }
    .ssl-doc-toolbar button:hover, .ssl-doc-toolbar button:focus { background: #064967; }
    .ssl-doc-toolbar .notice { color: #536772; font-size: 8pt; padding: 5px 0 0; }
    .ssl-document-header { display: table; width: 100%; border-bottom: 2px solid #111; padding-bottom: 9px; margin-bottom: 14px; }
    .ssl-document-header > div { display: table-cell; vertical-align: middle; }
    .ssl-document-brand { width: 27%; font-size: 9pt; font-weight: bold; }
    .ssl-document-brand img { display: block; max-height: 15mm; max-width: 43mm; object-fit: contain; margin-bottom: 3px; }
    .ssl-document-title { width: 48%; font-size: 12pt; font-family: Georgia, 'Times New Roman', serif; font-weight: 700; text-align: center; text-transform: uppercase; }
    .ssl-document-meta { width: 25%; font-size: 8pt; text-align: right; }
    .ssl-document-body p { margin: 8px 0; text-align: justify; }
    .ssl-document-recipient { margin-bottom: 8px; }
    .ssl-document-recipient div { margin: 3px 0; }
    .ssl-document-recipient strong { display: inline-block; min-width: 75px; }
    .ssl-document-section { margin: 14px 0 8px; font-size: 10pt; font-weight: bold; }
    .ssl-grid { border-collapse: collapse; width: 100%; margin: 8px 0 12px; table-layout: fixed; }
    .ssl-grid th, .ssl-grid td { border: 1px solid #222; padding: 5px 6px; overflow-wrap: anywhere; vertical-align: middle; }
    .ssl-grid th { font-weight: bold; text-align: center; background: #f0f2f3; }
    .ssl-grid .ssl-center { text-align: center; }
    .ssl-grid .ssl-right { text-align: right; }
    .ssl-grid .ssl-muted { color: #5f656a; font-style: italic; }
    .ssl-grid tfoot td { background: #f4f5f6; font-weight: bold; }
    .ssl-doc-terms { font-size: 8.5pt; margin: 8px 0 12px; }
    .ssl-doc-terms ul { padding-left: 19px; margin: 5px 0 8px; }
    .ssl-doc-terms li { margin-bottom: 3px; }
    .ssl-signature-table th, .ssl-signature-table td { text-align: center; }
    .ssl-signature-table .ssl-sign-cell { height: 38px; }
    .ssl-observation { border: 1px solid #333; margin-top: 5px; padding: 8px 10px; min-height: 54px; white-space: pre-wrap; overflow-wrap: anywhere; }
    .ssl-signatures { display: table; width: 100%; margin-top: 16px; table-layout: fixed; }
    .ssl-signatures > div { display: table-cell; vertical-align: bottom; padding-right: 20px; width: 50%; }
    .ssl-signatures .ssl-line { margin-top: 34px; border-bottom: 1px solid #111; height: 1px; }
    .ssl-signatures .ssl-small { font-size: 8pt; margin-top: 4px; }
    .ssl-fingerprint { width: 66px; height: 54px; border: 1px solid #333; margin: 7px 0 2px; }
    .ssl-doc-footer { border-top: 1px solid #aaa; color: #4b4b4b; font-size: 7.4pt; margin-top: 17px; padding-top: 5px; }
    .ssl-nowrap { white-space: nowrap; }
    .ssl-keep { break-inside: avoid; page-break-inside: avoid; }
    .ssl-grid thead { display: table-header-group; }
    .ssl-grid tr { break-inside: avoid; page-break-inside: avoid; }
    @media screen {
        body { background: #f1f3f4; padding-bottom: 28px; }
        .ssl-doc-paper { max-width: 216mm; padding: 15mm 13mm; box-shadow: 0 2px 15px rgba(0,0,0,.15); background: white; }
    }
    @media print {
        body { color: #111; background: white; print-color-adjust: exact; -webkit-print-color-adjust: exact; }
        .ssl-doc-toolbar { display: none !important; }
        .ssl-doc-paper { max-width: none; padding: 0; box-shadow: none; }
        .ssl-keep { break-inside: avoid; page-break-inside: avoid; }
    }
</style>
