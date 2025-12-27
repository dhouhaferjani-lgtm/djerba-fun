<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('platform_settings', function (Blueprint $table) {
            $table->string('brand_color_primary')->default('#0D642E')->after('frontend_url');
            $table->string('brand_color_accent')->default('#8BC34A')->after('brand_color_primary');
            $table->string('brand_color_cream')->default('#f5f0d1')->after('brand_color_accent');
        });
    }

    public function down(): void
    {
        Schema::table('platform_settings', function (Blueprint $table) {
            $table->dropColumn(['brand_color_primary', 'brand_color_accent', 'brand_color_cream']);
        });
    }
};
