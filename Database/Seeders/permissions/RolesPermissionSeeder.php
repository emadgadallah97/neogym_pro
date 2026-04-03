<?php

namespace Database\Seeders\permissions;

use Illuminate\Database\Seeder;
use App\Models\Permission;

class RolesPermissionSeeder extends Seeder
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
                'name'     => 'roles_view',
                'title'    => ['en' => 'Roles - View List/Profile', 'ar' => 'عرض قائمة/تفاصيل الأدوار'],
                'category' => ['en' => 'roles', 'ar' => 'الأدوار والصلاحيات'],
            ],
            [
                'name'     => 'roles_create',
                'title'    => ['en' => 'Roles - Create Role', 'ar' => 'إضافة دور جديد'],
                'category' => ['en' => 'roles', 'ar' => 'الأدوار والصلاحيات'],
            ],
            [
                'name'     => 'roles_edit',
                'title'    => ['en' => 'Roles - Edit Role', 'ar' => 'تعديل بيانات الدور'],
                'category' => ['en' => 'roles', 'ar' => 'الأدوار والصلاحيات'],
            ],
            [
                'name'     => 'roles_delete',
                'title'    => ['en' => 'Roles - Delete Role', 'ar' => 'حذف دور'],
                'category' => ['en' => 'roles', 'ar' => 'الأدوار والصلاحيات'],
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
