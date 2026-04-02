<?php

namespace Database\Seeders\permissions;

use Illuminate\Database\Seeder;
use App\Models\Permission;

class SalesPermissionSeeder extends Seeder
{
    public function deletePermissionsData()
    {
        \App\Models\Role::truncate();
        Permission::truncate();

        return 'تم حذف البيانات من جداول الصلاحيات بنجاح.';
    }

    public function run()
    {
        $permissions = [
            [
                'name'     => 'sales_view_subscriptions',
                'title'    => ['en' => 'View Current Subscriptions', 'ar' => 'عرض الاشتراكات الحالية'],
                'category' => ['en' => 'sales', 'ar' => 'المبيعات'],
            ],
            [
                'name'     => 'sales_view_subscription_details',
                'title'    => ['en' => 'View Subscription Details', 'ar' => 'عرض تفاصيل الاشتراك'],
                'category' => ['en' => 'sales', 'ar' => 'المبيعات'],
            ],
            [
                'name'     => 'sales_add_pt_sessions',
                'title'    => ['en' => 'Add PT Sessions', 'ar' => 'إضافة حصص PT'],
                'category' => ['en' => 'sales', 'ar' => 'المبيعات'],
            ],
        ];



        foreach ($permissions as $permissionData) {
            if (!Permission::where('name', $permissionData['name'])->exists()) {
                Permission::create($permissionData);
            }
        }
    }
}
