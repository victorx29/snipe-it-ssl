<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('licenses', function (Blueprint $table): void {
            // Link each replacement to exactly one previous validity record.
            // NULL permits all existing and unrelated records to remain untouched.
            $table->unsignedBigInteger('ssl_previous_license_id')->nullable()
                ->unique('licenses_ssl_previous_unique');
            $table->unsignedBigInteger('ssl_renewal_root_id')->nullable()
                ->index('licenses_ssl_root_index');
            $table->date('ssl_issued_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('licenses', function (Blueprint $table): void {
            $table->dropUnique('licenses_ssl_previous_unique');
            $table->dropIndex('licenses_ssl_root_index');
            $table->dropColumn(['ssl_previous_license_id', 'ssl_renewal_root_id', 'ssl_issued_at']);
        });
    }
};
