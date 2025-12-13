<?php

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
        Schema::create('media', function (Blueprint $table) {
            $table->id();
            $table->uuid()->unique();
            $table->morphs('mediable'); // mediable_type, mediable_id
            $table->string('url');
            $table->string('thumbnail_url')->nullable();
            $table->string('alt', 200);
            $table->string('type'); // image, video
            $table->integer('order')->default(0);
            $table->timestamps();

            $table->index(['mediable_type', 'mediable_id']);
            $table->index('order');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('media');
    }
};
