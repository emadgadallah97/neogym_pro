<?php

namespace Database\Seeders\permissions;

use Illuminate\Database\Seeder;
use App\Models\Permission;

class CouponsPermissionSeeder extends Seeder
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
                'name'     => 'coupon_create',
                'title'    => ['en' => 'Add Coupon', 'ar' => 'إضافة كوبون'],
                'category' => ['en' => 'coupons', 'ar' => 'الكوبونات'],
            ],
            [
                'name'     => 'coupon_view',
                'title'    => ['en' => 'View Coupon', 'ar' => 'عرض الكوبون'],
                'category' => ['en' => 'coupons', 'ar' => 'الكوبونات'],
            ],
            [
                'name'     => 'coupon_edit',
                'title'    => ['en' => 'Edit Coupon', 'ar' => 'تعديل الكوبون'],
                'category' => ['en' => 'coupons', 'ar' => 'الكوبونات'],
            ],
            [
                'name'     => 'coupon_delete',
                'title'    => ['en' => 'Delete Coupon', 'ar' => 'حذف الكوبون'],
                'category' => ['en' => 'coupons', 'ar' => 'الكوبونات'],
            ],
        ];



        foreach ($permissions as $permissionData) {
            if (!Permission::where('name', $permissionData['name'])->exists()) {
                Permission::create($permissionData);
            }
        }
    }
}
