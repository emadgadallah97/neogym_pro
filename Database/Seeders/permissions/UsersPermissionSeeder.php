<?php

namespace Database\Seeders\permissions;

use Illuminate\Database\Seeder;
use App\Models\Permission;

class UsersPermissionSeeder extends Seeder
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
                'name'     => 'users_view',
                'title'    => ['en' => 'Users - View List/Profile', 'ar' => 'عرض قائمة/ملفات المستخدمين'],
                'category' => ['en' => 'users', 'ar' => 'المستخدمين'],
            ],
            [
                'name'     => 'users_create',
                'title'    => ['en' => 'Users - Create User', 'ar' => 'إضافة مستخدم جديد'],
                'category' => ['en' => 'users', 'ar' => 'المستخدمين'],
            ],
            [
                'name'     => 'users_edit',
                'title'    => ['en' => 'Users - Edit User', 'ar' => 'تعديل بيانات المستخدم'],
                'category' => ['en' => 'users', 'ar' => 'المستخدمين'],
            ],
            [
                'name'     => 'users_delete',
                'title'    => ['en' => 'Users - Delete User', 'ar' => 'حذف مستخدم'],
                'category' => ['en' => 'users', 'ar' => 'المستخدمين'],
            ],
            [
                'name'     => 'users_password_edit',
                'title'    => ['en' => 'Users - Edit Password', 'ar' => 'تعديل كلمة المرور'],
                'category' => ['en' => 'users', 'ar' => 'المستخدمين'],
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
