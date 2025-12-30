<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Performance optimizations:
     * - Add indexes on foreign keys to speed up joins
     * - Add indexes on frequently filtered columns (status, is_enabled, is_published, etc.)
     * - Add composite indexes for common query patterns
     */
    public function up(): void
    {
        // Users table indexes
        Schema::table('users', function (Blueprint $table) {
            $table->index('email', 'idx_users_email');
            $table->index('role', 'idx_users_role');
            $table->index(['email', 'role'], 'idx_users_email_role');
        });

        // Listings table indexes
        Schema::table('listings', function (Blueprint $table) {
            $table->index('vendor_id', 'idx_listings_vendor_id');
            $table->index('location_id', 'idx_listings_location_id');
            $table->index('status', 'idx_listings_status');
            $table->index('service_type', 'idx_listings_service_type');
            $table->index('slug', 'idx_listings_slug');
            $table->index('published_at', 'idx_listings_published_at');
            $table->index('rating', 'idx_listings_rating');
            $table->index('bookings_count', 'idx_listings_bookings_count');
            // Composite indexes for common queries
            $table->index(['status', 'published_at'], 'idx_listings_status_published');
            $table->index(['location_id', 'status'], 'idx_listings_location_status');
            $table->index(['service_type', 'status'], 'idx_listings_service_type_status');
        });

        // Locations table indexes
        Schema::table('locations', function (Blueprint $table) {
            $table->index('slug', 'idx_locations_slug');
            $table->index('city', 'idx_locations_city');
            $table->index('listings_count', 'idx_locations_listings_count');
            $table->index(['city', 'listings_count'], 'idx_locations_city_count');
        });

        // Bookings table indexes
        Schema::table('bookings', function (Blueprint $table) {
            $table->index('user_id', 'idx_bookings_user_id');
            $table->index('listing_id', 'idx_bookings_listing_id');
            $table->index('availability_slot_id', 'idx_bookings_slot_id');
            $table->index('status', 'idx_bookings_status');
            $table->index('booking_number', 'idx_bookings_number');
            $table->index('session_id', 'idx_bookings_session_id');
            $table->index('partner_id', 'idx_bookings_partner_id');
            $table->index('created_at', 'idx_bookings_created_at');
            $table->index('confirmed_at', 'idx_bookings_confirmed_at');
            // Composite indexes for common queries
            $table->index(['user_id', 'status'], 'idx_bookings_user_status');
            $table->index(['user_id', 'created_at'], 'idx_bookings_user_created');
            $table->index(['listing_id', 'status'], 'idx_bookings_listing_status');
            $table->index(['session_id', 'status'], 'idx_bookings_session_status');
        });

        // Booking holds table indexes
        Schema::table('booking_holds', function (Blueprint $table) {
            $table->index('user_id', 'idx_holds_user_id');
            $table->index('listing_id', 'idx_holds_listing_id');
            $table->index('availability_slot_id', 'idx_holds_slot_id');
            $table->index('session_id', 'idx_holds_session_id');
            $table->index('cart_id', 'idx_holds_cart_id');
            $table->index('expires_at', 'idx_holds_expires_at');
            $table->index('created_at', 'idx_holds_created_at');
            // Composite indexes for common queries
            $table->index(['session_id', 'expires_at'], 'idx_holds_session_expires');
            $table->index(['user_id', 'expires_at'], 'idx_holds_user_expires');
        });

        // Availability slots table indexes
        Schema::table('availability_slots', function (Blueprint $table) {
            $table->index('listing_id', 'idx_slots_listing_id');
            $table->index('date', 'idx_slots_date');
            $table->index('is_available', 'idx_slots_is_available');
            // Composite indexes for common queries
            $table->index(['listing_id', 'date'], 'idx_slots_listing_date');
            $table->index(['listing_id', 'date', 'is_available'], 'idx_slots_listing_date_available');
        });

        // Availability rules table indexes
        Schema::table('availability_rules', function (Blueprint $table) {
            $table->index('listing_id', 'idx_rules_listing_id');
            $table->index('rule_type', 'idx_rules_rule_type');
            $table->index('is_enabled', 'idx_rules_is_enabled');
            $table->index(['listing_id', 'is_enabled'], 'idx_rules_listing_enabled');
        });

        // Carts table indexes
        Schema::table('carts', function (Blueprint $table) {
            $table->index('user_id', 'idx_carts_user_id');
            $table->index('session_id', 'idx_carts_session_id');
            $table->index('status', 'idx_carts_status');
            $table->index('expires_at', 'idx_carts_expires_at');
            // Composite indexes for common queries
            $table->index(['user_id', 'status'], 'idx_carts_user_status');
            $table->index(['session_id', 'status'], 'idx_carts_session_status');
            $table->index(['status', 'expires_at'], 'idx_carts_status_expires');
        });

        // Cart items table indexes
        Schema::table('cart_items', function (Blueprint $table) {
            $table->index('cart_id', 'idx_cart_items_cart_id');
            $table->index('listing_id', 'idx_cart_items_listing_id');
            $table->index('hold_id', 'idx_cart_items_hold_id');
        });

        // Payment intents table indexes
        Schema::table('payment_intents', function (Blueprint $table) {
            $table->index('booking_id', 'idx_payment_intents_booking_id');
            $table->index('cart_id', 'idx_payment_intents_cart_id');
            $table->index('status', 'idx_payment_intents_status');
            $table->index('payment_method', 'idx_payment_intents_method');
            $table->index('gateway_payment_id', 'idx_payment_intents_gateway_id');
            $table->index('created_at', 'idx_payment_intents_created_at');
            // Composite indexes
            $table->index(['booking_id', 'status'], 'idx_payment_intents_booking_status');
        });

        // Reviews table indexes
        Schema::table('reviews', function (Blueprint $table) {
            $table->index('listing_id', 'idx_reviews_listing_id');
            $table->index('user_id', 'idx_reviews_user_id');
            $table->index('booking_id', 'idx_reviews_booking_id');
            $table->index('status', 'idx_reviews_status');
            $table->index('rating', 'idx_reviews_rating');
            $table->index('created_at', 'idx_reviews_created_at');
            // Composite indexes
            $table->index(['listing_id', 'status'], 'idx_reviews_listing_status');
            $table->index(['listing_id', 'rating'], 'idx_reviews_listing_rating');
        });

        // Partners table indexes
        Schema::table('partners', function (Blueprint $table) {
            $table->index('is_active', 'idx_partners_is_active');
            $table->index('created_at', 'idx_partners_created_at');
        });

        // Partner API keys table indexes
        Schema::table('partner_api_keys', function (Blueprint $table) {
            $table->index('partner_id', 'idx_partner_keys_partner_id');
            $table->index('is_active', 'idx_partner_keys_is_active');
            $table->index('last_used_at', 'idx_partner_keys_last_used');
        });

        // Coupons table indexes
        Schema::table('coupons', function (Blueprint $table) {
            $table->index('code', 'idx_coupons_code');
            $table->index('is_active', 'idx_coupons_is_active');
            $table->index('valid_from', 'idx_coupons_valid_from');
            $table->index('valid_until', 'idx_coupons_valid_until');
            // Composite indexes
            $table->index(['code', 'is_active'], 'idx_coupons_code_active');
        });

        // Media table indexes (if using morphMany for listings)
        Schema::table('media', function (Blueprint $table) {
            $table->index(['model_type', 'model_id'], 'idx_media_model');
            $table->index('collection_name', 'idx_media_collection');
        });

        // Listing FAQs table indexes
        Schema::table('listing_faqs', function (Blueprint $table) {
            $table->index('listing_id', 'idx_faqs_listing_id');
            $table->index('order', 'idx_faqs_order');
        });

        // Booking participants table indexes
        Schema::table('booking_participants', function (Blueprint $table) {
            $table->index('booking_id', 'idx_participants_booking_id');
            $table->index('email', 'idx_participants_email');
        });

        // Traveler profiles table indexes
        Schema::table('traveler_profiles', function (Blueprint $table) {
            $table->index('user_id', 'idx_traveler_profiles_user_id');
        });

        // Vendor profiles table indexes
        Schema::table('vendor_profiles', function (Blueprint $table) {
            $table->index('user_id', 'idx_vendor_profiles_user_id');
            $table->index('kyc_status', 'idx_vendor_profiles_kyc_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop indexes in reverse order
        Schema::table('vendor_profiles', function (Blueprint $table) {
            $table->dropIndex('idx_vendor_profiles_user_id');
            $table->dropIndex('idx_vendor_profiles_kyc_status');
        });

        Schema::table('traveler_profiles', function (Blueprint $table) {
            $table->dropIndex('idx_traveler_profiles_user_id');
        });

        Schema::table('booking_participants', function (Blueprint $table) {
            $table->dropIndex('idx_participants_booking_id');
            $table->dropIndex('idx_participants_email');
        });

        Schema::table('listing_faqs', function (Blueprint $table) {
            $table->dropIndex('idx_faqs_listing_id');
            $table->dropIndex('idx_faqs_order');
        });

        Schema::table('media', function (Blueprint $table) {
            $table->dropIndex('idx_media_model');
            $table->dropIndex('idx_media_collection');
        });

        Schema::table('coupons', function (Blueprint $table) {
            $table->dropIndex('idx_coupons_code');
            $table->dropIndex('idx_coupons_is_active');
            $table->dropIndex('idx_coupons_valid_from');
            $table->dropIndex('idx_coupons_valid_until');
            $table->dropIndex('idx_coupons_code_active');
        });

        Schema::table('partner_api_keys', function (Blueprint $table) {
            $table->dropIndex('idx_partner_keys_partner_id');
            $table->dropIndex('idx_partner_keys_is_active');
            $table->dropIndex('idx_partner_keys_last_used');
        });

        Schema::table('partners', function (Blueprint $table) {
            $table->dropIndex('idx_partners_is_active');
            $table->dropIndex('idx_partners_created_at');
        });

        Schema::table('reviews', function (Blueprint $table) {
            $table->dropIndex('idx_reviews_listing_id');
            $table->dropIndex('idx_reviews_user_id');
            $table->dropIndex('idx_reviews_booking_id');
            $table->dropIndex('idx_reviews_status');
            $table->dropIndex('idx_reviews_rating');
            $table->dropIndex('idx_reviews_created_at');
            $table->dropIndex('idx_reviews_listing_status');
            $table->dropIndex('idx_reviews_listing_rating');
        });

        Schema::table('payment_intents', function (Blueprint $table) {
            $table->dropIndex('idx_payment_intents_booking_id');
            $table->dropIndex('idx_payment_intents_cart_id');
            $table->dropIndex('idx_payment_intents_status');
            $table->dropIndex('idx_payment_intents_method');
            $table->dropIndex('idx_payment_intents_gateway_id');
            $table->dropIndex('idx_payment_intents_created_at');
            $table->dropIndex('idx_payment_intents_booking_status');
        });

        Schema::table('cart_items', function (Blueprint $table) {
            $table->dropIndex('idx_cart_items_cart_id');
            $table->dropIndex('idx_cart_items_listing_id');
            $table->dropIndex('idx_cart_items_hold_id');
        });

        Schema::table('carts', function (Blueprint $table) {
            $table->dropIndex('idx_carts_user_id');
            $table->dropIndex('idx_carts_session_id');
            $table->dropIndex('idx_carts_status');
            $table->dropIndex('idx_carts_expires_at');
            $table->dropIndex('idx_carts_user_status');
            $table->dropIndex('idx_carts_session_status');
            $table->dropIndex('idx_carts_status_expires');
        });

        Schema::table('availability_rules', function (Blueprint $table) {
            $table->dropIndex('idx_rules_listing_id');
            $table->dropIndex('idx_rules_rule_type');
            $table->dropIndex('idx_rules_is_enabled');
            $table->dropIndex('idx_rules_listing_enabled');
        });

        Schema::table('availability_slots', function (Blueprint $table) {
            $table->dropIndex('idx_slots_listing_id');
            $table->dropIndex('idx_slots_date');
            $table->dropIndex('idx_slots_is_available');
            $table->dropIndex('idx_slots_listing_date');
            $table->dropIndex('idx_slots_listing_date_available');
        });

        Schema::table('booking_holds', function (Blueprint $table) {
            $table->dropIndex('idx_holds_user_id');
            $table->dropIndex('idx_holds_listing_id');
            $table->dropIndex('idx_holds_slot_id');
            $table->dropIndex('idx_holds_session_id');
            $table->dropIndex('idx_holds_cart_id');
            $table->dropIndex('idx_holds_expires_at');
            $table->dropIndex('idx_holds_created_at');
            $table->dropIndex('idx_holds_session_expires');
            $table->dropIndex('idx_holds_user_expires');
        });

        Schema::table('bookings', function (Blueprint $table) {
            $table->dropIndex('idx_bookings_user_id');
            $table->dropIndex('idx_bookings_listing_id');
            $table->dropIndex('idx_bookings_slot_id');
            $table->dropIndex('idx_bookings_status');
            $table->dropIndex('idx_bookings_number');
            $table->dropIndex('idx_bookings_session_id');
            $table->dropIndex('idx_bookings_partner_id');
            $table->dropIndex('idx_bookings_created_at');
            $table->dropIndex('idx_bookings_confirmed_at');
            $table->dropIndex('idx_bookings_user_status');
            $table->dropIndex('idx_bookings_user_created');
            $table->dropIndex('idx_bookings_listing_status');
            $table->dropIndex('idx_bookings_session_status');
        });

        Schema::table('locations', function (Blueprint $table) {
            $table->dropIndex('idx_locations_slug');
            $table->dropIndex('idx_locations_city');
            $table->dropIndex('idx_locations_listings_count');
            $table->dropIndex('idx_locations_city_count');
        });

        Schema::table('listings', function (Blueprint $table) {
            $table->dropIndex('idx_listings_vendor_id');
            $table->dropIndex('idx_listings_location_id');
            $table->dropIndex('idx_listings_status');
            $table->dropIndex('idx_listings_service_type');
            $table->dropIndex('idx_listings_slug');
            $table->dropIndex('idx_listings_published_at');
            $table->dropIndex('idx_listings_rating');
            $table->dropIndex('idx_listings_bookings_count');
            $table->dropIndex('idx_listings_status_published');
            $table->dropIndex('idx_listings_location_status');
            $table->dropIndex('idx_listings_service_type_status');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('idx_users_email');
            $table->dropIndex('idx_users_role');
            $table->dropIndex('idx_users_email_role');
        });
    }
};
