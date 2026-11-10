<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('licenses', function (Blueprint $table): void {
            // New SSL permits use a single technical seat, never a user checkout.
            // Existing licenses remain legacy; no data or seats are rewritten.
            $table->boolean('ssl_is_permit')->default(false);
            $table->unsignedBigInteger('location_id')->nullable()->index();
            $table->unsignedSmallInteger('ssl_notice_value')->nullable();
            $table->string('ssl_notice_unit', 8)->nullable();
            $table->string('ssl_process_status', 20)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('licenses', function (Blueprint $table): void {
            $table->dropIndex(['location_id']);
            $table->dropColumn([
                'ssl_is_permit',
                'location_id',
                'ssl_notice_value',
                'ssl_notice_unit',
                'ssl_process_status',
            ]);
        });
    }
};
