<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('listings', function (Blueprint $table) {
            $table->foreignId('activity_type_id')
                ->nullable()
                ->after('service_type')
                ->constrained('activity_types')
                ->nullOnDelete();

            $table->index('activity_type_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('listings', function (Blueprint $table) {
            $table->dropForeign(['activity_type_id']);
            $table->dropIndex(['activity_type_id']);
            $table->dropColumn('activity_type_id');
        });
    }
};
