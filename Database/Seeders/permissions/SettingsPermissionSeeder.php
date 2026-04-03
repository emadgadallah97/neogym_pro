<?php

namespace Database\Seeders\permissions;

use Illuminate\Database\Seeder;
use App\Models\Permission;

class SettingsPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $permissions = [
            [
                'name'     => 'settings_places_view',
                'title'    => ['en' => 'Settings - View Places', 'ar' => 'عرض إعدادات الأماكن'],
                'category' => ['en' => 'settings', 'ar' => 'الإعدادات'],
            ],
            [
                'name'     => 'settings_currencies_view',
                'title'    => ['en' => 'Settings - View Currencies', 'ar' => 'عرض إعدادات العملات'],
                'category' => ['en' => 'settings', 'ar' => 'الإعدادات'],
            ],
            [
                'name'     => 'settings_general_view',
                'title'    => ['en' => 'Settings - General Settings', 'ar' => 'عرض الإعدادات العامة'],
                'category' => ['en' => 'settings', 'ar' => 'الإعدادات'],
            ],
            [
                'name'     => 'settings_branches_view',
                'title'    => ['en' => 'Settings - View Branches', 'ar' => 'عرض إعدادات الفروع'],
                'category' => ['en' => 'settings', 'ar' => 'الإعدادات'],
            ],
            [
                'name'     => 'settings_jobs_view',
                'title'    => ['en' => 'Settings - View Jobs', 'ar' => 'عرض إعدادات الوظائف'],
                'category' => ['en' => 'settings', 'ar' => 'الإعدادات'],
            ],
            [
                'name'     => 'settings_subscriptions_types_view',
                'title'    => ['en' => 'Settings - Subscription Types', 'ar' => 'عرض أنواع الاشتراكات'],
                'category' => ['en' => 'settings', 'ar' => 'الإعدادات'],
            ],
            [
                'name'     => 'settings_commissions_view',
                'title'    => ['en' => 'Settings - Commission Settings', 'ar' => 'عرض إعدادات العمولات'],
                'category' => ['en' => 'settings', 'ar' => 'الإعدادات'],
            ],
            [
                'name'     => 'settings_trainer_pricing_view',
                'title'    => ['en' => 'Settings - Trainer Pricing', 'ar' => 'عرض تسعير حصص المدربين'],
                'category' => ['en' => 'settings', 'ar' => 'الإعدادات'],
            ],
            [
                'name'     => 'settings_expenses_types_view',
                'title'    => ['en' => 'Settings - Expenses Types', 'ar' => 'عرض أنواع المصروفات'],
                'category' => ['en' => 'settings', 'ar' => 'الإعدادات'],
            ],
            [
                'name'     => 'settings_income_types_view',
                'title'    => ['en' => 'Settings - Income Types', 'ar' => 'عرض أنواع الإيرادات'],
                'category' => ['en' => 'settings', 'ar' => 'الإعدادات'],
            ],
            [
                'name'     => 'settings_referral_sources_view',
                'title'    => ['en' => 'Settings - Referral Sources', 'ar' => 'عرض طرق التعرف علينا'],
                'category' => ['en' => 'settings', 'ar' => 'الإعدادات'],
            ],
        ];

        $owner = \App\Models\Role::where('name', 'owner')->first();

        foreach ($permissions as $permissionData) {
            $permission = Permission::updateOrCreate(
                ['name' => $permissionData['name']],
                $permissionData
            );

            if ($owner && !$owner->hasPermissionTo($permission->name)) {
                $owner->givePermissionTo($permission->name);
            }
        }
    }
}
