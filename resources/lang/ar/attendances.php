<?php

return [
    'attendances' => 'الحضور',
    'kiosk_page' => 'صفحة تسجيل الحضور (سريعة)',
    'kiosk_hint' => 'قم بقراءة الباركود ثم Enter.',
    'global_scan_hint' => 'يمكنك استخدام الباركود من أي شاشة داخل النظام.',
    'branch_hint' => 'فرع المستخدم الحالي',
    'user_branch_missing' => 'يرجى تحديد الفرع للمستخدم الحالي أولاً.',
    'member_code' => 'كود العضو',
    'member' => 'العضو',
    'date' => 'التاريخ',
    'time' => 'الوقت',
    'method' => 'طريقة التسجيل',
    'base' => 'الاشتراك العام',
    'pt' => 'PT',
    'status' => 'الحالة',
    'actions' => 'إجراءات',
    'active' => 'نشط',
    'cancelled' => 'ملغي',

    'date_from' => 'من تاريخ',
    'date_to' => 'إلى تاريخ',
    'filter' => 'بحث',

    'deduct_pt' => 'خصم PT',
    'deduct_pt_hint' => 'خصم حصة PT إذا كان متاح',
    'manual_checkin' => 'تسجيل يدوي',
    'checkin' => 'تسجيل حضور',
    'processing' => 'جارٍ التنفيذ...',
    'ajax_error' => 'حدث خطأ، حاول مرة أخرى.',

    'toast_ok' => 'تم بنجاح',
    'toast_fail' => 'فشل',
    'toast_hint_close' => 'اضغط لإغلاق الرسالة',

    'add_guest' => 'إضافة ضيف',
    'guest_name' => 'اسم الضيف (اختياري)',
    'guest_phone' => 'موبايل الضيف (اختياري)',
    'save_guest' => 'حفظ الضيف',

    'cancel_attendance' => 'إلغاء الحضور',
    'cancel_pt' => 'إلغاء PT فقط',

    'member_code_required' => 'كود العضو مطلوب.',
    'member_not_found' => 'العضو غير موجود.',
    'already_checked_in_today' => 'تم تسجيل حضور هذا العضو اليوم بالفعل.',

    // ✅ Detailed subscription messages
    'member_has_no_subscriptions' => 'هذا العضو لا يملك أي اشتراكات.',
    'subscription_status_not_active' => 'لا يوجد اشتراك بحالة Active لهذا العضو (قد يكون Frozen/Cancelled/Expired).',
    'subscription_out_of_date' => 'يوجد اشتراك Active لكنه خارج فترة الصلاحية (Start/End Date).',
    'subscription_sessions_finished' => 'لا توجد حصص متبقية في الاشتراك (Sessions Remaining = 0).',
    'subscription_branch_not_allowed' => 'اشتراك العضو لا يسمح بالحضور في هذا الفرع.',
    'no_active_subscription' => 'لا يوجد اشتراك فعال يسمح بالحضور.',

    'plan_not_found' => 'لم يتم العثور على بيانات الخطة.',
    'day_not_allowed' => 'اليوم غير مسموح للحضور حسب أيام الخطة.',

    'scan_success' => 'تم تسجيل الحضور بنجاح.',
    'scan_success_without_pt' => 'تم تسجيل الحضور بنجاح (بدون خصم PT لعدم توفر رصيد PT).',
    'something_went_wrong' => 'حدث خطأ غير متوقع.',

    'attendance_not_found' => 'سجل الحضور غير موجود.',
    'already_cancelled' => 'هذا السجل ملغي بالفعل.',
    'cannot_edit_cancelled' => 'لا يمكن تعديل سجل ملغي.',
    'cancelled_success' => 'تم إلغاء الحضور وإرجاع الحصص.',

    'pt_not_deducted' => 'لم يتم خصم PT في هذا السجل.',
    'pt_addon_not_found' => 'لم يتم العثور على اشتراك PT.',
    'pt_cancelled_success' => 'تم إلغاء خصم PT وإرجاع الحصة.',

    'guests_not_allowed' => 'هذه الخطة لا تسمح بإضافة ضيوف.',
    'guest_day_not_allowed' => 'اليوم غير مسموح للضيوف حسب الخطة.',
    'guest_people_limit_reached' => 'تم الوصول للحد الأقصى لعدد الضيوف في هذه الزيارة.',
    'guest_times_limit_reached' => 'تم الوصول للحد الأقصى لمرات استخدام الضيوف لهذه الخطة.',
    'guest_added_success' => 'تم إضافة الضيف بنجاح.',
];
