<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('availability_rules', function (Blueprint $table) {
            // JSON array of {start_time, end_time, capacity} entries.
            // When set, supersedes the legacy start_time / end_time / capacity columns.
            // Postgres-prod and sqlite-test compatible.
            $table->json('time_slots')->nullable()->after('end_time');
        });
    }

    public function down(): void
    {
        Schema::table('availability_rules', function (Blueprint $table) {
            $table->dropColumn('time_slots');
        });
    }
};
