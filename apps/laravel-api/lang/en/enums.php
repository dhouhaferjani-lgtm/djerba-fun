<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Enum Translations - English
    |--------------------------------------------------------------------------
    |
    | English labels for all enums used in the application.
    |
    */

    // Booking Status
    'booking_status' => [
        'pending_payment' => 'Pending Payment',
        'pending_confirmation' => 'Pending Confirmation',
        'confirmed' => 'Confirmed',
        'cancelled' => 'Cancelled',
        'completed' => 'Completed',
        'no_show' => 'No-Show',
        'refund_requested' => 'Refund Requested',
        'refunded' => 'Refunded',
    ],

    // Listing Status
    'listing_status' => [
        'draft' => 'Draft',
        'pending_review' => 'Pending Review',
        'published' => 'Published',
        'active' => 'Active',
        'inactive' => 'Inactive',
        'archived' => 'Archived',
        'rejected' => 'Rejected',
    ],

    // Service Type
    'service_type' => [
        'tour' => 'Tour',
        'event' => 'Event',
        'activity' => 'Activity',
    ],

    // User Role
    'user_role' => [
        'admin' => 'Administrator',
        'vendor' => 'Vendor',
        'traveler' => 'Traveler',
        'agent' => 'Agent',
    ],

    // Extra Category
    'extra_category' => [
        'equipment' => 'Equipment',
        'meal' => 'Meal',
        'insurance' => 'Insurance',
        'upgrade' => 'Upgrade',
        'merchandise' => 'Merchandise',
        'transport' => 'Transport',
        'accessibility' => 'Accessibility',
        'other' => 'Other',
    ],

    // Extra Pricing Type
    'pricing_type' => [
        'per_person' => 'Per Person',
        'per_booking' => 'Per Booking',
        'per_unit' => 'Per Unit',
        'per_person_type' => 'Per Person Type',
    ],

    // Difficulty Level
    'difficulty_level' => [
        'easy' => 'Easy',
        'moderate' => 'Moderate',
        'challenging' => 'Challenging',
        'expert' => 'Expert',
    ],

    // KYC Status
    'kyc_status' => [
        'pending' => 'Pending',
        'submitted' => 'Submitted',
        'verified' => 'Verified',
        'rejected' => 'Rejected',
    ],

    // Payout Status
    'payout_status' => [
        'pending' => 'Pending',
        'processing' => 'Processing',
        'completed' => 'Completed',
        'failed' => 'Failed',
    ],

    // Availability Rule Type
    'availability_rule_type' => [
        'weekly' => 'Weekly',
        'daily' => 'Daily',
        'specific_dates' => 'Specific Dates',
        'custom' => 'Custom',
    ],

    // Discount Type
    'discount_type' => [
        'percentage' => 'Percentage',
        'fixed_amount' => 'Fixed Amount',
    ],

    // Payment Status
    'payment_status' => [
        'pending' => 'Pending',
        'processing' => 'Processing',
        'succeeded' => 'Succeeded',
        'completed' => 'Completed',
        'failed' => 'Failed',
        'refunded' => 'Refunded',
        'partially_refunded' => 'Partially Refunded',
    ],

    // Payment Method
    'payment_method' => [
        'card' => 'Credit Card',
        'bank_transfer' => 'Bank Transfer',
        'cash' => 'Cash',
        'wallet' => 'Wallet',
    ],

    // Company Type
    'company_type' => [
        'individual' => 'Individual',
        'company' => 'Company',
        'association' => 'Association',
    ],

    // Review Status
    'review_status' => [
        'pending' => 'Pending',
        'approved' => 'Approved',
        'rejected' => 'Rejected',
    ],

    // Blog Status
    'blog_status' => [
        'draft' => 'Draft',
        'published' => 'Published',
        'scheduled' => 'Scheduled',
        'archived' => 'Archived',
    ],
];
