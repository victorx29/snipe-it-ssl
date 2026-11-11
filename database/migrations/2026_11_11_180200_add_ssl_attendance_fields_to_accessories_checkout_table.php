<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * SSL 4.2: identifiable attendance events using the existing
     * accessories_checkout relation, without a new table. NULL status on
     * all pre-existing checkouts means they remain physical deliveries.
     */
    public function up(): void
    {
        Schema::table('accessories_checkout', function (Blueprint $table): void {
            $table->string('ssl_attendance_status', 24)->nullable()->index('ssl_attendance_status_idx');
            $table->unsignedInteger('ssl_attended_minutes')->nullable();
            $table->dateTime('ssl_attendance_confirmed_at')->nullable();
            $table->unsignedInteger('ssl_attendance_confirmed_by')->nullable();
        });
    }

    public function down(): void
    {
        // Never turn attendance rows back into ordinary physical checkouts
        // by removing their discriminator while records are still present.
        if (DB::table('accessories_checkout')->whereNotNull('ssl_attendance_status')->exists()) {
            throw new \RuntimeException('No es seguro revertir la migración SSL 4.2 con asistencias existentes.');
        }

        Schema::table('accessories_checkout', function (Blueprint $table): void {
            $table->dropIndex('ssl_attendance_status_idx');
            $table->dropColumn([
                'ssl_attendance_status',
                'ssl_attended_minutes',
                'ssl_attendance_confirmed_at',
                'ssl_attendance_confirmed_by',
            ]);
        });
    }
};
