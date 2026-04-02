<?php

namespace Database\Seeders\permissions;

use Illuminate\Database\Seeder;
use App\Models\Permission;

class OffersPermissionSeeder extends Seeder
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
                'name'     => 'offer_create',
                'title'    => ['en' => 'Add Offer', 'ar' => 'إضافة عرض'],
                'category' => ['en' => 'offers', 'ar' => 'العروض'],
            ],
            [
                'name'     => 'offer_view',
                'title'    => ['en' => 'View Offer', 'ar' => 'عرض العرض'],
                'category' => ['en' => 'offers', 'ar' => 'العروض'],
            ],
            [
                'name'     => 'offer_edit',
                'title'    => ['en' => 'Edit Offer', 'ar' => 'تعديل العرض'],
                'category' => ['en' => 'offers', 'ar' => 'العروض'],
            ],
            [
                'name'     => 'offer_delete',
                'title'    => ['en' => 'Delete Offer', 'ar' => 'حذف العرض'],
                'category' => ['en' => 'offers', 'ar' => 'العروض'],
            ],
        ];



        foreach ($permissions as $permissionData) {
            if (!Permission::where('name', $permissionData['name'])->exists()) {
                Permission::create($permissionData);
            }
        }
    }
}
