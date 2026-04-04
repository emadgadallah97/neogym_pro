<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('whats_app_templates', function (Blueprint $table) {
            $table->id();
            $table->string('key', 100)->unique();
            $table->string('label', 255);
            $table->text('body');
            $table->boolean('is_active')->default(true);
            $table->json('variables')->nullable();
            $table->boolean('is_system')->default(false);
            $table->timestamps();
        });

        $now = now();
        $templates = [
            [
                'key' => 'welcome',
                'label' => 'رسالة الترحيب بعضو جديد',
                'body' => "أهلاً وسهلاً {name} 🎉\nاشتراكك: {subscription}\nتاريخ البدء: {date}\nنتمنى لك رحلة رياضية موفقة 💪",
                'variables' => json_encode(['{name}', '{subscription}', '{date}'], JSON_UNESCAPED_UNICODE),
                'is_active' => 1,
                'is_system' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'renewal_reminder',
                'label' => 'تذكير بتجديد الاشتراك',
                'body' => "مرحباً {name} 👋\nاشتراكك ينتهي في: {end_date}\nجدد الآن للاستمرار معنا 💪",
                'variables' => json_encode(['{name}', '{end_date}'], JSON_UNESCAPED_UNICODE),
                'is_active' => 1,
                'is_system' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'payment_confirmation',
                'label' => 'تأكيد استلام الدفع',
                'body' => "عزيزي {name}\nتم استلام دفعتك بقيمة {amount} {currency}\nتاريخ العملية: {date}\nشكراً لثقتك 🙏",
                'variables' => json_encode(['{name}', '{amount}', '{currency}', '{date}'], JSON_UNESCAPED_UNICODE),
                'is_active' => 1,
                'is_system' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'birthday',
                'label' => 'تهنئة عيد ميلاد',
                'body' => "كل سنة وأنت بخير يا {name} 🎂🎉\nنتمنى لك يوماً سعيداً ولياقة دائمة من فريق النادي 💪",
                'variables' => json_encode(['{name}'], JSON_UNESCAPED_UNICODE),
                'is_active' => 1,
                'is_system' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'session_reminder',
                'label' => 'تذكير بجلسة تدريب',
                'body' => "مرحباً {name}\nلديك جلسة {session_type} اليوم الساعة {time}\nننتظرك 🏋️",
                'variables' => json_encode(['{name}', '{session_type}', '{time}'], JSON_UNESCAPED_UNICODE),
                'is_active' => 1,
                'is_system' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'absence_followup',
                'label' => 'متابعة غياب',
                'body' => "أهلاً {name}\nاشتقنا لك في النادي! آخر زيارة كانت: {last_visit}\nنسعد بعودتك قريباً 💚",
                'variables' => json_encode(['{name}', '{last_visit}'], JSON_UNESCAPED_UNICODE),
                'is_active' => 1,
                'is_system' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        DB::table('whats_app_templates')->insert($templates);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('whats_app_templates');
    }
};
