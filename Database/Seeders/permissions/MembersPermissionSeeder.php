<?php

namespace Database\Seeders\permissions;

use Illuminate\Database\Seeder;
use App\Models\Permission;

class MembersPermissionSeeder extends Seeder
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
                'name'     => 'member_create',
                'title'    => ['en' => 'Create Member',      'ar' => 'إنشاء عضو'],
                'category' => ['en' => 'members',        'ar' => 'الأعضاء'],
            ],
            [
                'name'     => 'member_view',
                'title'    => ['en' => 'View Member',      'ar' => 'عرض عضو'],
                'category' => ['en' => 'members',        'ar' => 'الأعضاء'],
            ],
            [
                'name'     => 'member_edit',
                'title'    => ['en' => 'Edit Member',      'ar' => 'تعديل عضو'],
                'category' => ['en' => 'members',        'ar' => 'الأعضاء'],
            ],
            [
                'name'     => 'member_delete',
                'title'    => ['en' => 'Delete Member',      'ar' => 'حذف عضو'],
                'category' => ['en' => 'members',        'ar' => 'الأعضاء'],
            ],
            [
                'name'     => 'member_card_and_qr',
                'title'    => ['en' => 'Print Card & QR',      'ar' => 'طباعة الكارت والـ QR'],
                'category' => ['en' => 'members',        'ar' => 'الأعضاء'],
            ],
        ];



        foreach ($permissions as $permissionData) {
            if (!Permission::where('name', $permissionData['name'])->exists()) {
                Permission::create($permissionData);
            }
        }
    }
}
