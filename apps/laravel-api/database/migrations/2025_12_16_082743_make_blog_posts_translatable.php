<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // PostgreSQL requires USING clause for type conversion
        DB::statement('ALTER TABLE blog_posts ALTER COLUMN title TYPE JSON USING jsonb_build_object(\'en\', title)');
        DB::statement('ALTER TABLE blog_posts ALTER COLUMN excerpt TYPE JSON USING CASE WHEN excerpt IS NULL THEN NULL ELSE jsonb_build_object(\'en\', excerpt) END');
        DB::statement('ALTER TABLE blog_posts ALTER COLUMN content TYPE JSON USING jsonb_build_object(\'en\', content)');
        DB::statement('ALTER TABLE blog_posts ALTER COLUMN seo_title TYPE JSON USING CASE WHEN seo_title IS NULL THEN NULL ELSE jsonb_build_object(\'en\', seo_title) END');
        DB::statement('ALTER TABLE blog_posts ALTER COLUMN seo_description TYPE JSON USING CASE WHEN seo_description IS NULL THEN NULL ELSE jsonb_build_object(\'en\', seo_description) END');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('blog_posts', function (Blueprint $table) {
            // Revert back to text columns
            $table->string('title')->change();
            $table->text('excerpt')->nullable()->change();
            $table->longText('content')->change();
            $table->string('seo_title')->nullable()->change();
            $table->text('seo_description')->nullable()->change();
        });
    }
};
