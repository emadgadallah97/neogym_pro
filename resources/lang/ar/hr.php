<?php
return [
    'title'                    => 'الموارد البشرية',
    'programs_label'           => 'برامج الموارد البشرية',
    'open'                     => 'فتح',

    // Stats
    'total_employees'          => 'إجمالي الموظفين',
    'present_today'            => 'حاضرون اليوم',
    'pending_advances'         => 'سلف معلقة',
    'month_payrolls'           => 'رواتب الشهر',

    // Cards
    'attendances'              => 'الحضور والانصراف',
    'attendances_desc'         => 'تسجيل ومتابعة حضور وانصراف الموظفين',
    'advances'                 => 'السلف',
    'advances_desc'            => 'إدارة طلبات السلف وتتبع الأقساط الشهرية',
    'deductions'               => 'الخصومات والجزاءات',
    'deductions_desc'          => 'تسجيل الخصومات والجزاءات وتطبيقها على الرواتب',
    'overtime_allowances'      => 'الإضافي والمكافآت',
    'overtime_allowances_desc' => 'إدارة ساعات العمل الإضافي والمكافآت والبدلات',
    'payrolls'                 => 'كشف وصرف الرواتب',
    'payrolls_desc'            => 'إعداد كشوف الرواتب الشهرية واعتمادها وصرفها',
    'devices'                  => 'أجهزة الحضور',
    'devices_desc'             => 'إدارة أجهزة البصمة ومزامنة بيانات الحضور',
    'reports'                  => 'تقارير الموارد البشرية',
    'reports_desc'             => 'تقارير تحليلية شاملة للحضور والرواتب والموظفين',
    'employees'                => 'الموظفون',
    'employees_desc'           => 'إدارة بيانات الموظفين والوظائف والفروع',
    // ── Devices ─────────────────────────────────────────────────
'devices_list'               => 'قائمة أجهزة الحضور',
'add_device'                 => 'إضافة جهاز',
'edit_device'                => 'تعديل جهاز الحضور',
'no_devices'                 => 'لا توجد أجهزة مسجلة',
'device_name'                => 'اسم الجهاز',
'device_name_placeholder'    => 'مثال: جهاز البصمة - الفرع الرئيسي',
'total_devices'              => 'إجمالي الأجهزة',
'active_devices'             => 'أجهزة نشطة',
'inactive_devices'           => 'أجهزة غير نشطة',

// ── Fields ──────────────────────────────────────────────────
'branch'                     => 'الفرع',
'select_branch'              => '-- اختر الفرع --',
'serial_number'              => 'الرقم التسلسلي',
'ip_address'                 => 'عنوان IP',
'status'                     => 'الحالة',
'active'                     => 'نشط',
'inactive'                   => 'غير نشط',
'notes'                      => 'ملاحظات',
'notes_placeholder'          => 'أي ملاحظات إضافية...',
'optional'                   => 'اختياري',

// ── Actions ─────────────────────────────────────────────────
'actions'                    => 'الإجراءات',
'edit'                       => 'تعديل',
'delete'                     => 'حذف',
'save'                       => 'حفظ',
'update'                     => 'تحديث',
'cancel'                     => 'إلغاء',

// ── Validation ──────────────────────────────────────────────
'validation_branch_required' => 'الفرع مطلوب',
'validation_name_required'   => 'اسم الجهاز مطلوب',
'validation_serial_required' => 'الرقم التسلسلي مطلوب',
'validation_serial_unique'   => 'الرقم التسلسلي مستخدم بالفعل',
'validation_ip_invalid'      => 'عنوان IP غير صحيح',

// ── Messages ────────────────────────────────────────────────
'device_created_success'     => 'تم إضافة الجهاز بنجاح',
'device_updated_success'     => 'تم تحديث بيانات الجهاز بنجاح',
'device_deleted_success'     => 'تم حذف الجهاز بنجاح',
'device_has_logs_error'      => 'لا يمكن حذف الجهاز، توجد سجلات بصمات مرتبطة به',
'error_occurred'             => 'حدث خطأ، يرجى المحاولة مجدداً',

// ── Confirm Dialog ───────────────────────────────────────────
'delete_confirm_title'       => 'تأكيد الحذف',
'delete_confirm_msg'         => 'هل تريد حذف الجهاز',
'yes_delete'                 => 'نعم، احذف',
// ── Attendance ─────────────────────────────────────────────
'attendance'                  => 'الحضور والانصراف',
'attendance_list'             => 'سجل الحضور والانصراف',
'manual_entry'                => 'إدخال يدوي',
'process_logs'                => 'معالجة البصمات الخام',
'raw_logs'                    => 'البصمات الخام',
'run_processing'              => 'تشغيل المعالجة',

// ── Filters / View ─────────────────────────────────────────
'filter'                      => 'بحث',
'view_mode'                   => 'نوع العرض',
'monthly'                     => 'شهري',
'daily'                       => 'يومي',
'month'                       => 'الشهر',
'date'                        => 'التاريخ',
'all_employees'               => 'كل الموظفين',
'employee'                    => 'الموظف',
'select_employee'             => '-- اختر الموظف --',

// ── Table Columns ──────────────────────────────────────────
'shift'                       => 'الوردية',
'check_in'                    => 'دخول',
'check_out'                   => 'خروج',
'total_hours'                 => 'إجمالي الساعات',
'source'                      => 'المصدر',

// ── Status ────────────────────────────────────────────────
'present'                     => 'حاضر',
'absent'                      => 'غائب',
'late'                        => 'متأخر',
'halfday'                     => 'نصف يوم',
'leave'                       => 'إجازة',

// ── Sources ───────────────────────────────────────────────
'source_manual'               => 'يدوي',
'source_device'               => 'جهاز',
'source_system'               => 'النظام',

// ── Devices / Logs ────────────────────────────────────────
'device'                      => 'الجهاز',
'all_devices'                 => 'كل الأجهزة',
'punch_time'                  => 'وقت البصمة',
'punch_type'                  => 'نوع البصمة',
'in'                          => 'دخول',
'out'                         => 'خروج',
'unknown'                     => 'غير معروف',

// ── Shifts ────────────────────────────────────────────────
'shifts'                      => 'الورديات',
'shift_name'                  => 'اسم الوردية',
'no_shift'                    => 'بدون وردية',
'start_time'                  => 'بداية الدوام',
'end_time'                    => 'نهاية الدوام',
'grace_minutes'               => 'سماح التأخير (دقيقة)',
'working_days'                => 'أيام العمل',

// ── General UI ────────────────────────────────────────────
'back'                        => 'رجوع',

// ── Messages ──────────────────────────────────────────────
'attendance_saved_success'    => 'تم حفظ الحضور بنجاح',
'attendance_updated_success'  => 'تم تحديث الحضور بنجاح',
'attendance_deleted_success'  => 'تم حذف السجل بنجاح',
'logs_processed_success'      => 'تمت معالجة البصمات بنجاح',
'log_received_success'        => 'تم استقبال البصمة بنجاح',

// ── Errors ────────────────────────────────────────────────
'employee_not_in_branch'      => 'هذا الموظف غير تابع لهذا الفرع (الفرع الأساسي)',
'device_not_found'            => 'لم يتم العثور على الجهاز',

];
