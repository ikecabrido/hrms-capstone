<?php

/**
 * Holiday Feature Configuration
 * Centralized configuration for the holiday management system
 */

return [
    'holiday' => [
        // API Configuration
        'api' => [
            'provider' => 'nager.date',
            'base_url' => 'https://date.nager.at/api/v3',
            'country_code' => 'PH',
            'timeout' => 10, // seconds
        ],

        // Database Tables
        'tables' => [
            'holidays' => 'ta_holidays',
            'sync_log' => 'ta_holiday_sync_log',
            'attendance' => 'ta_attendance',
            'leave_requests' => 'ta_leave_requests',
            'leave_balances' => 'ta_leave_balances',
        ],

        // Features
        'features' => [
            'auto_sync' => true,           // Auto-sync on page load
            'recurring_support' => true,    // Support recurring holidays
            'calendar_integration' => true, // Show on calendar
            'dashboard_widget' => true,     // Show on dashboard
            'skip_attendance' => true,      // Skip time-in on holidays
            'leave_integration' => true,    // Use in leave calculations
            'absence_integration' => true,  // Mark as holiday not absent
        ],

        // Display Settings
        'display' => [
            'upcoming_days' => 30,  // Show upcoming holidays for next X days
            'widget_items' => 5,    // Show top X holidays in widget
            'colors' => [
                'national' => '#e74c3c',   // Red
                'regional' => '#f39c12',   // Orange
                'optional' => '#3498db',   // Blue
                'special' => '#9b59b6'     // Purple
            ],
        ],

        // Sync Settings
        'sync' => [
            'auto_sync_years' => [0, 1],  // Sync current year (0) and next year (1)
            'clear_old_holidays' => true, // Clear old non-recurring holidays before sync
            'batch_size' => 100,          // Batch insert size
        ],

        // API Endpoints
        'endpoints' => [
            'base' => 'app/api/holiday_api.php',
            'actions' => [
                'get_all' => 'Get all holidays',
                'get_upcoming' => 'Get upcoming holidays',
                'get_range' => 'Get holidays by date range',
                'is_holiday' => 'Check if date is holiday',
                'create' => 'Create new holiday',
                'update' => 'Update holiday',
                'delete' => 'Delete holiday',
                'sync' => 'Sync from API',
                'sync_info' => 'Get sync information',
            ]
        ],

        // Attendance Settings
        'attendance' => [
            'mark_as' => 'HOLIDAY',           // Mark holiday attendance as
            'skip_time_in' => true,           // Don't require time-in on holidays
            'skip_absence' => true,           // Don't mark as absent on holidays
            'auto_record' => true,            // Automatically record as holiday
        ],

        // Leave Settings
        'leave' => [
            'exclude_from_balance' => true,   // Don't deduct from leave balance
            'prevent_overlap' => false,       // Warn but don't prevent overlapping leave
            'show_preview' => true,           // Show leave calculation preview
            'calculate_excluding_holidays' => true,
            'calculate_excluding_weekends' => true,
        ],

        // Cache Settings
        'cache' => [
            'enabled' => true,
            'ttl' => 3600,              // 1 hour
            'key_prefix' => 'ta_holiday_',
        ],
    ]
];
