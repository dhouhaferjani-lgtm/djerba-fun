<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * This migration renames the custom media table to listing_media
     * and creates a new media table for Spatie Media Library (used by CMS pages).
     */
    public function up(): void
    {
        // Step 1: Rename the custom media table to listing_media
        if (Schema::hasTable('media') && ! Schema::hasTable('listing_media')) {
            // SQLite compatibility: Drop indexes and constraints differently
            $driver = DB::getDriverName();

            if ($driver === 'sqlite') {
                // For SQLite, we need to recreate the table
                Schema::table('media', function (Blueprint $table) {
                    $table->dropUnique('media_uuid_unique');
                    $table->dropIndex('media_order_index');
                    $table->dropIndex('media_mediable_type_mediable_id_index');
                });
            } else {
                // For other databases
                DB::statement('ALTER TABLE media DROP CONSTRAINT IF EXISTS media_uuid_unique');
                DB::statement('DROP INDEX IF EXISTS media_order_index');
                DB::statement('DROP INDEX IF EXISTS media_mediable_type_mediable_id_index');
            }

            Schema::rename('media', 'listing_media');

            // Recreate constraints with new table name
            Schema::table('listing_media', function (Blueprint $table) {
                $table->unique('uuid', 'listing_media_uuid_unique');
                $table->index('order', 'listing_media_order_index');
                $table->index(['mediable_type', 'mediable_id'], 'listing_media_mediable_type_mediable_id_index');
            });
        }

        // Step 2: Create Spatie Media Library table
        if (! Schema::hasTable('media')) {
            Schema::create('media', function (Blueprint $table) {
                $table->id();

                $table->morphs('model');
                $table->uuid()->nullable()->unique();
                $table->string('collection_name');
                $table->string('name');
                $table->string('file_name');
                $table->string('mime_type')->nullable();
                $table->string('disk');
                $table->string('conversions_disk')->nullable();
                $table->unsignedBigInteger('size');
                $table->json('manipulations');
                $table->json('custom_properties');
                $table->json('generated_conversions');
                $table->json('responsive_images');
                $table->unsignedInteger('order_column')->nullable()->index();

                $table->nullableTimestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop Spatie media table
        Schema::dropIfExists('media');

        // Rename listing_media back to media
        if (Schema::hasTable('listing_media')) {
            // Drop constraints
            $driver = DB::getDriverName();

            if ($driver === 'sqlite') {
                Schema::table('listing_media', function (Blueprint $table) {
                    $table->dropUnique('listing_media_uuid_unique');
                    $table->dropIndex('listing_media_order_index');
                    $table->dropIndex('listing_media_mediable_type_mediable_id_index');
                });
            } else {
                DB::statement('ALTER TABLE listing_media DROP CONSTRAINT IF EXISTS listing_media_uuid_unique');
                DB::statement('DROP INDEX IF EXISTS listing_media_order_index');
                DB::statement('DROP INDEX IF EXISTS listing_media_mediable_type_mediable_id_index');
            }

            Schema::rename('listing_media', 'media');

            // Recreate constraints with original table name
            Schema::table('media', function (Blueprint $table) {
                $table->unique('uuid', 'media_uuid_unique');
                $table->index('order', 'media_order_index');
                $table->index(['mediable_type', 'mediable_id'], 'media_mediable_type_mediable_id_index');
            });
        }
    }
};
