<?php

namespace Database\Seeders\permissions;

use Illuminate\Database\Seeder;
use App\Models\Permission;

class HRPermissionSeeder extends Seeder
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
                'name'     => 'hr_attendance_view',
                'title'    => ['en' => 'Attendances', 'ar' => 'الحضور والانصراف'],
                'category' => ['en' => 'human_resources', 'ar' => 'الموارد البشرية'],
            ],
            [
                'name'     => 'hr_advances_view',
                'title'    => ['en' => 'Advances', 'ar' => 'السلف'],
                'category' => ['en' => 'human_resources', 'ar' => 'الموارد البشرية'],
            ],
            [
                'name'     => 'hr_deductions_view',
                'title'    => ['en' => 'Deductions', 'ar' => 'الخصومات والجزاءات'],
                'category' => ['en' => 'human_resources', 'ar' => 'الموارد البشرية'],
            ],
            [
                'name'     => 'hr_overtime_view',
                'title'    => ['en' => 'Overtime', 'ar' => 'ساعات العمل الإضافي'],
                'category' => ['en' => 'human_resources', 'ar' => 'الموارد البشرية'],
            ],
            [
                'name'     => 'hr_allowances_view',
                'title'    => ['en' => 'Allowances', 'ar' => 'المكافآت والبدلات'],
                'category' => ['en' => 'human_resources', 'ar' => 'الموارد البشرية'],
            ],
            [
                'name'     => 'hr_payrolls_view',
                'title'    => ['en' => 'Payrolls', 'ar' => 'كشف وصرف الرواتب'],
                'category' => ['en' => 'human_resources', 'ar' => 'الموارد البشرية'],
            ],
            [
                'name'     => 'hr_devices_view',
                'title'    => ['en' => 'Fingerprint Devices', 'ar' => 'أجهزة الحضور'],
                'category' => ['en' => 'human_resources', 'ar' => 'الموارد البشرية'],
            ],
            [
                'name'     => 'hr_shifts_view',
                'title'    => ['en' => 'Shifts', 'ar' => 'الورديات'],
                'category' => ['en' => 'human_resources', 'ar' => 'الموارد البشرية'],
            ],
            [
                'name'     => 'hr_employee_shifts_view',
                'title'    => ['en' => 'Employee Shifts', 'ar' => 'ورديات الموظفين'],
                'category' => ['en' => 'human_resources', 'ar' => 'الموارد البشرية'],
            ],
            [
                'name'     => 'hr_advances_create',
                'title'    => ['en' => 'Advances - Create', 'ar' => 'إنشاء سلفة'],
                'category' => ['en' => 'human_resources', 'ar' => 'الموارد البشرية'],
            ],
            [
                'name'     => 'hr_advances_edit',
                'title'    => ['en' => 'Advances - Edit', 'ar' => 'تعديل سلفة'],
                'category' => ['en' => 'human_resources', 'ar' => 'الموارد البشرية'],
            ],
            [
                'name'     => 'hr_advances_approve',
                'title'    => ['en' => 'Advances - Approve', 'ar' => 'اعتماد سلفة'],
                'category' => ['en' => 'human_resources', 'ar' => 'الموارد البشرية'],
            ],
            [
                'name'     => 'hr_advances_reject',
                'title'    => ['en' => 'Advances - Reject', 'ar' => 'رفض سلفة'],
                'category' => ['en' => 'human_resources', 'ar' => 'الموارد البشرية'],
            ],
            [
                'name'     => 'hr_advances_delete',
                'title'    => ['en' => 'Advances - Delete', 'ar' => 'حذف سلفة'],
                'category' => ['en' => 'human_resources', 'ar' => 'الموارد البشرية'],
            ],
            [
                'name'     => 'hr_deductions_create',
                'title'    => ['en' => 'Deductions - Create', 'ar' => 'إضافة خصم/جزاء'],
                'category' => ['en' => 'human_resources', 'ar' => 'الموارد البشرية'],
            ],
            [
                'name'     => 'hr_deductions_edit',
                'title'    => ['en' => 'Deductions - Edit', 'ar' => 'تعديل خصم/جزاء'],
                'category' => ['en' => 'human_resources', 'ar' => 'الموارد البشرية'],
            ],
            [
                'name'     => 'hr_deductions_approve',
                'title'    => ['en' => 'Deductions - Approve', 'ar' => 'اعتماد خصم/جزاء'],
                'category' => ['en' => 'human_resources', 'ar' => 'الموارد البشرية'],
            ],
            [
                'name'     => 'hr_deductions_delete',
                'title'    => ['en' => 'Deductions - Delete', 'ar' => 'حذف خصم/جزاء'],
                'category' => ['en' => 'human_resources', 'ar' => 'الموارد البشرية'],
            ],
            [
                'name'     => 'hr_allowances_create',
                'title'    => ['en' => 'Allowances - Create', 'ar' => 'إضافة مكافأة/بدل'],
                'category' => ['en' => 'human_resources', 'ar' => 'الموارد البشرية'],
            ],
            [
                'name'     => 'hr_allowances_edit',
                'title'    => ['en' => 'Allowances - Edit', 'ar' => 'تعديل مكافأة/بدل'],
                'category' => ['en' => 'human_resources', 'ar' => 'الموارد البشرية'],
            ],
            [
                'name'     => 'hr_allowances_approve',
                'title'    => ['en' => 'Allowances - Approve', 'ar' => 'اعتماد مكافأة/بدل'],
                'category' => ['en' => 'human_resources', 'ar' => 'الموارد البشرية'],
            ],
            [
                'name'     => 'hr_allowances_delete',
                'title'    => ['en' => 'Allowances - Delete', 'ar' => 'حذف مكافأة/بدل'],
                'category' => ['en' => 'human_resources', 'ar' => 'الموارد البشرية'],
            ],
        ];



        $permissionsNames = array_column($permissions, 'name');

        foreach ($permissions as $permissionData) {
            Permission::updateOrCreate(
                ['name' => $permissionData['name']],
                [
                    'title'    => $permissionData['title'],
                    'category' => $permissionData['category'],
                    'guard_name' => 'web', 
                ]
            );
        }

        // assign all permissions to owner role
        $role = \App\Models\Role::where('name', 'owner')->first();
        if ($role) {
            $role->givePermissionTo($permissionsNames);
        }
    }
}
