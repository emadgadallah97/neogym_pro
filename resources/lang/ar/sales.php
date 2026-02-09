<?php

return [

    // عامة
    'sales' => 'المبيعات / اشتراكات الأعضاء',
    'view' => 'عرض',
    'status' => 'الحالة',

    // الصفحة الرئيسية
    'new_subscription_sale' => 'إضافة اشتراك جديد للعضو',
    'form_hint' => 'قم باختيار الفرع ثم العضو والخطة، ثم استكمل بيانات المدرب والعروض وبيانات الدفع من التبويبات بالأسفل.',
    'last_subscriptions' => 'أحدث الاشتراكات المسجلة',
    'total_amount' => 'الإجمالي النهائي',

    // Tabs
    'tab_basic' => 'البيانات الأساسية',
    'tab_trainer_pt' => 'المدرب والجلسات',
    'tab_discounts' => 'العروض والكوبونات',
    'tab_payment' => 'الدفع والعمولة',

    // البيانات الأساسية
    'allow_all_branches' => 'الحضور من أي فرع',
    'allow_all_branches_label' => 'نعم، يسمح للعضو بالحضور من أي فرع',
    'source' => 'قناة الاشتراك',
    'source_reception' => 'الاستقبال',
    'source_website' => 'الموقع الإلكتروني',
    'source_mobile' => 'تطبيق الموبايل',
    'source_call_center' => 'مركز الاتصال',
    'source_partner' => 'شريك',
    'source_other' => 'أخرى',

    // المدرب و PT
    'with_trainer' => 'اشتراك مع مدرب',
    'with_trainer_label' => 'نعم، هذا الاشتراك مع مدرب رئيسي',
    'main_trainer' => 'المدرب الأساسي',
    'main_trainer_hint' => 'اختر المدرب الأساسي إذا كانت الخطة مع مدرب.',
    'pt_addons_title' => 'جلسات مدرب إضافية (PT Add-ons)',
    'pt_addons_hint' => 'يمكن إضافة أكثر من باقة جلسات PT مع مدربين مختلفين لكل اشتراك.',
    'trainer' => 'المدرب',
    'sessions_count' => 'عدد الجلسات',
    'sessions_remaining' => 'الجلسات المتبقية',
    'pt_total' => 'إجمالي سعر الجلسات',
    'add_pt_addon' => 'إضافة باقة جلسات',

    // العروض والكوبونات
    'offer_section' => 'العروض التلقائية',
    'offer_section_hint' => 'سيقوم النظام باختيار أفضل عرض صالح تلقائياً بناءً على الخطة والفرع والمبلغ.',
    'gross_amount' => 'إجمالي قبل الخصم',
    'net_amount' => 'الإجمالي بعد الخصم',
    'coupon_hint' => 'اختياري؛ سيتم التحقق من الكوبون وتطبيقه أثناء حفظ الاشتراك.',

    // الدفع والعمولة
    'sales_employee' => 'موظف المبيعات',
    'sales_employee_hint' => 'اختياري؛ سيتم حساب عمولة هذا الموظف حسب إعدادات العمولة الخاصة به.',
    'payment_method' => 'طريقة الدفع',
    'payment_other' => 'طريقة أخرى',
    'price_plan' => 'سعر الخطة',
    'price_pt_addons' => 'سعر جلسات المدرب',
    'total_discount' => 'إجمالي الخصم',
    'commission_base_amount' => 'المبلغ الأساس لحساب العمولة',
    'commission_amount' => 'قيمة العمولة',

    // عرض الاشتراك
    'subscription_show_title' => 'تفاصيل الاشتراك',
    'basic_info' => 'البيانات الأساسية',
    'pricing' => 'التسعير والعمولة',
    'payments' => 'المدفوعات',
    'amount' => 'المبلغ',
    'paid_at' => 'تاريخ الدفع',

    // رسائل
    'saved_successfully' => 'تم حفظ عملية الاشتراك بنجاح.',
    'something_went_wrong' => 'حدث خطأ أثناء حفظ عملية الاشتراك، برجاء المحاولة مرة أخرى.',
    'not_implemented_yet' => 'سيتم تفعيل حفظ عملية البيع في الخطوة التالية.',
    'coming_soon_form_hint' => 'سيتم هنا بناء نموذج مفصل لبيع الاشتراك وربطه بالخطط والعروض والكوبونات والمدفوعات.',
'plan_requires_trainer' => 'هذه الخطة تتطلب مدرب (Private Coach).',
'main_trainer_required' => 'يجب اختيار المدرب الأساسي لهذه الخطة.',
'session_price' => 'سعر الجلسة',
'best_offer' => 'أفضل عرض',
'offer_discount' => 'خصم العرض',
'amount_after_offer' => 'بعد العرض',
'no_offers_available' => 'لا يوجد عروض متاحة',
'cash' => 'نقدي',
'card' => 'بطاقة',
'transfer' => 'تحويل',
'instapay' => 'InstaPay',
'ewallet' => 'محفظة إلكترونية',
'cheque' => 'شيك',
'totals_preview_hint' => 'هذه القيم للعرض فقط (Preview) ويتم اعتماد الحساب النهائي عند الحفظ.',
'offers_list' => 'قائمة العروض المتاحة',
'auto_best_offer' => 'تلقائي (أفضل عرض)',
'selected_offer' => 'العرض المختار',
'offer_list_hint' => 'اختر عرض يدويًا أو اتركه تلقائي ليختار النظام أفضل عرض.',
'choose_branch_first' => 'اختر الفرع أولاً',
'members_hint' => 'سيتم تحميل الأعضاء النشطين بعد اختيار الفرع',
'plans_hint' => 'سيتم تحميل الخطط النشطة بعد اختيار الفرع',
   'with_trainer_optional_label' => 'اختياري: تفعيل الاشتراك مع مدرب',
    'plan_price_without_trainer' => 'سعر الاشتراك بدون مدرب',
    'price_updates_by_branch_plan' => 'يتغير السعر حسب الفرع والخطة.',
    'plan_price_with_trainer' => 'سعر الاشتراك مع المدرب',
    'plan_price_with_trainer_hint' => 'يظهر بعد اختيار المدرب الأساسي.',
    'no_trainers_for_branch_plan' => 'لا يوجد مدربين متاحين لهذه الخطة في هذا الفرع.',

    'ajax_error_try_again' => 'حدث خطأ أثناء تحميل البيانات، حاول مرة أخرى.',
    'base_price_not_found' => 'لا يوجد سعر أساسي (بدون مدرب) لهذه الخطة في هذا الفرع.',
    'coach_price_not_found' => 'لا يوجد سعر لهذه الخطة مع هذا المدرب في هذا الفرع.',


    // Coaches by branch (PT addons)
    'branch_coaches_note' => 'المدربين',
    'choose_branch_to_load_coaches' => 'اختر الفرع لعرض المدربين المتاحين.',
    'no_coaches_in_branch' => 'لا يوجد مدربين مرتبطين بهذا الفرع.',
    'coaches_loaded' => 'تم تحميل المدربين حسب الفرع المختار.',
    'coach_not_in_branch' => 'المدرب المختار غير مرتبط بالفرع الحالي.',

 'validate_coupon' => 'تحقق وتطبيق',
    'validating' => 'جارِ التحقق...',
    'coupon_valid' => 'الكوبون صالح',
    'coupon_invalid' => 'الكوبون غير صالح',
    'coupon_empty' => 'اكتب كود الكوبون أولًا.',
    'coupon_discount' => 'خصم الكوبون',
    'amount_after_coupon' => 'بعد الكوبون',



    // Payment / Invoice summary (new underscore keys)
    'preview_only'      => 'Preview فقط',
    'invoice_summary'   => 'ملخص الفاتورة',
    'item_plan'         => 'الخطة',
    'item_pt_addons'    => 'جلسات المدرب (PT)',
    'subtotal_gross'    => 'الإجمالي قبل الخصم',
    'discount_offer'    => 'خصم العرض',
    'discount_coupon'   => 'خصم الكوبون',
    'net_total'         => 'الإجمالي المستحق',
    'summary_hint'      => 'يتم تحديث الملخص تلقائيًا حسب الخطة/جلسات PT/العرض/الكوبون.',

    // Commission (new underscore keys)
    'commission_section'            => 'العمولة',
    'commission_employee'           => 'موظف المبيعات',
    'commissionbase_amount'        => 'أساس العمولة',
    'commission_net_amount'         => 'صافي المبلغ',
    'commission_estimated'          => 'قيمة العمولة',
    'commission_calculated_on_save' => 'سيتم حساب العمولة النهائية عند الحفظ حسب إعدادات الموظف.',
  // Commission UI
    'commission_value_type' => 'نوع العمولة',
    'commission_value' => 'قيمة النسبة/المبلغ',
    'commission_base_gross' => 'أساس العمولة: قبل الخصومات (Gross)',
    'commission_base_net' => 'أساس العمولة: بعد الخصومات (Net)',

 // Current subscriptions list
    'current_subscriptions' => 'الاشتراكات الحالية',
    'search' => 'بحث',
    'search_member_placeholder' => 'ابحث بكود العضو أو الاسم',
    'all' => 'الكل',
    'actions' => 'الإجراءات',
    'no_data' => 'لا توجد بيانات',

    // Filters
    'has_pt_addons' => 'حصص المدرب (PT)',
    'with_pt_addons' => 'يوجد',
    'without_pt_addons' => 'لا يوجد',
    'date_from' => 'من',
    'date_to' => 'إلى',
    'apply_filters' => 'تطبيق',
    'clear_filters' => 'مسح',

    // Modal
    'subscription_details' => 'تفاصيل الاشتراك',
    'loading' => 'جاري التحميل...',
    'close' => 'إغلاق',

    // Columns / fields
    'member' => 'العضو',
    'sessions' => 'الحصص',
    'subscription_sessions' => 'حصص الاشتراك',
    'pt_sessions' => 'حصص المدرب (PT)',
    'pt_addons_short' => 'PT',
    'remaining' => 'المتبقي',
    'yes' => 'نعم',
    'no' => 'لا',
    'start_date' => 'تاريخ البداية',
    'end_date' => 'تاريخ النهاية',

    'source_callcenter' => 'الكول سنتر',

  'per_page' => 'عدد الصفوف',

    // Status translations
    'status_active' => 'نشط',
    'status_expired' => 'منتهي',
    'status_frozen' => 'مجمّد',
    'status_cancelled' => 'ملغي',
    'status_pendingpayment' => 'بانتظار الدفع',
  // Actions
    'add_pt' => 'إضافة PT',

    // PT after sale
    'pt_addons_after_sale_title' => 'إضافة جلسات مدرب (PT) للاشتراك',
    'pt_addons_only_active' => 'متاح فقط للاشتراكات النشطة',
    'pt_addons_already_exists' => 'لا يمكن إضافة PT لأن الاشتراك يحتوي على PT بالفعل',
    'pt_addons_total_zero' => 'الإجمالي لا يمكن أن يكون صفر',
    'pt_addons_saved' => 'تمت إضافة PT بنجاح',

    // Form labels

    'trainer_filtered_by_branch' => 'المدربين حسب الفرع',
    'trainer_not_in_branch' => 'المدرب غير مرتبط بهذا الفرع',
    'reference' => 'مرجع',
'paid_at_hint' => 'يتم تعبئة التاريخ والوقت تلقائياً بالوقت الحالي ويمكنك تعديله عند الحاجة',

];
