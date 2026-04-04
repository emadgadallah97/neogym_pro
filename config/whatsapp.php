<?php

// Runtime config is stored in whatsapp_settings table (editable from UI).
// These values are used only as fallback when DB row is missing.

return [
    // سر مشترك بين Node و Laravel لتسجيل الرسائل الواردة (POST /api/whatsapp/incoming)
    'internal_webhook_secret' => env('WHATSAPP_INTERNAL_SECRET', ''),

    'defaults' => [
        'service_url' => 'http://localhost:3001',
        'api_key' => 'change_this_secret',
        'country_code' => '20',
        'bulk_delay' => 1500,
        'timeout' => 30,
        'enabled' => true,
        'log_messages' => true,
        'max_bulk' => 50,
        'test_phone' => '',
    ],

    'templates' => [
        'welcome' => [
            'key' => 'welcome',
            'label' => 'رسالة الترحيب بعضو جديد',
            'body' => "أهلاً وسهلاً {name} 🎉\nاشتراكك: {subscription}\nتاريخ البدء: {date}\nنتمنى لك رحلة رياضية موفقة 💪",
            'variables' => ['{name}', '{subscription}', '{date}'],
        ],
        'renewal_reminder' => [
            'key' => 'renewal_reminder',
            'label' => 'تذكير بتجديد الاشتراك',
            'body' => "مرحباً {name} 👋\nاشتراكك ينتهي في: {end_date}\nجدد الآن للاستمرار معنا 💪",
            'variables' => ['{name}', '{end_date}'],
        ],
        'payment_confirmation' => [
            'key' => 'payment_confirmation',
            'label' => 'تأكيد استلام الدفع',
            'body' => "عزيزي {name}\nتم استلام دفعتك بقيمة {amount} {currency}\nتاريخ العملية: {date}\nشكراً لثقتك 🙏",
            'variables' => ['{name}', '{amount}', '{currency}', '{date}'],
        ],
        'birthday' => [
            'key' => 'birthday',
            'label' => 'تهنئة عيد ميلاد',
            'body' => "كل سنة وأنت بخير يا {name} 🎂🎉\nنتمنى لك يوماً سعيداً ولياقة دائمة من فريق النادي 💪",
            'variables' => ['{name}'],
        ],
        'session_reminder' => [
            'key' => 'session_reminder',
            'label' => 'تذكير بجلسة تدريب',
            'body' => "مرحباً {name}\nلديك جلسة {session_type} اليوم الساعة {time}\nننتظرك 🏋️",
            'variables' => ['{name}', '{session_type}', '{time}'],
        ],
        'absence_followup' => [
            'key' => 'absence_followup',
            'label' => 'متابعة غياب',
            'body' => "أهلاً {name}\nاشتقنا لك في النادي! آخر زيارة كانت: {last_visit}\nنسعد بعودتك قريباً 💚",
            'variables' => ['{name}', '{last_visit}'],
        ],
    ],
];
