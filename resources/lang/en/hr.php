<?php
return [
    'title'                    => 'Human Resources',
    'programs_label'           => 'HR Programs',
    'open'                     => 'Open',

    // Stats
    'total_employees'          => 'Total Employees',
    'present_today'            => 'Present Today',
    'pending_advances'         => 'Pending Advances',
    'month_payrolls'           => 'Month Payrolls',

    // Cards
    'attendances'              => 'Attendances',
    'attendances_desc'         => 'Recording and monitoring employee attendance',
    'advances'                 => 'Advances',
    'advances_desc'            => 'Managing advance requests and tracking monthly installments',
    'deductions'               => 'Deductions and Penalties',
    'deductions_desc'          => 'Recording deductions and penalties and applying them to salaries',
    'overtime_allowances'      => 'Overtime and Allowances',
    'overtime_allowances_desc' => 'Managing overtime hours, bonuses, and allowances',
    'payrolls'                 => 'Payroll Sheet and Payment',
    'payrolls_desc'            => 'Preparing, approving, and paying monthly payroll sheets',
    'devices'                  => 'Attendance Devices',
    'devices_desc'             => 'Managing fingerprint devices and synchronizing attendance data',
    'reports'                  => 'HR Reports',
    'reports_desc'             => 'Comprehensive analytical reports for attendance, salaries, and employees',
    'employees'                => 'Employees',
    'employees_desc'           => 'Managing employee data, jobs, and branches',
    // ── Devices ─────────────────────────────────────────────────
    'devices_list'               => 'Attendance Devices List',
    'add_device'                 => 'Add Device',
    'edit_device'                => 'Edit Attendance Device',
    'no_devices'                 => 'No registered devices',
    'device_name'                => 'Device Name',
    'device_name_placeholder'    => 'Example: Fingerprint Device - Main Branch',
    'total_devices'              => 'Total Devices',
    'active_devices'             => 'Active Devices',
    'inactive_devices'           => 'Inactive Devices',

    // ── Fields ──────────────────────────────────────────────────
    'branch'                     => 'Branch',
    'select_branch'              => '-- Select Branch --',
    'serial_number'              => 'Serial Number',
    'ip_address'                 => 'IP Address',
    'status'                     => 'Status',
    'active'                     => 'Active',
    'inactive'                   => 'Inactive',
    'notes'                      => 'Notes',
    'notes_placeholder'          => 'Any additional notes...',
    'optional'                   => 'Optional',

    // ── Actions ─────────────────────────────────────────────────
    'actions'                    => 'Actions',
    'edit'                       => 'Edit',
    'delete'                     => 'Delete',
    'save'                       => 'Save',
    'update'                     => 'Update',
    'cancel'                     => 'Cancel',

    // ── Validation ──────────────────────────────────────────────
    'validation_branch_required' => 'Branch is required',
    'validation_name_required'   => 'Device name is required',
    'validation_serial_required' => 'Serial number is required',
    'validation_serial_unique'   => 'Serial number is already in use',
    'validation_ip_invalid'      => 'Invalid IP address',

    // ── Messages ────────────────────────────────────────────────
    'device_created_success'     => 'Device added successfully',
    'device_updated_success'     => 'Device data updated successfully',
    'device_deleted_success'     => 'Device deleted successfully',
    'device_has_logs_error'      => 'Cannot delete device, there are fingerprint logs associated with it',
    'error_occurred'             => 'An error occurred, please try again',

    // ── Confirm Dialog ───────────────────────────────────────────
    'delete_confirm_title'       => 'Confirm Deletion',
    'delete_confirm_msg'         => 'Do you want to delete ',
    'yes_delete'                 => 'Yes, delete',
    // ── Attendance ─────────────────────────────────────────────
    'attendance'                  => 'Attendance',
    'attendance_list'             => 'Attendance Record',
    'manual_entry'                => 'Manual Entry',
    'process_logs'                => 'Process Raw Logs',
    'raw_logs'                    => 'Raw Logs',
    'run_processing'              => 'Run Processing',

    // ── Filters / View ─────────────────────────────────────────
    'filter'                      => 'Search',
    'view_mode'                   => 'View Mode',
    'monthly'                     => 'Monthly',
    'daily'                       => 'Daily',
    'month'                       => 'Month',
    'date'                        => 'Date',
    'all_employees'               => 'All Employees',
    'employee'                    => 'Employee',
    'select_employee'             => '-- Select Employee --',

    // ── Table Columns ──────────────────────────────────────────
    'shift'                       => 'Shift',
    'check_in'                    => 'Check In',
    'check_out'                   => 'Check Out',
    'total_hours'                 => 'Total Hours',
    'source'                      => 'Source',

    // ── Status ────────────────────────────────────────────────
    'present'                     => 'Present',
    'absent'                      => 'Absent',
    'late'                        => 'Late',
    'halfday'                     => 'Half Day',
    'leave'                       => 'Leave',
    'fullday'                     => 'Full Day',

    // ── Sources ───────────────────────────────────────────────
    'source_manual'               => 'Manual',
    'source_device'               => 'Device',
    'source_system'               => 'System',

    // ── Devices / Logs ────────────────────────────────────────
    'device'                      => 'Device',
    'all_devices'                 => 'All Devices',
    'punch_time'                  => 'Punch Time',
    'punch_type'                  => 'Punch Type',
    'in'                          => 'In',
    'out'                         => 'Out',
    'unknown'                     => 'Unknown',

    // ── Shifts ────────────────────────────────────────────────
    'shifts'                      => 'Shifts',
    'shift_name'                  => 'Shift Name',
    'no_shift'                    => 'No Shift',
    'start_time'                  => 'Start Time',
    'end_time'                    => 'End Time',
    'grace_minutes'               => 'Grace Minutes',
    'working_days'                => 'Working Days',

    // ── General UI ────────────────────────────────────────────
    'back'                        => 'Back',
    'print'                       => 'Print',
    'export_excel'                => 'Export Excel',
    'advance_id'                  => 'Advance ID',



    // ── Messages ──────────────────────────────────────────────
    'attendance_saved_success'    => 'Attendance saved successfully',
    'attendance_updated_success'  => 'Attendance updated successfully',
    'attendance_deleted_success'  => 'Record deleted successfully',
    'logs_processed_success'      => 'Logs processed successfully',
    'log_received_success'        => 'Log received successfully',

    // ── Errors ────────────────────────────────────────────────
    'employee_not_in_branch'      => 'This employee does not belong to this branch (Primary Branch)',
    'device_not_found'            => 'Device not found',
    // Shifts cards
    'shifts_desc'          => 'Managing shifts (work times, working days, and grace period)',

    'employee_shifts'      => 'Employee Shifts',
    'employee_shifts_desc' => 'Assigning a shift to each employee based on branch and validity period',
    // Night shifts support
    'overnight'              => 'Night',
    'shift_duration'         => 'Duration',
    'hours'                  => 'Hour',
    'night_shift_hint'       => 'For night shifts: choose an end time earlier than the start time (it will be considered the next day).',

    // Validation messages
    'shift_time_invalid'       => 'Invalid end time',
    'shift_duration_too_long'  => 'Shift duration should not be 24 hours or more',
    'min_half_exceeds_shift'   => 'Half day limit exceeds shift duration',
    'min_full_exceeds_shift'   => 'Full day limit exceeds shift duration',
    // Shifts CRUD
    'shifts_list'            => 'Shifts List',
    'add_shift'              => 'Add Shift',
    'edit_shift'             => 'Edit Shift',
    'work_time'              => 'Work Time',
    'min_hours'              => 'Minimum (Hours)',
    'min_half_hours'         => 'Half Day Limit (Hours)',
    'min_full_hours'         => 'Full Day Limit (Hours)',
    'create_date'            => 'Creation Date',

    // Days (if not present)
    'sun' => 'Sunday',
    'mon' => 'Monday',
    'tue' => 'Tuesday',
    'wed' => 'Wednesday',
    'thu' => 'Thursday',
    'fri' => 'Friday',
    'sat' => 'Saturday',

    // Messages
    'shift_saved_success'    => 'Shift saved successfully',
    'shift_updated_success'  => 'Shift updated successfully',
    'shift_deleted_success'  => 'Shift deleted successfully',
    'night_shift_logs_hint' => 'Note: The system displays logs for today + the next day to support night shifts.',

    'employee_shifts_list'               => 'Employee Shifts List',
    'add_employee_shift'                 => 'Add Employee Shift',
    'edit_employee_shift'                => 'Edit Employee Shift',
    'select_branch_first'                => 'Please select branch first',

    'select_shift'                       => 'Select Shift',
    'start_date'                         => 'From',
    'end_date'                           => 'To',

    'employee_shift_saved_success'       => 'Employee shift saved successfully',
    'employee_shift_updated_success'     => 'Employee shift updated successfully',
    'employee_shift_deleted_success'     => 'Employee shift deleted successfully',

    'employee_shift_overlap'             => 'There is a conflict with another active shift for the same employee and branch within the same period',
    'end_date_after_or_equal_start'      => 'End date must be after or equal to start date',
    // Advances
    'advances_list'                  => 'Advances List',
    'add_advance'                    => 'Add Advance',
    'edit_advance'                   => 'Edit Advance',

    'request_date'                   => 'Request Date',
    'start_month'                    => 'Deduction Start',
    'total_amount'                   => 'Total',
    'monthly_installment'            => 'Monthly Installment',
    'installments_count'             => 'Installments Count',
    'paid_amount'                    => 'Paid',
    'remaining_amount'               => 'Remaining',

    'status_pending'                 => 'Pending',
    'status_approved'                => 'Approved',
    'status_rejected'                => 'Rejected',
    'status_completed'               => 'Completed',

    'advance_saved_success'          => 'Advance saved successfully',
    'advance_updated_success'        => 'Advance updated successfully',
    'advance_deleted_success'        => 'Advance deleted successfully',

    'advance_approved_success'       => 'Advance approved and installments generated',
    'advance_rejected_success'       => 'Advance rejected',

    'approve_advance'                => 'Approve',
    'reject_advance'                 => 'Reject',

    'approve_confirm_title'          => 'Confirm Approval',
    'approve_confirm_msg'            => 'The advance will be approved and installments generated. Do you want to continue?',
    'yes_approve'                    => 'Yes, Approve',

    'reject_confirm_title'           => 'Confirm Rejection',
    'reject_confirm_msg'             => 'Do you want to reject this advance?',
    'yes_reject'                     => 'Yes, Reject',

    'advance_has_active'             => 'A new advance cannot be created before completing the current one',
    'advance_has_paid_cannot_delete' => 'An advance with paid installments or linked to payrolls cannot be deleted',
    'advance_cannot_edit_status'     => 'An advance in this status cannot be modified',
    'advance_cannot_approve'         => 'This advance cannot be approved',
    'advance_cannot_reject'          => 'This advance cannot be rejected',

    'start_month_format'             => 'Invalid deduction start month format',

    'advance_total_less_than_paid'           => 'The total advance cannot be less than what has already been deducted',
    'advance_installments_less_than_paid'    => 'The number of installments cannot be less than the number of paid installments',
    'advance_remaining_count_invalid'        => 'Invalid number of remaining installments',
    'advance_reschedule_hint'               => 'When modifying an approved advance, only unpaid installments will be rescheduled.',

    // Installments UI
    'installments'                   => 'Installments',
    'view_installments'              => 'View Installments',
    'installment_month'              => 'Month',
    'installment_amount'             => 'Installment Value',
    'is_paid'                        => 'Paid?',
    'paid'                           => 'Paid',
    'not_paid'                       => 'Not Paid',
    'paid_date'                      => 'Paid Date',
    'payroll_id'                     => 'Payroll',
    // Deductions
    'deductions_list'            => 'Deductions and Penalties List',
    'add_deduction'              => 'Add Deduction/Penalty',
    'edit_deduction'             => 'Edit Deduction/Penalty',

    'type'                       => 'Type',
    'type_deduction'             => 'Deduction',
    'type_penalty'               => 'Penalty',
    'reason'                     => 'Reason',
    'amount'                     => 'Value',
    'applied_month'              => 'Applied Month',

    'status_applied'             => 'Applied',
    'all_status'                 => 'All Statuses',

    'approve'                    => 'Approve',
    'deduction_saved_success'    => 'Deduction/Penalty saved successfully',
    'deduction_updated_success'  => 'Deduction/Penalty updated successfully',
    'deduction_deleted_success'  => 'Deduction/Penalty deleted successfully',
    'deduction_approved_success' => 'Deduction/Penalty approved successfully',

    'deduction_cannot_approve'   => 'This record cannot be approved',
    'deduction_applied_locked'   => 'This record has been applied to a payroll and cannot be modified/deleted',

    'deduction_approve_confirm_msg' => 'Do you want to approve this deduction/penalty?',
    // Common
    'record_applied_locked'   => 'This record has been applied to a payroll and cannot be modified/deleted.',
    'cannot_approve'          => 'This record cannot be approved.',
    'all_types'               => 'All Types',



    // Overtime
    'overtime'                => 'Overtime',
    'overtime_list'           => 'Overtime List',
    'add_overtime'            => 'Add Overtime',
    'edit_overtime'           => 'Edit Overtime',
    'hour_rate'               => 'Hour Rate',
    'hour_rate_auto_hint'     => 'Calculated automatically from basic salary and can be edited.',
    'overtime_saved_success'  => 'Overtime saved successfully',
    'overtime_updated_success' => 'Overtime updated successfully',
    'overtime_deleted_success' => 'Overtime deleted successfully',
    'overtime_approved_success' => 'Overtime approved successfully',
    'overtime_approve_confirm_msg'     => 'Overtime will be approved and saved. Do you want to continue?',

    // Allowances
    'allowances'              => 'Bonuses and Allowances',
    'allowances_list'         => 'Bonuses and Allowances List',
    'add_allowance'           => 'Add Bonus/Allowance',
    'edit_allowance'          => 'Edit Bonus/Allowance',
    'allowance_saved_success' => 'Bonus/Allowance saved successfully',
    'allowance_updated_success' => 'Bonus/Allowance updated successfully',
    'allowance_deleted_success' => 'Bonus/Allowance deleted successfully',
    'allowance_approved_success' => 'Bonus/Allowance approved successfully',
    'allowance_approve_confirm_msg'     => 'Bonus/Allowance will be approved and saved. Do you want to continue?',

    // Allowance types (for display)
    'allowance_type_bonus'           => 'Bonus',
    'allowance_type_incentive'       => 'Incentive',
    'allowance_type_transportation'  => 'Transportation Allowance',
    'allowance_type_housing'         => 'Housing Allowance',
    'allowance_type_meal'            => 'Meal Allowance',
    'allowance_type_other'           => 'Other',
    'overtime_desc'           => 'Managing overtime hours',
    'allowances_desc'           => 'Managing bonuses and allowances',

    // Overtime - generate from attendance
    'generate_from_attendance' => 'Generate from Attendance',
    'generate'                 => 'Generate',
    'date_from'                => 'From Date',
    'date_to'                  => 'To Date',
    'calc_notes'               => 'Calculation Notes',

    // Source labels
    'source_attendance' => 'From Attendance',

    // Messages (optional but used in controller/view)
    'overtime_generated_success' => 'Overtime generated successfully',
    'no_data'                    => 'No data',
    'done'                       => 'Done',

    // ─────────────────────────────────────────
    // Payrolls
    'payrolls_list' => 'Payrolls List',

    'generate_payrolls' => 'Generate Payrolls',

    'approve_payrolls' => 'Approve Payrolls',
    'payrolls_approve_confirm_msg' => 'Do you want to approve payrolls and apply items (Overtime/Allowances/Deductions/Advance installments)?',

    'pay_payrolls' => 'Pay Payrolls',
    'pay' => 'Pay',
    'pay_bulk_note' => 'All approved payrolls for the selected month/branch will be paid.',

    'note_generate_snapshot_method' => 'Note: Payment method snapshot is saved from employee data at the time of generation.',

    // Status
    'draft' => 'Draft',
    'approved' => 'Approved',
    'all' => 'All',
    // Fields
    'base_salary' => 'Basic Salary',
    'net_salary' => 'Net Salary',

    'payment_method' => 'Payment Method',
    'payment_date' => 'Payment Date',
    'payment_reference' => 'Payment Reference',

    // Messages
    'payrolls_generated_success' => 'Payrolls generated successfully',
    'payrolls_approved_success' => 'Payrolls approved successfully',
    'payrolls_paid_success' => 'Payrolls paid successfully',
    'no_data_to_approve' => 'No draft payrolls for approval',
    'no_data_to_pay' => 'No approved payrolls for payment',
    'total_rows' => 'Number of Records',
    'total_salary_base' => 'Total Basic',
    'total_gross' => 'Total Gross',
    'total_overtime' => 'Total Overtime',
    'total_allowances' => 'Total Allowances/Incentives',
    'total_advances' => 'Total Advances',
    'total_deductions' => 'Total Deductions/Penalties',
    'total_net' => 'Total Net',
    'payment_details' => 'Payment Method Statement',
    'generate_note_auto_ot' => 'Overtime will be automatically generated from attendance (when needed) without duplication.',
    'cancel_drafts' => 'Cancel (Drafts)',
    'cancel_drafts_note' => 'Only draft payrolls for the same month/branch will be deleted; approved/paid ones will not be touched.',
    'delete_auto_overtime' => 'Delete automatically generated overtime',
    'delete_auto_overtime_hint' => 'Only records generated with AUTO_OT_FROM_PAYROLL tag and not applied to a sheet will be deleted.',
    'confirm' => 'Confirm',
    'details' => 'Details',
    'payroll_breakdown' => 'Payroll Sheet Details',
    'breakdown_overtime' => 'Overtime Details',
    'breakdown_allowances' => 'Allowances/Incentives Details',
    'breakdown_deductions' => 'Deductions/Penalties Details',
    'breakdown_advances' => 'Advances Details',
    'close' => 'Close',
    'no_results' => 'No data',
    'payment_info' => 'Payment',
    'attendance_days' => 'Attendance',
    'breakdown_attendance' => 'Attendance Details',
    'present_days' => 'Presence',
    'absent_days' => 'Absence',
    'halfday_days' => 'Half Day',
    'late_days' => 'Delay',
    'work_days' => 'Work Days',
    'day_rate' => 'Day Rate',
    'present_amount' => 'Presence Value',
    'halfday_amount' => 'Half Day Value',
    'attendance_total_amount' => 'Total Attendance Value',


];
