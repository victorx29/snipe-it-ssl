<?php

return [
    // Opt-in: con false se conserva exactamente el correo nativo actual.
    'digest_enabled' => (bool) env('SSL_DIGEST_ENABLED', false),
    // Espera minima antes de agrupar los movimientos del mismo trabajador.
    'digest_window_minutes' => max(1, (int) env('SSL_DIGEST_WINDOW_MINUTES', 10)),
];
