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
        Schema::create('custom_trip_requests', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('reference', 20)->unique();
            $table->string('status', 20)->default('pending');

            // Trip Details
            $table->date('travel_start_date');
            $table->date('travel_end_date');
            $table->boolean('dates_flexible')->default(false);
            $table->integer('adults')->default(1);
            $table->integer('children')->default(0);
            $table->integer('duration_days');

            // Preferences
            $table->json('interests');
            $table->integer('budget_per_person');
            $table->string('budget_currency', 3)->default('TND');
            $table->string('accommodation_style', 20)->nullable();
            $table->string('travel_pace', 20)->nullable();
            $table->json('special_occasions')->nullable();
            $table->text('special_requests')->nullable();

            // Contact
            $table->string('contact_name', 255);
            $table->string('contact_email', 255);
            $table->string('contact_phone', 50);
            $table->string('contact_whatsapp', 50)->nullable();
            $table->string('contact_country', 2);
            $table->string('preferred_contact_method', 20);
            $table->boolean('newsletter_consent')->default(false);

            // Metadata
            $table->string('locale', 5)->default('en');
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->foreignId('assigned_agent_id')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            // Indexes
            $table->index('status');
            $table->index('contact_email');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('custom_trip_requests');
    }
};
