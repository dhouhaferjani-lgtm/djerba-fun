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
        if (Schema::hasTable('media') && !Schema::hasTable('listing_media')) {
            // Drop existing unique constraint on uuid before renaming
            DB::statement('ALTER TABLE media DROP CONSTRAINT IF EXISTS media_uuid_unique');
            DB::statement('DROP INDEX IF EXISTS media_order_index');
            DB::statement('DROP INDEX IF EXISTS media_mediable_type_mediable_id_index');

            Schema::rename('media', 'listing_media');

            // Recreate constraints with new table name
            DB::statement('ALTER TABLE listing_media ADD CONSTRAINT listing_media_uuid_unique UNIQUE (uuid)');
            DB::statement('CREATE INDEX IF NOT EXISTS listing_media_order_index ON listing_media (\"order\")');
            DB::statement('CREATE INDEX IF NOT EXISTS listing_media_mediable_type_mediable_id_index ON listing_media (mediable_type, mediable_id)');
        }

        // Step 2: Create Spatie Media Library table
        if (!Schema::hasTable('media')) {
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
            DB::statement('ALTER TABLE listing_media DROP CONSTRAINT IF EXISTS listing_media_uuid_unique');
            DB::statement('DROP INDEX IF EXISTS listing_media_order_index');
            DB::statement('DROP INDEX IF EXISTS listing_media_mediable_type_mediable_id_index');

            Schema::rename('listing_media', 'media');

            // Recreate constraints with original table name
            DB::statement('ALTER TABLE media ADD CONSTRAINT media_uuid_unique UNIQUE (uuid)');
            DB::statement('CREATE INDEX IF NOT EXISTS media_order_index ON media (\"order\")');
            DB::statement('CREATE INDEX IF NOT EXISTS media_mediable_type_mediable_id_index ON media (mediable_type, mediable_id)');
        }
    }
};
