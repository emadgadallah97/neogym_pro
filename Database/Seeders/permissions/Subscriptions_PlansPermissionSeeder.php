<?php

namespace Database\Seeders\permissions;

use Illuminate\Database\Seeder;
use App\Models\Permission;

class Subscriptions_PlansPermissionSeeder extends Seeder
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
                'name'     => 'subscriptions_plan_create',
                'title'    => ['en' => 'Create Subscription Plan',      'ar' => 'إضافة خطة اشتراك'],
                'category' => ['en' => 'subscriptions plans',        'ar' => 'خطط الاشتراكات'],
            ],
            [
                'name'     => 'subscriptions_plan_view',
                'title'    => ['en' => 'View Subscription Plan',      'ar' => 'عرض خطة اشتراك'],
                'category' => ['en' => 'subscriptions plans',        'ar' => 'خطط الاشتراكات'],
            ],
            [
                'name'     => 'subscriptions_plan_edit',
                'title'    => ['en' => 'Edit Subscription Plan',      'ar' => 'تعديل خطة اشتراك'],
                'category' => ['en' => 'subscriptions plans',        'ar' => 'خطط الاشتراكات'],
            ],
            [
                'name'     => 'subscriptions_plan_delete',
                'title'    => ['en' => 'Delete Subscription Plan',      'ar' => 'حذف خطة اشتراك'],
                'category' => ['en' => 'subscriptions plans',        'ar' => 'خطط الاشتراكات'],
            ],
        ];



        foreach ($permissions as $permissionData) {
            if (!Permission::where('name', $permissionData['name'])->exists()) {
                Permission::create($permissionData);
            }
        }
    }
}
