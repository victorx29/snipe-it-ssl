<?php

namespace App\Services;

use App\Models\Accessory;
use App\Models\Asset;
use App\Models\Consumable;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Registra avisos meramente informativos, sin sustituir aceptaciones ni EULA.
 * La tabla es un outbox historico: se persiste antes de suprimir el correo
 * individual; ante cualquier error se conserva la via nativa de Snipe-IT.
 */
class SslMovementDigestRecorder
{
    public function capture(object $event, string $movement, mixed $acceptance = null): bool
    {
        try {
            return $this->captureSafely($event, $movement, $acceptance);
        } catch (Throwable $e) {
            // Nunca interrumpir un movimiento de inventario por errores de avisos.
            Log::warning('SSL digest: captura fallida, se conserva el correo individual.', [
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    private function captureSafely(object $event, string $movement, mixed $acceptance): bool
    {
        if (! config('ssl_notifications.digest_enabled', false)) {
            return false;
        }

        $item = $event->checkoutable ?? null;
        $worker = $event->checkedOutTo ?? null;

        if (! $worker instanceof User || ! $worker->email || ! $item ||
            ! in_array($movement, ['checkout', 'checkin'], true)) {
            return false;
        }

        $itemType = match (true) {
            $item instanceof Consumable => 'consumable',
            $item instanceof Accessory => 'accessory',
            $item instanceof Asset => 'asset',
            default => null,
        };

        if ($itemType === null || ($movement === 'checkin' && $itemType === 'consumable')) {
            return false;
        }

        // Las sesiones SSL/GH/TI no son inventario; no entran al digest.
        if ($item instanceof Accessory && in_array($item->category?->name, [
            'CAPACITACION_SSL', 'CAPACITACION_GH', 'CAPACITACION_TI',
        ], true)) {
            return false;
        }

        if ($movement === 'checkout') {
            // Estos mensajes son individualmente necesarios para aceptar
            // condiciones o capturar firmas; NUNCA consolidarlos.
            if ($acceptance !== null || ($event->signInPlace ?? false) ||
                $item->requireAcceptance() || filled($item->getEula())) {
                return false;
            }
        }

        $reference = match ($itemType) {
            'asset' => trim(($item->asset_tag ?: 'Sin placa').($item->serial ? ' / Serie: '.$item->serial : '')),
            'accessory' => (string) ($item->model_number ?: 'Sin talla o referencia'),
            'consumable' => (string) ($item->model_number ?: ($item->item_no ?: 'Sin referencia')),
        };

        $name = match ($itemType) {
            'asset' => (string) ($item->name ?: ($item->model?->name ?? 'Activo')),
            default => (string) $item->name,
        };
        $qty = $movement === 'checkin'
            ? 1
            : max(1, (int) ($event->quantity ?? $item->checkout_qty ?? 1));

        $actor = $movement === 'checkout'
            ? ($event->checkedOutBy ?? null)
            : ($event->checkedInBy ?? null);

        // Si el movimiento se registro con fecha original distinta de hoy,
        // conservarla cuando Snipe-IT la expone en el evento/modelo.
        $originalDate = $movement === 'checkin'
            ? ($event->action_date ?? null)
            : ($item->last_checkout ?? null);
        $occurredAt = $originalDate
            ? \Illuminate\Support\Carbon::parse($originalDate)
            : now();

        try {
            DB::table('ssl_notification_movements')->insert([
                'recipient_user_id' => $worker->id,
                'recipient_name' => (string) $worker->display_name,
                'employee_num' => $worker->employee_num ?: null,
                'jobtitle' => $worker->jobtitle ?: null,
                'work_location' => $worker->location?->name,
                'movement' => $movement,
                'item_type' => $itemType,
                'item_id' => $item->id,
                'item_name' => $this->truncate($name),
                'item_reference' => $this->truncate($reference),
                'quantity' => $qty,
                'company_name' => $item->company?->name,
                'recorded_by' => $actor instanceof User ? $actor->display_name : null,
                'note' => $event->note ?? null,
                'occurred_at' => $occurredAt,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return true;
        } catch (Throwable $e) {
            Log::warning('SSL digest: no se pudo guardar el movimiento; se conserva el correo individual.', [
                'recipient_user_id' => $worker->id,
                'item_type' => $itemType,
                'item_id' => $item->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }
    private function truncate(string $value): string
    {
        if (function_exists('mb_substr')) {
            return mb_substr($value, 0, 255);
        }

        if (function_exists('iconv_substr')) {
            return (string) iconv_substr($value, 0, 255, 'UTF-8');
        }

        return substr($value, 0, 255);
    }
}
