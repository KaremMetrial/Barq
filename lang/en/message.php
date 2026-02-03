<?php


return [
    // Common
    'page_not_found' => 'Page not found.',
    'record_not_found' => 'Record not found.',
    'method_not_allowed' => 'Method not allowed.',
    'validation_failed' => 'Validation failed.',
    'unauthorized_access' => 'Unauthorized access.',
    'access_forbidden' => 'Access forbidden.',
    'access_denied' => 'Access denied.',
    'rate_limit_exceeded' => 'Too many requests, please try again later.',
    'database_error' => 'A database error occurred.',
    'unexpected_error' => 'An unexpected error occurred. Please try again later.',
    'success' => 'Success.',

    // OTP
    'otp.not_found' => 'OTP not found.',
    'otp.expired' => 'OTP expired.',
    'otp.invalid' => 'OTP invalid.',
    'otp.sent' => 'OTP sent successfully.',
    'otp.verified' => 'OTP verified successfully.',

    // Auth
    'auth' => [
        'register' => 'Register successfully.',
        'login' => 'Login successfully.',
        'logout' => 'Logout successfully.',
        'password_reset' => 'Password reset successfully.',
    ],

    // Working Day
    'working_day.created' => 'Working day has been created successfully.',
    'working_day.updated' => 'Working day has been updated successfully.',
    'working_day.index' => 'Working days retrieved successfully.',

    'store' => [
        'discount_banner_percentage' => 'Discounts up to :discount%🔥',
        'discount_banner_fixed' => 'Discounts worth :amount🔥',
    ],

    'store_available_for_delivery' => 'Store is available for delivery.',
    'store_cannot_deliver_to_address' => 'Store cannot deliver to this address.',
    'store_is_closed' => 'Store is currently closed.',
    'some_products_unavailable' => 'Some products in the cart are unavailable.',
    'some_products_out_of_stock' => 'Some products are out of stock',

    'review_count' => ':count reviews',
    'review_count_above_1000' => '1,000+ reviews',

    // Vendor
    'vendor_no_store' => 'Vendor has no store.',
    'old_password_incorrect' => 'The old password is incorrect.',

    // Promotion Validation
    'promotion_not_active' => 'Promotion is not active.',
    'promotion_not_started' => 'Promotion has not started yet.',
    'promotion_expired' => 'Promotion has expired.',
    'promotion_usage_limit_reached' => 'Promotion usage limit reached.',
    'promotion_user_usage_limit_reached' => 'User promotion usage limit reached.',
    'promotion_not_available_country' => 'Promotion is not available in this country.',
    'promotion_not_available_city' => 'Promotion is not available in this city.',
    'promotion_not_available_zone' => 'Promotion is not available in this zone.',

    // Promotion Type Labels
    'promotion_types' => [
        'delivery' => [
            'free_delivery' => [
                'label' => 'Free Delivery',
                'description' => 'Free delivery for eligible orders',
            ],
            'discount_delivery' => [
                'label' => 'Discount Delivery',
                'description' => 'Percentage discount on delivery fees',
            ],
            'fixed_delivery' => [
                'label' => 'Fixed Delivery',
                'description' => 'Fixed delivery price for eligible orders',
            ],
        ],
        'product' => [
            'fixed_price' => [
                'label' => 'Fixed Price',
                'description' => 'Fixed price for selected products',
            ],
            'percentage_discount' => [
                'label' => 'Percentage Discount',
                'description' => 'Percentage discount on selected products',
            ],
            'first_order' => [
                'label' => 'First Order',
                'description' => 'Special offer for first-time customers',
            ],
            'bundle' => [
                'label' => 'Bundle',
                'description' => 'Buy one get one free or bundle offers',
            ],
            'buy_one_get_one' => [
                'label' => 'Buy One Get One',
                'description' => 'Buy one get one free offer',
            ],
        ],
    ],
    // Authentication
    'credentials_incorrect' => 'The provided credentials are incorrect.',

    // Store & Courier Validation
    'latitude_longitude_not_in_zone' => 'The provided latitude and longitude are not within the specified zone.',
    'delivery_company_section_required' => 'Please create a section of type "delivery_company" before creating a delivery store.',
    'phone_not_validated_otp' => 'The phone number is not validated with OTP.',

    // Order Validation
    'invalid_status_transition' => 'Invalid status transition. Current status: :current. Allowed transitions: :allowed.',

    // Cart Validation
    'different_stores_in_cart' => 'Cannot add products from different stores to the same cart.',
    'shift_time_required' => 'Start and end time are required for working non-flexible days.',

    // Generic Errors
    'not_found' => 'Not found.',
    'unauthorized' => 'Unauthorized.',
    'internal_server_error' => 'Internal server error.',
    'authentication_required' => 'Authentication required.',
    'access_denied_or_not_found' => 'Access denied or record not found.',

    // Module Specific Errors
    'cart_not_found' => 'Cart not found.',
    'promotion_not_found' => 'Promotion not found.',
    'promotion_not_applicable' => 'Promotion not applicable.',

    'courier_not_found' => 'Courier not found.',
    'shift_schedule_error' => 'Failed to retrieve shift schedule.',
    'location_update_failed' => 'Failed to update location.',
    'assignment_not_found' => 'Assignment not found or access denied.',
    'failed_to_update_status' => 'Failed to update order status.',

    'loyalty_redemption_failed' => 'Failed to redeem points.',

    'message_not_found' => 'Message not found.',
    'failed_to_mark_message_read' => 'Failed to mark message as read.',
    'channel_socket_required' => 'Channel name and socket ID are required.',
    'pusher_auth_failed' => 'Failed to authenticate with Pusher.',
    'typing_indicator_failed' => 'Failed to send typing indicator.',
];
