<?php

return [
    'attendances' => 'Attendances',
    'kiosk_page' => 'Quick Attendance (Kiosk)',
    'kiosk_hint' => 'Scan barcode then press Enter.',
    'global_scan_hint' => 'You can scan from any page inside the system.',
    'branch_hint' => 'Current user branch',
    'user_branch_missing' => 'Please set the current user branch first.',
    'member_code' => 'Member code',
    'member' => 'Member',
    'date' => 'Date',
    'time' => 'Time',
    'method' => 'Method',
    'base' => 'Base',
    'pt' => 'PT',
    'status' => 'Status',
    'actions' => 'Actions',
    'active' => 'Active',
    'cancelled' => 'Cancelled',

    'date_from' => 'Date from',
    'date_to' => 'Date to',
    'filter' => 'Filter',

    'deduct_pt' => 'Deduct PT',
    'deduct_pt_hint' => 'Deduct PT session if available',
    'manual_checkin' => 'Manual check-in',
    'checkin' => 'Check-in',
    'processing' => 'Processing...',
    'ajax_error' => 'Unexpected error, please try again.',

    'toast_ok' => 'Success',
    'toast_fail' => 'Failed',
    'toast_hint_close' => 'Click to close',

    'add_guest' => 'Add guest',
    'guest_name' => 'Guest name (optional)',
    'guest_phone' => 'Guest phone (optional)',
    'save_guest' => 'Save guest',

    'cancel_attendance' => 'Cancel attendance',
    'cancel_pt' => 'Cancel PT only',

    'member_code_required' => 'Member code is required.',
    'member_not_found' => 'Member not found.',
    'already_checked_in_today' => 'Member is already checked-in today.',

    'member_has_no_subscriptions' => 'This member has no subscriptions.',
    'subscription_status_not_active' => 'No subscription with Active status for this member.',
    'subscription_out_of_date' => 'There is an Active subscription but it is out of date range.',
    'subscription_sessions_finished' => 'No remaining sessions in subscription (Sessions Remaining = 0).',
    'subscription_branch_not_allowed' => 'This subscription is not allowed for the current branch.',
    'no_active_subscription' => 'No active subscription allows attendance.',

    'plan_not_found' => 'Plan not found.',
    'day_not_allowed' => 'Today is not allowed by plan allowed days.',

    'scan_success' => 'Attendance saved successfully.',
    'scan_success_without_pt' => 'Attendance saved (PT not deducted due to no PT remaining).',
    'something_went_wrong' => 'Something went wrong.',

    'attendance_not_found' => 'Attendance not found.',
    'already_cancelled' => 'Already cancelled.',
    'cannot_edit_cancelled' => 'Cannot edit a cancelled record.',
    'cancelled_success' => 'Attendance cancelled and sessions refunded.',

    'pt_not_deducted' => 'PT was not deducted for this record.',
    'pt_addon_not_found' => 'PT addon not found.',
    'pt_cancelled_success' => 'PT deduction cancelled and session refunded.',

    'guests_not_allowed' => 'Guests are not allowed for this plan.',
    'guest_day_not_allowed' => 'Guests are not allowed today for this plan.',
    'guest_people_limit_reached' => 'Guest count limit reached for this visit.',
    'guest_times_limit_reached' => 'Guest usage times limit reached for this plan.',
    'guest_added_success' => 'Guest added successfully.',
];
