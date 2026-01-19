<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Navigation Groups
    |--------------------------------------------------------------------------
    */
    'nav' => [
        'sales' => 'Sales',
        'operations' => 'Operations',
        'people' => 'People',
        'catalog' => 'Catalog',
        'content' => 'Content',
        'marketing' => 'Marketing',
        'system' => 'System',
        'compliance' => 'Compliance',
        'settings' => 'Settings',
        // Vendor Panel
        'my_listings' => 'My Listings',
        'bookings' => 'Bookings',
        'feedback' => 'Feedback',
        'finance' => 'Finance',
    ],

    /*
    |--------------------------------------------------------------------------
    | Resource Labels
    |--------------------------------------------------------------------------
    */
    'resources' => [
        'blog_posts' => 'Blog Posts',
        'blog_categories' => 'Blog Categories',
        'users' => 'Users',
        'vendor_kyc' => 'Vendor KYC',
        'vendor_profile' => 'Vendor Profile',
        'vendor_profiles' => 'Vendor Profiles',
        'listings' => 'Listings',
        'bookings' => 'Bookings',
        'coupons' => 'Coupons',
        'payouts' => 'Payouts',
        'locations' => 'Locations',
        'availability_rules' => 'Availability Rules',
        'agents' => 'Agents',
        'reviews' => 'Reviews',
        'extras' => 'Extras',
        'partners' => 'Partners',
        'pages' => 'Pages',
        'travel_tips' => 'Travel Tips',
        'custom_trip_requests' => 'Custom Trip Requests',
        'data_deletion_requests' => 'Data Deletion Requests',
        'payment_gateways' => 'Payment Gateways',
    ],

    /*
    |--------------------------------------------------------------------------
    | Section Titles
    |--------------------------------------------------------------------------
    */
    'sections' => [
        'content' => 'Content',
        'metadata' => 'Metadata',
        'media' => 'Media',
        'publishing' => 'Publishing',
        'seo' => 'SEO',
        'basic_information' => 'Basic Information',
        'discount_settings' => 'Discount Settings',
        'validity_usage' => 'Validity & Usage',
        'restrictions' => 'Restrictions',
        'user_information' => 'User Information',
        'booking_information' => 'Booking Information',
        'pricing' => 'Pricing',
        'traveler_information' => 'Traveler Information',
        'cancellation' => 'Cancellation',
        'listing_information' => 'Listing Information',
        'location' => 'Location',
        'description' => 'Description',
        'pricing_capacity' => 'Pricing & Capacity',
        'moderation' => 'Moderation',
        'vendor_information' => 'Vendor Information',
        'contact_information' => 'Contact Information',
        'address' => 'Address',
        'kyc_status' => 'KYC Status',
        'payout_information' => 'Payout Information',
        'additional_information' => 'Additional Information',
        'location_information' => 'Location Information',
        'geographic_information' => 'Geographic Information',
        'map_coordinates' => 'Map Coordinates',
        'statistics' => 'Statistics',
    ],

    /*
    |--------------------------------------------------------------------------
    | Common Labels
    |--------------------------------------------------------------------------
    */
    'labels' => [
        // General
        'active' => 'Active',
        'status' => 'Status',
        'name' => 'Name',
        'description' => 'Description',
        'created_at' => 'Created At',
        'updated_at' => 'Updated At',
        'created' => 'Created',
        'type' => 'Type',
        'notes' => 'Notes',
        'reference' => 'Reference',
        'currency' => 'Currency',
        'amount' => 'Amount',

        // Blog
        'title' => 'Title',
        'slug' => 'Slug',
        'excerpt' => 'Excerpt',
        'content' => 'Content',
        'author' => 'Author',
        'category' => 'Category',
        'tags' => 'Tags',
        'featured_image' => 'Featured Image',
        'publish_date' => 'Publish Date',
        'feature_on_homepage' => 'Feature on Homepage',
        'seo_title' => 'SEO Title',
        'seo_description' => 'SEO Description',
        'image' => 'Image',
        'featured' => 'Featured',
        'views' => 'Views',
        'published' => 'Published',

        // Coupon
        'code' => 'Code',
        'discount_type' => 'Discount Type',
        'discount_value' => 'Discount Value',
        'minimum_order' => 'Minimum Order',
        'maximum_discount' => 'Maximum Discount',
        'valid_from' => 'Valid From',
        'valid_until' => 'Valid Until',
        'usage_limit' => 'Usage Limit',
        'usage_count' => 'Usage Count',
        'used' => 'Used',
        'listing_ids' => 'Listing IDs',
        'user_ids' => 'User IDs',

        // User
        'display_name' => 'Display Name',
        'email' => 'Email',
        'password' => 'Password',
        'role' => 'Role',
        'avatar_url' => 'Avatar URL',
        'email_verified_at' => 'Email Verified At',
        'verified' => 'Verified',

        // Booking
        'booking_number' => 'Booking Number',
        'booking_hash' => 'Booking #',
        'traveler' => 'Traveler',
        'traveler_details' => 'Traveler Details',
        'field' => 'Field',
        'value' => 'Value',
        'extras_addons' => 'Extras/Add-ons',
        'cancellation_reason' => 'Cancellation Reason',
        'cancelled_at' => 'Cancelled At',
        'confirmed_at' => 'Confirmed At',
        'booked_on' => 'Booked On',
        'confirmed' => 'Confirmed',
        'participant_names' => 'Participant Names',
        'linked_to_account' => 'Linked to Account',
        'link_method' => 'Link Method',
        'quantity' => 'Quantity',
        'qty' => 'Qty',
        'total_amount' => 'Total Amount',
        'from' => 'From',
        'until' => 'Until',

        // Listing
        'title_english' => 'Title (English)',
        'title_french' => 'Title (French)',
        'summary_english' => 'Summary (English)',
        'description_english' => 'Description (English)',
        'base_price' => 'Base Price (cents)',
        'min_group_size' => 'Min Group Size',
        'max_group_size' => 'Max Group Size',
        'rejection_reason' => 'Rejection Reason',
        'vendor' => 'Vendor',
        'price' => 'Price',
        'rating' => 'Rating',
        'pending_review' => 'Pending Review',

        // Vendor
        'company_name' => 'Company Name',
        'company_type' => 'Company Type',
        'tax_id' => 'Tax ID / VAT Number',
        'phone' => 'Phone Number',
        'website' => 'Website',
        'business_description' => 'Business Description',
        'street_address' => 'Street Address',
        'city' => 'City',
        'postal_code' => 'Postal Code',
        'country' => 'Country',
        'commission_tier' => 'Commission Tier',
        'payout_account_id' => 'Payout Account ID',
        'verified_at' => 'Verified At',
        'company' => 'Company',
        'tier' => 'Tier',
        'joined' => 'Joined',

        // Payout
        'payout_method' => 'Payout Method',
        'transaction_reference' => 'Transaction Reference',
        'processed_at' => 'Processed At',
        'failure_reason' => 'Failure Reason',

        // Additional Notes
        'additional_notes' => 'Additional Notes',
        'required_documents' => 'Required Documents',
        'new_commission_tier' => 'New Commission Tier',

        // Location
        'image_url' => 'Image URL',
        'address' => 'Address',
        'region' => 'Region/State',
        'timezone' => 'Timezone',
        'latitude' => 'Latitude',
        'longitude' => 'Longitude',
        'number_of_listings' => 'Number of Listings',
        'listings' => 'Listings',
    ],

    /*
    |--------------------------------------------------------------------------
    | Options / Choices
    |--------------------------------------------------------------------------
    */
    'options' => [
        // Status
        'draft' => 'Draft',
        'published' => 'Published',
        'scheduled' => 'Scheduled',
        'archived' => 'Archived',
        'pending' => 'Pending',
        'processing' => 'Processing',
        'completed' => 'Completed',
        'failed' => 'Failed',
        'rejected' => 'Rejected',

        // Discount Type
        'percentage' => 'Percentage',
        'fixed_amount' => 'Fixed Amount',

        // Company Type
        'individual' => 'Individual / Sole Proprietor',
        'company_llc' => 'Company / LLC',
        'nonprofit' => 'Non-Profit Organization',
        'government' => 'Government Entity',

        // Commission Tier
        'standard' => 'Standard (15%)',
        'silver' => 'Silver (12%)',
        'gold' => 'Gold (10%)',
        'platinum' => 'Platinum (8%)',
        'standard_label' => 'Standard',
        'silver_label' => 'Silver',
        'gold_label' => 'Gold',
        'platinum_label' => 'Platinum',

        // Payout Method
        'bank_transfer' => 'Bank Transfer',
        'paypal' => 'PayPal',

        // Service Type
        'tours' => 'Tours',
        'events' => 'Events',

        // Participant Status
        'not_required' => 'Not Required',
        'partial' => 'Partial',
        'complete' => 'Complete',

        // Link Method
        'auto' => 'Auto',
        'manual' => 'Manual',
        'claimed' => 'Claimed',
        'na' => 'N/A',

        // Documents
        'id_proof' => 'Government ID (Passport/National ID)',
        'business_license' => 'Business License',
        'tax_certificate' => 'Tax Certificate',
        'bank_statement' => 'Bank Statement',
        'insurance' => 'Liability Insurance',
        'address_proof' => 'Proof of Address',
    ],

    /*
    |--------------------------------------------------------------------------
    | Helper Texts
    |--------------------------------------------------------------------------
    */
    'helpers' => [
        'slug_auto_generated' => 'Auto-generated from title. Edit to customize.',
        'excerpt_auto_generated' => 'Short summary (auto-generated if left empty)',
        'show_on_homepage' => 'Show this post on the homepage',
        'seo_title_max' => 'Max 60 characters (defaults to post title)',
        'seo_description_max' => 'Max 160 characters (defaults to excerpt)',
        'add_tags' => 'Add tags',
        'minimum_order_helper' => 'Minimum order amount required to use this coupon',
        'maximum_discount_helper' => 'Maximum discount amount (for percentage discounts)',
        'usage_limit_helper' => 'Leave empty for unlimited uses',
        'usage_count_helper' => 'Number of times this coupon has been used',
        'listing_ids_helper' => 'Leave empty to apply to all listings. Enter listing UUIDs to restrict.',
        'user_ids_helper' => 'Leave empty to apply to all users. Enter user UUIDs to restrict.',
        'traveler_info_warning' => 'Contact information is protected. Only view when necessary for customer support.',
        'sensitive_info_warning' => 'Contains sensitive personal information - handle with care',
        'rejection_reason_helper' => 'If rejecting, provide a reason for the vendor.',
        'commission_tier_helper' => 'Commission rate charged on bookings',
        'payout_account_helper' => 'Stripe Connect account or bank account reference',
        'transaction_reference_helper' => 'Transaction reference number',
        'document_request_helper' => 'This will be sent to the vendor.',
        'rejection_shared_helper' => 'This will be shared with the vendor.',
        'enter_transaction_reference' => 'Enter the transaction reference number',
        'slug_url_friendly' => 'URL-friendly identifier (auto-generated from name)',
        'description_rich' => 'Rich description for destination landing pages',
        'image_url_helper' => 'Full URL to destination hero image (e.g., from Unsplash or uploaded to MinIO)',
        'listings_count_helper' => 'Auto-calculated based on published listings',
    ],

    /*
    |--------------------------------------------------------------------------
    | Actions
    |--------------------------------------------------------------------------
    */
    'actions' => [
        'cancel' => 'Cancel',
        'mark_no_show' => 'Mark No-Show',
        'mark_completed' => 'Mark Completed',
        'approve_publish' => 'Approve & Publish',
        'reject' => 'Reject',
        'archive' => 'Archive',
        'republish' => 'Re-publish',
        'approve_selected' => 'Approve Selected',
        'archive_selected' => 'Archive Selected',
        'verify_vendor' => 'Verify Vendor',
        'reject_kyc' => 'Reject KYC',
        'request_documents' => 'Request Documents',
        'update_commission_tier' => 'Update Commission Tier',
        'verify_selected' => 'Verify Selected',
        'approve' => 'Approve',
        'complete' => 'Complete',
        'fail' => 'Fail',
    ],

    /*
    |--------------------------------------------------------------------------
    | Modal Headings & Descriptions
    |--------------------------------------------------------------------------
    */
    'modals' => [
        'delete_coupon' => 'Delete Coupon',
        'delete_coupon_description' => 'Are you sure you want to delete this coupon? Active bookings using this coupon will not be affected.',
        'approve_listing' => 'Approve Listing',
        'approve_listing_description' => 'This will publish the listing and make it visible to travelers.',
        'reject_listing' => 'Reject Listing',
        'reject_listing_description' => 'The vendor will be notified of the rejection.',
        'verify_vendor' => 'Verify Vendor',
        'verify_vendor_description' => 'This will mark the vendor as verified and allow them to publish listings.',
        'reject_kyc' => 'Reject KYC Application',
    ],

    /*
    |--------------------------------------------------------------------------
    | Notifications
    |--------------------------------------------------------------------------
    */
    'notifications' => [
        'listing_approved' => 'Listing Approved',
        'listing_approved_body' => 'The listing has been published.',
        'listing_rejected' => 'Listing Rejected',
        'listing_rejected_body' => 'The listing has been rejected.',
        'listing_archived' => 'Listing Archived',
        'listing_republished' => 'Listing Re-published',
        'cannot_publish' => 'Cannot Publish Listing',
        'missing_fields' => 'Missing required fields: :fields',
        'vendor_verified' => 'Vendor Verified',
        'vendor_verified_body' => 'The vendor has been verified successfully.',
        'kyc_rejected' => 'KYC Rejected',
        'document_request_sent' => 'Document Request Sent',
        'document_request_sent_body' => 'The vendor has been notified to submit additional documents.',
        'commission_tier_updated' => 'Commission Tier Updated',
        'vendors_verified' => ':count vendors verified',
        'listings_approved' => ':approved listings approved',
        'listings_skipped' => ':approved listings approved, :skipped skipped (incomplete data)',
        'listings_archived' => ':count listings archived',
        'payout_approved' => 'Payout approved',
        'payout_approved_body' => 'The payout is now being processed.',
        'payout_completed' => 'Payout completed',
        'payout_completed_body' => 'The payout has been marked as completed.',
        'payout_failed' => 'Payout failed',
        'payout_failed_body' => 'The payout has been marked as failed.',
    ],

    /*
    |--------------------------------------------------------------------------
    | Validation Errors
    |--------------------------------------------------------------------------
    */
    'validation' => [
        'english_title_required' => 'English title is required',
        'english_summary_required' => 'English summary is required',
        'pricing_required' => 'Pricing information is required',
        'location_required' => 'Location is required',
    ],

    /*
    |--------------------------------------------------------------------------
    | Tooltips / Badges
    |--------------------------------------------------------------------------
    */
    'tooltips' => [
        'pending_review' => 'Pending review',
        'pending_kyc_review' => 'Pending KYC review',
        'not_verified' => 'Not verified',
        'upcoming_confirmed_bookings' => 'Upcoming confirmed bookings',
    ],

    /*
    |--------------------------------------------------------------------------
    | Filters
    |--------------------------------------------------------------------------
    */
    'filters' => [
        'pending_review' => 'Pending Review',
        'pending_kyc_review' => 'Pending KYC Review',
        'verified_only' => 'Verified Only',
        'has_listings' => 'Has Listings',
        'has_coordinates' => 'Has Coordinates',
    ],

    /*
    |--------------------------------------------------------------------------
    | Empty States
    |--------------------------------------------------------------------------
    */
    'empty_states' => [
        'no_locations' => 'No locations yet',
        'create_first_location' => 'Create your first destination/location to organize listings',
    ],
];
