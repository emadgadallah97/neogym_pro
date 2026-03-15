<?php

return [

    // General
    'sales' => 'Sales / Member Subscriptions',
    'view' => 'View',
    'status' => 'Status',

    // Main page
    'new_subscription_sale' => 'New member subscription sale',
    'form_hint' => 'Select branch, member and plan, then complete trainer, discounts and payment details from the tabs below.',
    'last_subscriptions' => 'Latest subscriptions',
    'total_amount' => 'Total amount',

    // Tabs
    'tab_basic' => 'Basic info',
    'tab_trainer_pt' => 'Trainer & PT sessions',
    'tab_discounts' => 'Offers & coupons',
    'tab_payment' => 'Payment & commission',

    // Basic info
    'allow_all_branches' => 'Allow attendance from any branch',
    'allow_all_branches_label' => 'Yes, allow the member to attend from any branch',
    'source' => 'Subscription source',
    'source_reception' => 'Reception',
    'source_website' => 'Website',
    'source_mobile' => 'Mobile app',
    'source_call_center' => 'Call center',
    'source_partner' => 'Partner',
    'source_other' => 'Other',

    // Trainer & PT
    'with_trainer' => 'Subscription with trainer',
    'with_trainer_label' => 'Yes, this subscription includes a main trainer',
    'main_trainer' => 'Main trainer',
    'main_trainer_hint' => 'Select the main trainer if the plan is with trainer.',
    'pt_addons_title' => 'Trainer PT sessions (Add-ons)',
    'pt_addons_hint' => 'You can add multiple PT session bundles with different trainers per subscription.',
    'trainer' => 'Trainer',
    'sessions_count' => 'Sessions count',
    'sessions_remaining' => 'Remaining sessions',
    'pt_total' => 'PT total amount',
    'add_pt_addon' => 'Add PT bundle',

    // Offers & coupons
    'offer_section' => 'Automatic offers',
    'offer_section_hint' => 'System will automatically select the best valid offer based on plan, branch and amount.',
    'gross_amount' => 'Gross amount (before discount)',
    'net_amount' => 'Net amount (after discount)',
    'coupon_hint' => 'Optional; coupon will be validated and applied when saving the subscription.',

    // Payment & commission
    'sales_employee' => 'Sales employee',
    'sales_employee_hint' => 'Optional; commission will be calculated based on this employee settings.',
    'payment_method' => 'Payment method',
    'payment_other' => 'Other method',

    // Subscription show
    'subscription_show_title' => 'Subscription details',
    'basic_info' => 'Basic information',
    'pricing' => 'Pricing & commission',
    'payments' => 'Payments',
    'amount' => 'Amount',
    'paid_at' => 'Paid at',

    // Messages
    'saved_successfully' => 'Subscription sale saved successfully.',
    'something_went_wrong' => 'Something went wrong while saving the sale. Please try again.',
    'not_implemented_yet' => 'Saving sale is not implemented yet.',
    'coming_soon_form_hint' => 'A detailed subscription sale form will be built here, linked to plans, offers, coupons, and payments.',
    'plan_requires_trainer' => 'This plan requires a Private Coach.',
    'main_trainer_required' => 'A main trainer must be selected for this plan.',
    'totals_preview_hint' => 'These values are for preview only. Final calculation is applied on save.',
    'auto_best_offer' => 'Auto (Best Offer)',
    'choose_branch_first' => 'Choose branch first',
    'members_hint' => 'Active members will be loaded after selecting the branch',
    'plans_hint' => 'Active plans will be loaded after selecting the branch',
    'with_trainer_optional_label' => 'Optional: Activate subscription with trainer',
    'plan_price_without_trainer' => 'Subscription price without trainer',
    'price_updates_by_branch_plan' => 'Price changes based on branch and plan.',
    'plan_price_with_trainer' => 'Subscription price with trainer',
    'plan_price_with_trainer_hint' => 'Appears after selecting the main trainer.',
    'no_trainers_for_branch_plan' => 'No trainers available for this plan in this branch.',
    'coach_price_not_found' => 'No price found for this plan with this trainer in this branch.',
    'branch_coaches_note' => 'Trainers',
    'choose_branch_to_load_coaches' => 'Choose a branch to view available trainers.',
    'no_coaches_in_branch' => 'No trainers linked to this branch.',
    'coaches_loaded' => 'Trainers loaded based on the selected branch.',
    'preview_only' => 'Preview Only',
    'commission_employee' => 'Sales Employee',
    'commissionbase_amount' => 'Commission Base',
    'commission_net_amount' => 'Net Amount',
    'reference' => 'Reference',
    'paid_at_hint' => 'Date and time are auto-filled with current time and can be modified if needed',

    // Current subscriptions list
    'current_subscriptions' => 'Current subscriptions',
    'search' => 'Search',
    'search_member_placeholder' => 'Search by member code or name',
    'all' => 'All',
    'actions' => 'Actions',
    'no_data' => 'No data',

    // Filters
    'has_pt_addons' => 'PT sessions (Add-ons)',
    'with_pt_addons' => 'Has',
    'without_pt_addons' => 'None',
    'date_from' => 'From',
    'date_to' => 'To',
    'apply_filters' => 'Apply',
    'clear_filters' => 'Clear',

    // Modal
    'subscription_details' => 'Subscription details',
    'loading' => 'Loading...',
    'close' => 'Close',
    'ajax_error_try_again' => 'An error occurred, please try again',

    // Columns / fields
    'member' => 'Member',
    'sessions' => 'Sessions',
    'subscription_sessions' => 'Subscription sessions',
    'pt_sessions' => 'PT sessions',
    'pt_addons_short' => 'PT',
    'remaining' => 'Remaining',
    'yes' => 'Yes',
    'no' => 'No',
    'start_date' => 'Start date',
    'end_date' => 'End date',
    'source_callcenter' => 'Call center',

    // PT after sale
    'pt_addons_after_sale_title' => 'Add PT sessions to subscription',
    'pt_addons_only_active' => 'Only available for active subscriptions',
    'pt_addons_already_exists' => 'Cannot add PT because the subscription already has PT',
    'pt_addons_total_zero' => 'Total cannot be zero',
    'pt_addons_saved' => 'PT added successfully',
    'trainer_filtered_by_branch' => 'Trainers by branch',
    'trainer_not_in_branch' => 'Trainer is not linked to this branch',

    // ✅ New restructuring keys
    'save_and_print' => 'Save & Print Invoice',
    'view_subscriptions_list' => 'View Current Subscriptions',
    'back_to_sales' => 'Back to Sales',
    'no_offer_selected' => 'No offer (choose manually)',

    // Invoice print
    'invoice' => 'Invoice',
    'invoice_number' => 'Invoice Number',
    'invoice_date' => 'Invoice Date',
    'member_info' => 'Member Information',
    'subscription_info' => 'Subscription Information',
    'invoice_footer' => 'Thank you for your subscription',
    'print' => 'Print',
    'item' => 'Item',
    'details' => 'Details',

    // Invoice summary
    'invoice_summary' => 'Invoice Summary',
    'item_plan' => 'Plan',
    'item_pt_addons' => 'PT Sessions (Trainer)',
    'subtotal_gross' => 'Subtotal (before discount)',
    'discount_offer' => 'Offer Discount',
    'discount_coupon' => 'Coupon Discount',
    'net_total' => 'Net Total',
    'total_discount' => 'Total Discount',
    'summary_hint' => 'Summary updates automatically based on plan/PT sessions/offer/coupon.',

    // Commission
    'commission_section' => 'Commission',
    'commission_base_amount' => 'Commission Base',
    'commission_amount' => 'Commission Amount',
    'commission_estimated' => 'Estimated Commission',
    'commission_value_type' => 'Commission Type',
    'commission_value' => 'Commission Value',
    'commission_base_gross' => 'Base = Gross (before discounts)',
    'commission_base_net' => 'Base = Net (after discounts)',
    'commission_type_percent' => 'Percentage %',
    'commission_type_fixed' => 'Fixed Amount',
    'commission_calculated_on_save' => 'Final commission is calculated on save based on employee settings.',

    // Pricing
    'price_plan' => 'Plan Price',
    'price_pt_addons' => 'Trainer Sessions Price',
    'offer_discount' => 'Offer Discount',
    'amount_after_offer' => 'After Offer',
    'no_offers_available' => 'No offers available',
    'best_offer' => 'Best Offer',
    'coupon_discount' => 'Coupon Discount',
    'amount_after_coupon' => 'After Coupon',

    // Payment
    'cash' => 'Cash',
    'card' => 'Card',
    'transfer' => 'Transfer',
    'instapay' => 'InstaPay',
    'ewallet' => 'E-Wallet',
    'cheque' => 'Cheque',

    // Messages
    'savedsuccessfully' => 'Subscription saved successfully.',
    'base_price_not_found' => 'No base price found for this plan in this branch.',
    'coach_not_in_branch' => 'The selected trainer is not assigned to this branch.',
    'coupon_valid' => 'Coupon is valid',
    'coupon_invalid' => 'Coupon is invalid',
    'coupon_empty' => 'Please enter coupon code first.',
    'validate_coupon' => 'Validate & Apply',
    'validating' => 'Validating...',
    'offers_list' => 'Available Offers',
    'selected_offer' => 'Selected Offer',
    'offer_list_hint' => 'Select an offer to apply or leave without an offer.',
    'session_price' => 'Session Price',
    'per_page' => 'Rows per page',
    'add_pt' => 'Add PT',

    // Status
    'status_active' => 'Active',
    'status_expired' => 'Expired',
    'status_frozen' => 'Frozen',
    'status_cancelled' => 'Cancelled',
    'status_pendingpayment' => 'Pending Payment',

    // Renewal
    'renew' => 'Renew',
    'renew_subscription' => 'Renew Subscription',
    'confirm_renewal' => 'Confirm Renewal',
    'old_end_date' => 'Old End Date',
    'base_price' => 'Base Price',
    'renewal_notes' => 'Subscription Renewal',
    'renewal_payment' => 'Subscription Renewal Payment',
    'renewedsuccessfully' => 'Subscription renewed successfully.',
    'plan_name' => 'Plan Name',
    'apply' => 'Apply',
    'notes' => 'Notes',
    'offer' => 'Offers',
    'coupon_code' => 'Coupon Code',
    'subscription_renewal' => 'Subscription Renewal',
    'new_subscription' => 'New Subscription',

];
