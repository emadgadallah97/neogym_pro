<?php

return [
    'employees' => 'الموظفين',
    'employees_list' => 'قائمة الموظفين',
    'add_new_employee' => 'إضافة موظف جديد',
    'update_employee' => 'تعديل الموظف',

    'employee_code' => 'كود الموظف',
    'full_name' => 'الاسم',
    'first_name' => 'الاسم الأول',
    'last_name' => 'الاسم الأخير',

    'job' => 'الوظيفة',
    'branches' => 'الفروع',
    'primary_branch' => 'الفرع الأساسي',
    'branches_hint' => 'اختر فرع واحد على الأقل.',
    'primary_branch_hint' => 'يجب أن يكون الفرع الأساسي ضمن الفروع المختارة.',

    'gender' => 'الجنس',
    'male' => 'ذكر',
    'female' => 'أنثى',
    'birth_date' => 'تاريخ الميلاد',

    'photo' => 'الصورة',
    'view_photo' => 'عرض الصورة',

    'phone' => 'الهاتف',
    'phone_1' => 'هاتف 1',
    'phone_2' => 'هاتف 2',
    'whatsapp' => 'واتساب',
    'email' => 'البريد الإلكتروني',

    'specialization' => 'التخصص',
    'years_experience' => 'سنوات الخبرة',
    'bio' => 'نبذة',

    'compensation_type' => 'نوع التعويض',
    'salary_only' => 'راتب فقط',
    'commission_only' => 'عمولة فقط',
    'salary_and_commission' => 'راتب + عمولة',
    'compensation_hint' => 'سيتم إظهار حقول الراتب/العمولة حسب الاختيار.',

    'base_salary' => 'الراتب الأساسي',
    'commission_percent' => 'نسبة العمولة (%)',
    'commission_fixed' => 'عمولة ثابتة',

    'salary_transfer_method' => 'طريقة تحويل الراتب',
    'salary_transfer_details' => 'بيانات التحويل',

    'cash' => 'كاش',
    'ewallet' => 'محفظة إلكترونية',
    'bank_transfer' => 'حساب/تحويل بنكي',
    'instapay' => 'InstaPay',
    'credit_card' => 'بطاقة ائتمانية',
    'cheque' => 'شيك',
    'other' => 'أخرى',

    // Validation / business messages
    'primary_branch_must_be_selected' => 'الفرع الأساسي يجب أن يكون ضمن الفروع المختارة.',
    'base_salary_required' => 'الراتب الأساسي مطلوب عند اختيار (راتب فقط) أو (راتب + عمولة).',
    'commission_required' => 'العمولة مطلوبة عند اختيار (عمولة فقط) أو (راتب + عمولة) (أدخل نسبة أو مبلغ ثابت).',
    'transfer_method_required' => 'طريقة تحويل الراتب مطلوبة عند وجود راتب.',

    // CRUD messages
    'saved_success' => 'تم حفظ الموظف بنجاح.',
    'saved_error' => 'حدث خطأ أثناء حفظ الموظف.',
    'updated_success' => 'تم تحديث الموظف بنجاح.',
    'updated_error' => 'حدث خطأ أثناء تحديث الموظف.',
    'deleted_success' => 'تم حذف الموظف بنجاح.',
    'deleted_error' => 'حدث خطأ أثناء حذف الموظف.',

    // Delete modal
    'delete_confirm_title' => 'تأكيد الحذف',
    'delete_confirm_text' => 'سيتم حذف الموظف: ',
    'confirm_delete' => 'تأكيد الحذف',
 

    // New keys (for the improved UI)
    'search' => 'بحث',
    'search_hint' => 'ابحث بالاسم / الهاتف / الكود',
    'search_note' => 'يمكنك البحث السريع باستخدام الاسم أو الهاتف أو كود الموظف.',
    'required_fields_note' => 'الحقول المطلوبة: الاسم الأول، الاسم الأخير، الفروع، الفرع الأساسي، نوع التعويض.',
    'reset_filters' => 'مسح الفلاتر',

    'view' => 'عرض',
    'edit' => 'تعديل',
    'delete' => 'حذف',
    'more' => 'المزيد',

];
