<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * SSL Etapa 4.1: datos de una sesión, sin tablas nuevas ni cambios a
     * accesorios_checkout, cantidades o historiales existentes.
     */
    public function up(): void
    {
        Schema::table('accessories', function (Blueprint $table): void {
            $table->dateTime('ssl_training_starts_at')->nullable();
            $table->dateTime('ssl_training_ends_at')->nullable();
            $table->unsignedInteger('ssl_training_duration_minutes')->nullable();
            $table->string('ssl_training_status', 24)->nullable();
            $table->string('ssl_training_modality', 20)->nullable();
            $table->string('ssl_training_facilitator', 191)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('accessories', function (Blueprint $table): void {
            $table->dropColumn([
                'ssl_training_starts_at',
                'ssl_training_ends_at',
                'ssl_training_duration_minutes',
                'ssl_training_status',
                'ssl_training_modality',
                'ssl_training_facilitator',
            ]);
        });
    }
};
