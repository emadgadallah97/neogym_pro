<?php

namespace Database\Seeders\permissions;

use Illuminate\Database\Seeder;
use App\Models\Permission;

class EmployeesPermissionSeeder extends Seeder
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
                'name'     => 'employee_create',
                'title'    => ['en' => 'Create Employee',      'ar' => 'انشاء موظف'],
                'category' => ['en' => 'employyes',        'ar' => 'الموظفين'],
            ],
            [
                'name'     => 'employee_view',
                'title'    => ['en' => 'View Employee',      'ar' => 'عرض موظف'],
                'category' => ['en' => 'employyes',        'ar' => 'الموظفين'],
            ],
            [
                'name'     => 'employee_edit',
                'title'    => ['en' => 'Edit Employee',      'ar' => 'تعديل موظف'],
                'category' => ['en' => 'employyes',        'ar' => 'الموظفين'],
            ],
            [
                'name'     => 'employee_delete',
                'title'    => ['en' => 'Delete Employee',      'ar' => 'حذف موظف'],
                'category' => ['en' => 'employyes',        'ar' => 'الموظفين'],
            ],


        ];



        foreach ($permissions as $permissionData) {
            if (!Permission::where('name', $permissionData['name'])->exists()) {
                Permission::create($permissionData);
            }
        }
    }
}
