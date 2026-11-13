<?php

namespace App\Console\Commands;

use App\Mail\SslMovementDigestMail;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Throwable;

/**
 * Emite un correo por usuario con los movimientos que ya superaron la ventana.
 * Predeterminado: previsualizacion via MAILER log (nunca entrega real).
 * --send habilita la entrega; requiere mailer real y digest_enabled=true.
 */
class SendSslMovementDigests extends Command
{
    protected $signature = 'ssl:send-digests
        {--send : Enviar realmente y marcar los movimientos como notificados}
        {--minutes= : Antiguedad minima del ultimo movimiento (por defecto, config SSL)}
        {--limit=100 : Maximo de trabajadores por ejecucion}
        {--items=100 : Maximo de movimientos por trabajador}';

    protected $description = 'Agrupa movimientos SSL por trabajador. Por defecto solo previsualiza en el mailer log.';

    public function handle(): int
    {
        $send = (bool) $this->option('send');
        $minutes = $this->option('minutes') === null
            ? (int) config('ssl_notifications.digest_window_minutes', 10)
            : (int) $this->option('minutes');
        $minutes = max(0, $minutes);
        $recipientLimit = min(500, max(1, (int) $this->option('limit')));
        $itemLimit = min(500, max(1, (int) $this->option('items')));
        $cutoff = now()->subMinutes($minutes);
        $expiredClaim = now()->subMinutes(30);

        if ($send && ! config('ssl_notifications.digest_enabled', false)) {
            $this->error('SSL_DIGEST_ENABLED debe ser true antes de realizar envios.');
            return self::FAILURE;
        }

        $transport = (string) config('mail.default', 'log');
        if ($send && ! in_array($transport, ['smtp', 'sendmail', 'ses', 'mailgun', 'postmark', 'resend'], true)) {
            $this->error('El transporte de correo ('.$transport.') no esta en la lista de envios reales permitidos. Configure SMTP antes de --send.');
            return self::FAILURE;
        }

        $baseQuery = DB::table('ssl_notification_movements')
            ->whereNull('sent_at')
            ->where('occurred_at', '<=', $cutoff)
            ->where(function ($q) use ($expiredClaim): void {
                $q->whereNull('claim_token')
                    ->orWhere('claimed_at', '<', $expiredClaim);
            });

        $recipientIds = (clone $baseQuery)
            ->select('recipient_user_id')
            ->distinct()
            ->orderBy('recipient_user_id')
            ->limit($recipientLimit)
            ->pluck('recipient_user_id');

        $processed = 0;
        $failures = 0;

        foreach ($recipientIds as $recipientId) {
            $worker = User::find($recipientId);
            // Nunca enviar al email historico si el trabajador fue eliminado
            // o su direccion actual ya no esta disponible.
            if (! $worker || ! filter_var($worker->email, FILTER_VALIDATE_EMAIL)) {
                $this->warn('Trabajador #'.$recipientId.' sin email actual; movimientos conservados pendientes.');
                continue;
            }

            if (! $send) {
                $rows = (clone $baseQuery)
                    ->where('recipient_user_id', $recipientId)
                    ->orderBy('id')
                    ->limit($itemLimit)
                    ->get();
                if ($rows->isEmpty()) {
                    continue;
                }
                try {
                    // Forzar el transporte LOG aunque el sistema tenga SMTP.
                    // No se marca enviado ni se toma un bloqueo de entrega.
                    Mail::mailer('log')->to($worker->email)->send(
                        new SslMovementDigestMail($worker, $rows, 'PREVIA-'.(string) $recipientId)
                    );
                    $this->line('Previsualizacion en log: trabajador #'.$recipientId.', movimientos: '.$rows->count());
                    $processed++;
                } catch (Throwable $e) {
                    $failures++;
                    $this->error('Error en vista previa para trabajador #'.$recipientId.': '.$e->getMessage());
                }
                continue;
            }

            // Claim transaccional: dos ejecuciones simultaneas no seleccionan
            // los mismos movimientos. Reintento de claims caidos a los 30 min.
            $token = (string) Str::uuid();
            $rows = DB::transaction(function () use ($recipientId, $cutoff, $expiredClaim, $itemLimit, $token) {
                $claimedRows = DB::table('ssl_notification_movements')
                    ->where('recipient_user_id', $recipientId)
                    ->whereNull('sent_at')
                    ->where('occurred_at', '<=', $cutoff)
                    ->where(function ($q) use ($expiredClaim): void {
                        $q->whereNull('claim_token')
                            ->orWhere('claimed_at', '<', $expiredClaim);
                    })
                    ->orderBy('id')
                    ->limit($itemLimit)
                    ->lockForUpdate()
                    ->get();

                if ($claimedRows->isNotEmpty()) {
                    DB::table('ssl_notification_movements')
                        ->whereIn('id', $claimedRows->pluck('id'))
                        ->update([
                            'claim_token' => $token,
                            'claimed_at' => now(),
                            'attempts' => DB::raw('attempts + 1'),
                            'updated_at' => now(),
                        ]);
                }

                return $claimedRows;
            });

            if ($rows->isEmpty()) {
                continue;
            }

            try {
                Mail::to($worker->email)->send(new SslMovementDigestMail($worker, $rows, $token));
                DB::table('ssl_notification_movements')
                    ->where('claim_token', $token)
                    ->whereIn('id', $rows->pluck('id'))
                    ->update([
                        'sent_at' => now(),
                        'sent_batch_token' => $token,
                        'claim_token' => null,
                        'claimed_at' => null,
                        'last_error' => null,
                        'updated_at' => now(),
                    ]);
                $this->info('Resumen enviado: trabajador #'.$recipientId.', movimientos: '.$rows->count());
                $processed++;
            } catch (Throwable $e) {
                $failures++;
                DB::table('ssl_notification_movements')
                    ->where('claim_token', $token)
                    ->whereIn('id', $rows->pluck('id'))
                    ->update([
                        'claim_token' => null,
                        'claimed_at' => null,
                        'last_error' => Str::limit($e->getMessage(), 1900),
                        'updated_at' => now(),
                    ]);
                Log::warning('SSL digest: fallo al emitir resumen.', [
                    'recipient_user_id' => $recipientId,
                    'batch_reference' => $token,
                    'error' => $e->getMessage(),
                ]);
                $this->error('No fue posible enviar el resumen al trabajador #'.$recipientId.'.');
            }
        }

        $this->line(($send ? 'Envios' : 'Previsualizaciones').': '.$processed.' | Fallos: '.$failures);
        return $failures > 0 ? self::FAILURE : self::SUCCESS;
    }
}
