<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Go Adventure - English Translations for Admin & Vendor Panels
    |--------------------------------------------------------------------------
    |
    | English translations for Filament admin and vendor panels.
    |
    */

    'navigation' => [
        'groups' => [
            // Admin Panel Navigation Groups
            'sales' => 'Sales',
            'operations' => 'Operations',
            'people' => 'People',
            'catalog' => 'Catalog',
            'content' => 'Content',
            'marketing' => 'Marketing',
            'system' => 'System',
            'compliance' => 'Compliance',
            'settings' => 'Settings',

            // Vendor Panel Navigation Groups
            'my_listings' => 'My Listings',
            'bookings' => 'Bookings',
            'feedback' => 'Feedback',
            'finance' => 'Finance',
            'availability' => 'Availability',
            'extras' => 'Extras & Add-ons',
        ],

        'items' => [
            // Admin Panel Navigation Items
            'users' => 'Users',
            'vendor_profiles' => 'Vendor Profiles',
            'vendor_kyc' => 'Vendor KYC',
            'bookings' => 'Bookings',
            'payouts' => 'Payouts',
            'listings' => 'Listings',
            'availability_rules' => 'Availability Rules',
            'locations' => 'Locations',
            'blog_posts' => 'Blog Posts',
            'blog_categories' => 'Blog Categories',
            'pages' => 'Pages',
            'travel_tips' => 'Travel Tips',
            'coupons' => 'Coupons',
            'custom_trip_requests' => 'Custom Trip Requests',
            'data_deletion_requests' => 'Data Deletion Requests',
            'partners' => 'API Partners',
            'payment_gateways' => 'Payment Gateways',
            'platform_settings' => 'Platform Settings',
            'agents' => 'API Agents',

            // Vendor Panel Navigation Items
            'my_bookings' => 'My Bookings',
            'my_listings' => 'My Listings',
            'extras' => 'Extras & Add-ons',
            'reviews' => 'Reviews',
        ],
    ],

    'resources' => [
        // Resource Labels (singular/plural)
        'user' => 'User',
        'users' => 'Users',
        'booking' => 'Booking',
        'bookings' => 'Bookings',
        'listing' => 'Listing',
        'listings' => 'Listings',
        'location' => 'Location',
        'locations' => 'Locations',
        'blog_post' => 'Blog Post',
        'blog_posts' => 'Blog Posts',
        'page' => 'Page',
        'pages' => 'Pages',
        'coupon' => 'Coupon',
        'coupons' => 'Coupons',
        'payout' => 'Payout',
        'payouts' => 'Payouts',
        'extra' => 'Extra',
        'extras' => 'Extras',
        'review' => 'Review',
        'reviews' => 'Reviews',
        'partner' => 'Partner',
        'partners' => 'Partners',
    ],

    'actions' => [
        // Common Actions
        'create' => 'Create',
        'save' => 'Save',
        'edit' => 'Edit',
        'delete' => 'Delete',
        'view' => 'View',
        'cancel' => 'Cancel',
        'close' => 'Close',
        'confirm' => 'Confirm',
        'submit' => 'Submit',
        'search' => 'Search',
        'filter' => 'Filter',
        'reset' => 'Reset',
        'export' => 'Export',
        'import' => 'Import',
        'refresh' => 'Refresh',
        'back' => 'Back',
        'next' => 'Next',
        'previous' => 'Previous',
        'finish' => 'Finish',

        // Listing Actions
        'approve' => 'Approve',
        'reject' => 'Reject',
        'archive' => 'Archive',
        'duplicate' => 'Duplicate',
        'publish' => 'Publish',
        'unpublish' => 'Unpublish',
        'save_draft' => 'Save Draft',
        'submit_for_review' => 'Submit for Review',
        'approve_publish' => 'Approve & Publish',
        're_publish' => 'Re-publish',

        // Booking Actions
        'mark_paid' => 'Mark as Paid',
        'mark_completed' => 'Mark Completed',
        'mark_no_show' => 'Mark No-Show',
        'partial_payment' => 'Partial Payment',
        'refund' => 'Refund',
        'contact' => 'Contact',
        'send_reminder' => 'Send Reminder',
        'resend_confirmation' => 'Resend Confirmation',

        // Status Actions
        'activate' => 'Activate',
        'deactivate' => 'Deactivate',
        'enable' => 'Enable',
        'disable' => 'Disable',
        'remove' => 'Remove',
        'add' => 'Add',
        'attach' => 'Attach',
        'detach' => 'Detach',

        // Vendor Actions
        'verify' => 'Verify',
        'reject_kyc' => 'Reject KYC',
        'create_vendor_profile' => 'Create Vendor Profile',

        // Extra Actions
        'create_from_template' => 'Create from Template',
        'add_existing_extra' => 'Add Existing Extra',
        'add_extra' => 'Add Extra',

        // Bulk Actions
        'delete_selected' => 'Delete Selected',
        'archive_selected' => 'Archive Selected',
        'approve_selected' => 'Approve Selected',
        'activate_selected' => 'Activate Selected',
        'deactivate_selected' => 'Deactivate Selected',
    ],

    'sections' => [
        // Common Sections
        'basic_information' => 'Basic Information',
        'user_information' => 'User Information',
        'contact_information' => 'Contact Information',
        'address' => 'Address',
        'settings' => 'Settings',
        'metadata' => 'Metadata',
        'media' => 'Media',
        'images' => 'Images',
        'content' => 'Content',
        'publishing' => 'Publishing',
        'seo' => 'SEO',

        // Booking Sections
        'booking_information' => 'Booking Information',
        'booking_details' => 'Booking Details',
        'traveler_information' => 'Traveler Information',
        'payment_information' => 'Payment Information',
        'cancellation' => 'Cancellation',

        // Listing Sections
        'details_highlights' => 'Details & Highlights',
        'service_details' => 'Service Details',
        'route_itinerary' => 'Route & Itinerary',
        'pricing_capacity' => 'Pricing & Capacity',
        'pricing' => 'Pricing',
        'availability' => 'Availability',
        'schedule' => 'Schedule',
        'tour_details' => 'Tour Details',
        'event_details' => 'Event Details',
        'meeting_point' => 'Meeting Point',
        'venue' => 'Venue',
        'group_size' => 'Group Size',
        'person_type_pricing' => 'Person Type Pricing',
        'booking_settings' => 'Booking Settings',
        'cancellation_policy' => 'Cancellation Policy',
        'display_settings' => 'Display Settings',

        // Extra Sections
        'category_pricing' => 'Category & Pricing',
        'quantity_settings' => 'Quantity Settings',
        'inventory_management' => 'Inventory Management',

        // Vendor Sections
        'vendor_information' => 'Vendor Information',
        'company_information' => 'Company Information',
        'kyc_status' => 'KYC Status',
        'payout_settings' => 'Payout Settings',

        // Partner Sections
        'api_configuration' => 'API Configuration',
        'permissions' => 'Permissions',
        'webhook_settings' => 'Webhook Settings',
        'rate_limiting' => 'Rate Limiting',

        // Custom Trip Sections
        'request_details' => 'Request Details',
        'trip_details' => 'Trip Details',
        'travelers' => 'Travelers',
        'interests' => 'Interests',
        'budget_style' => 'Budget & Style',
        'special_requests' => 'Special Requests',
        'special_occasions' => 'Special Occasions',
        'processing_information' => 'Processing Information',
    ],

    'fields' => [
        // Identity Fields
        'id' => 'ID',
        'title' => 'Title',
        'name' => 'Name',
        'display_name' => 'Display Name',
        'first_name' => 'First Name',
        'last_name' => 'Last Name',
        'full_name' => 'Full Name',
        'email' => 'Email',
        'phone' => 'Phone',
        'password' => 'Password',
        'password_confirmation' => 'Confirm Password',
        'avatar' => 'Avatar',
        'avatar_url' => 'Avatar URL',

        // Content Fields
        'description' => 'Description',
        'summary' => 'Summary',
        'short_description' => 'Short Description',
        'content' => 'Content',
        'excerpt' => 'Excerpt',
        'body' => 'Body',
        'notes' => 'Notes',
        'message' => 'Message',

        // Identification Fields
        'slug' => 'Slug',
        'code' => 'Code',
        'reference' => 'Reference',
        'booking_number' => 'Booking Number',

        // Status Fields
        'status' => 'Status',
        'type' => 'Type',
        'role' => 'Role',
        'active' => 'Active',
        'featured' => 'Featured',
        'published' => 'Published',
        'verified' => 'Verified',
        'required' => 'Required',
        'enabled' => 'Enabled',

        // Date/Time Fields
        'created_at' => 'Created',
        'updated_at' => 'Updated',
        'published_at' => 'Published',
        'start_date' => 'Start Date',
        'end_date' => 'End Date',
        'start_time' => 'Start Time',
        'end_time' => 'End Time',
        'date' => 'Date',
        'time' => 'Time',
        'event_date' => 'Event Date',
        'booked_on' => 'Booked On',
        'expires_at' => 'Expires At',
        'valid_from' => 'Valid From',
        'valid_until' => 'Valid Until',

        // Pricing Fields
        'price' => 'Price',
        'price_tnd' => 'Price (TND)',
        'price_eur' => 'Price (EUR)',
        'base_price' => 'Base Price',
        'amount' => 'Amount',
        'total' => 'Total',
        'subtotal' => 'Subtotal',
        'discount' => 'Discount',
        'discount_amount' => 'Discount Amount',
        'discount_percentage' => 'Discount Percentage',
        'currency' => 'Currency',
        'pricing_type' => 'Pricing Type',

        // Quantity Fields
        'quantity' => 'Quantity',
        'qty' => 'Qty',
        'guests' => 'Guests',
        'adults' => 'Adults',
        'children' => 'Children',
        'capacity' => 'Capacity',
        'min_quantity' => 'Min Quantity',
        'max_quantity' => 'Max Quantity',
        'default_quantity' => 'Default Quantity',
        'min_group_size' => 'Min Group Size',
        'max_group_size' => 'Max Group Size',

        // Duration Fields
        'duration' => 'Duration',
        'duration_value' => 'Duration Value',
        'duration_unit' => 'Duration Unit',
        'hours' => 'Hours',
        'minutes' => 'Minutes',

        // Location Fields
        'location' => 'Location',
        'address' => 'Address',
        'city' => 'City',
        'country' => 'Country',
        'region' => 'Region',
        'postal_code' => 'Postal Code',
        'latitude' => 'Latitude',
        'longitude' => 'Longitude',
        'coordinates' => 'Coordinates',

        // Company Fields
        'company_name' => 'Company Name',
        'company_type' => 'Company Type',
        'website' => 'Website',
        'website_url' => 'Website URL',
        'tax_id' => 'Tax ID',

        // Category Fields
        'category' => 'Category',
        'tags' => 'Tags',

        // Inventory Fields
        'inventory' => 'Inventory',
        'stock' => 'Stock',
        'inventory_count' => 'Stock Available',
        'track_inventory' => 'Track Inventory',
        'capacity_per_unit' => 'Capacity per Unit',

        // Relational Fields
        'vendor' => 'Vendor',
        'traveler' => 'Traveler',
        'author' => 'Author',
        'user' => 'User',
        'listing' => 'Listing',
        'listings' => 'Listings',
        'booking' => 'Booking',
        'bookings' => 'Bookings',

        // Analytics Fields
        'views' => 'Views',
        'rating' => 'Rating',
        'reviews_count' => 'Reviews Count',
        'bookings_count' => 'Bookings Count',
        'used' => 'Used',
        'used_count' => 'Used Count',

        // Days
        'days' => 'Days',
        'days_of_week' => 'Days of Week',
        'monday' => 'Monday',
        'tuesday' => 'Tuesday',
        'wednesday' => 'Wednesday',
        'thursday' => 'Thursday',
        'friday' => 'Friday',
        'saturday' => 'Saturday',
        'sunday' => 'Sunday',

        // Image Fields
        'image' => 'Image',
        'image_url' => 'Image URL',
        'featured_image' => 'Featured Image',
        'thumbnail' => 'Thumbnail',
        'gallery' => 'Gallery',

        // Other Fields
        'color' => 'Color',
        'icon' => 'Icon',
        'order' => 'Order',
        'display_order' => 'Display Order',
        'position' => 'Position',
    ],

    'statuses' => [
        // General Statuses
        'pending' => 'Pending',
        'active' => 'Active',
        'inactive' => 'Inactive',
        'completed' => 'Completed',
        'cancelled' => 'Cancelled',
        'archived' => 'Archived',
        'rejected' => 'Rejected',

        // Booking Statuses
        'pending_payment' => 'Pending Payment',
        'pending_confirmation' => 'Pending Confirmation',
        'confirmed' => 'Confirmed',
        'no_show' => 'No-Show',
        'refunded' => 'Refunded',
        'partial' => 'Partial',

        // Listing Statuses
        'draft' => 'Draft',
        'pending_review' => 'Pending Review',
        'published' => 'Published',

        // Other Statuses
        'complete' => 'Complete',
        'incomplete' => 'Incomplete',
        'auto' => 'Auto',
        'manual' => 'Manual',
        'verified' => 'Verified',
        'unverified' => 'Unverified',
        'processing' => 'Processing',
        'failed' => 'Failed',
    ],

    'empty_states' => [
        'no_records' => 'No records found',
        'no_results' => 'No results found',

        'no_bookings' => 'No bookings yet',
        'no_bookings_description' => 'When travelers book your listings, they will appear here.',

        'no_listings' => 'No listings yet',
        'no_listings_description' => 'Create your first listing to start receiving bookings.',

        'no_extras' => 'No extras attached',
        'no_extras_description' => 'Add extras like equipment rentals, meals, or insurance.',

        'no_extras_created' => 'No extras created',
        'no_extras_created_description' => 'Create extras that customers can add to their bookings.',

        'no_images' => 'No images uploaded',
        'no_images_description' => 'Add images to make your listing more attractive.',

        'no_reviews' => 'No reviews yet',
        'no_reviews_description' => 'Customer reviews will appear here.',

        'no_availability' => 'No availability configured',
        'no_availability_description' => 'Configure your availability slots to receive bookings.',
    ],

    'helpers' => [
        'required' => 'Required',
        'optional' => 'Optional',
        'required_for_publishing' => 'Required for publishing',
        'auto_generated' => 'Auto-generated from title',
        'auto_generated_from_name' => 'Auto-generated from name',
        'leave_empty_default' => 'Leave empty to use default',
        'leave_empty_unlimited' => 'Leave empty for unlimited',
        'url_friendly' => 'URL-friendly identifier',
        'short_summary' => 'Short summary',
        'max_characters' => ':count characters max',
        'select_option' => 'Select an option',
        'select_multiple' => 'Select one or more options',
        'no_options' => 'No options available',
        'search_placeholder' => 'Search...',
        'upload_image' => 'Click to upload or drag and drop',
        'supported_formats' => 'Supported formats: :formats',
        'max_file_size' => 'Max size: :size',
    ],

    'modals' => [
        'are_you_sure' => 'Are you sure?',
        'cannot_undo' => 'This action cannot be undone.',
        'confirm_delete' => 'Confirm Delete',
        'confirm_action' => 'Confirm Action',

        'delete_record' => 'Delete this record?',
        'delete_record_description' => 'This will permanently delete this record.',

        'approve_listing' => 'Approve this listing?',
        'approve_listing_description' => 'The listing will be published and visible to travelers.',

        'reject_listing' => 'Reject this listing?',
        'reject_listing_description' => 'The vendor will be notified of the rejection.',

        'archive_listing' => 'Archive this listing?',
        'archive_listing_description' => 'The listing will no longer be visible but existing bookings will be preserved.',

        'delete_availability' => 'Delete this availability rule?',
        'delete_availability_description' => 'Slots generated by this rule will also be deleted.',

        'mark_paid' => 'Mark as paid?',
        'mark_paid_description' => 'The booking will be confirmed and the customer will be notified.',

        'cancel_booking' => 'Cancel this booking?',
        'cancel_booking_description' => 'The customer will be notified of the cancellation.',

        'verify_vendor' => 'Verify this vendor?',
        'verify_vendor_description' => 'The vendor will be able to publish listings.',

        'reject_kyc' => 'Reject KYC application?',
        'reject_kyc_description' => 'The vendor will be notified of the rejection.',
    ],

    'notifications' => [
        'saved' => 'Saved successfully',
        'created' => 'Created successfully',
        'updated' => 'Updated successfully',
        'deleted' => 'Deleted successfully',
        'archived' => 'Archived successfully',
        'published' => 'Published successfully',
        'approved' => 'Approved successfully',
        'rejected' => 'Rejected',
        'completed' => 'Operation completed',
        'cancelled' => 'Cancelled',

        'error' => 'An error occurred',
        'try_again' => 'Please try again',
        'validation_error' => 'Please fix the errors below',
        'not_found' => 'Record not found',
        'unauthorized' => 'Unauthorized action',

        'booking_confirmed' => 'Booking confirmed',
        'booking_cancelled' => 'Booking cancelled',
        'payment_received' => 'Payment received',
        'refund_processed' => 'Refund processed',

        'listing_submitted' => 'Listing submitted for review',
        'listing_approved' => 'Listing approved and published',
        'listing_rejected' => 'Listing rejected',

        'vendor_verified' => 'Vendor verified',
        'vendor_rejected' => 'Vendor rejected',
    ],

    'days' => [
        'monday' => 'Monday',
        'tuesday' => 'Tuesday',
        'wednesday' => 'Wednesday',
        'thursday' => 'Thursday',
        'friday' => 'Friday',
        'saturday' => 'Saturday',
        'sunday' => 'Sunday',

        'mon' => 'Mon',
        'tue' => 'Tue',
        'wed' => 'Wed',
        'thu' => 'Thu',
        'fri' => 'Fri',
        'sat' => 'Sat',
        'sun' => 'Sun',
    ],

    'time_units' => [
        'second' => 'second',
        'seconds' => 'seconds',
        'minute' => 'minute',
        'minutes' => 'minutes',
        'hour' => 'hour',
        'hours' => 'hours',
        'day' => 'day',
        'days' => 'days',
        'week' => 'week',
        'weeks' => 'weeks',
        'month' => 'month',
        'months' => 'months',
        'year' => 'year',
        'years' => 'years',
    ],

    'filters' => [
        'all' => 'All',
        'active_only' => 'Active only',
        'inactive_only' => 'Inactive only',
        'pending_only' => 'Pending only',
        'published_only' => 'Published only',
        'draft_only' => 'Drafts only',
        'archived_only' => 'Archived only',
        'featured_only' => 'Featured only',
        'verified_only' => 'Verified only',
        'date_range' => 'Date Range',
        'from_date' => 'From',
        'to_date' => 'To',
    ],

    'pagination' => [
        'showing' => 'Showing',
        'to' => 'to',
        'of' => 'of',
        'results' => 'results',
        'per_page' => 'per page',
    ],

    'widgets' => [
        'total_bookings' => 'Total Bookings',
        'total_revenue' => 'Total Revenue',
        'pending_bookings' => 'Pending Bookings',
        'confirmed_bookings' => 'Confirmed Bookings',
        'upcoming_bookings' => 'Upcoming Bookings',
        'recent_bookings' => 'Recent Bookings',
        'popular_listings' => 'Popular Listings',
        'revenue_this_month' => 'Revenue This Month',
        'bookings_this_month' => 'Bookings This Month',
        'average_rating' => 'Average Rating',
        'total_reviews' => 'Total Reviews',
    ],

    'misc' => [
        'yes' => 'Yes',
        'no' => 'No',
        'or' => 'or',
        'and' => 'and',
        'none' => 'None',
        'n_a' => 'N/A',
        'unknown' => 'Unknown',
        'other' => 'Other',
        'loading' => 'Loading...',
        'processing' => 'Processing...',
        'please_wait' => 'Please wait...',
        'more' => 'More',
        'less' => 'Less',
        'show_more' => 'Show more',
        'show_less' => 'Show less',
        'view_all' => 'View all',
        'learn_more' => 'Learn more',
    ],
];
