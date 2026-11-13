<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ssl_notification_movements', function (Blueprint $table): void {
            $table->id();
            // No FK en cascada: preservar la evidencia aun si se elimina el usuario.
            $table->unsignedBigInteger('recipient_user_id')->index();
            $table->string('recipient_name');
            $table->string('employee_num', 100)->nullable();
            $table->string('jobtitle')->nullable();
            $table->string('work_location')->nullable();
            $table->string('movement', 16); // checkout | checkin
            $table->string('item_type', 24); // asset | accessory | consumable
            $table->unsignedBigInteger('item_id');
            $table->string('item_name');
            $table->string('item_reference')->nullable();
            $table->unsignedInteger('quantity')->default(1);
            $table->string('company_name')->nullable();
            $table->string('recorded_by')->nullable();
            $table->text('note')->nullable();
            $table->timestamp('occurred_at');
            $table->timestamp('sent_at')->nullable();
            $table->uuid('sent_batch_token')->nullable();
            $table->uuid('claim_token')->nullable();
            $table->timestamp('claimed_at')->nullable();
            $table->unsignedInteger('attempts')->default(0);
            $table->text('last_error')->nullable();
            $table->timestamps();

            $table->index(['sent_at', 'claim_token', 'occurred_at'], 'ssl_movements_pending_idx');
            $table->index(['recipient_user_id', 'sent_at', 'occurred_at'], 'ssl_movements_recipient_idx');
        });
    }

    public function down(): void
    {
        // Es deliberado: evitar perdida accidental del historial documental.
        // No ejecutar rollback. Desactivar mediante SSL_DIGEST_ENABLED=false.
        throw new \RuntimeException('El rollback de ssl_notification_movements no esta permitido para preservar el historial.');
    }
};
