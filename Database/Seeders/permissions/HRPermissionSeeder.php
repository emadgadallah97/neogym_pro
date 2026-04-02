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
                'title'    => ['en' => 'View Attendances', 'ar' => 'عرض الحضور والانصراف'],
                'category' => ['en' => 'human_resources', 'ar' => 'الموارد البشرية'],
            ],
            [
                'name'     => 'hr_advances_view',
                'title'    => ['en' => 'View Advances', 'ar' => 'عرض السلف'],
                'category' => ['en' => 'human_resources', 'ar' => 'الموارد البشرية'],
            ],
            [
                'name'     => 'hr_deductions_view',
                'title'    => ['en' => 'View Deductions', 'ar' => 'عرض الخصومات والجزاءات'],
                'category' => ['en' => 'human_resources', 'ar' => 'الموارد البشرية'],
            ],
            [
                'name'     => 'hr_overtime_view',
                'title'    => ['en' => 'View Overtime', 'ar' => 'عرض ساعات العمل الإضافي'],
                'category' => ['en' => 'human_resources', 'ar' => 'الموارد البشرية'],
            ],
            [
                'name'     => 'hr_allowances_view',
                'title'    => ['en' => 'View Allowances', 'ar' => 'عرض المكافآت والبدلات'],
                'category' => ['en' => 'human_resources', 'ar' => 'الموارد البشرية'],
            ],
            [
                'name'     => 'hr_payrolls_view',
                'title'    => ['en' => 'View Payrolls', 'ar' => 'عرض كشف وصرف الرواتب'],
                'category' => ['en' => 'human_resources', 'ar' => 'الموارد البشرية'],
            ],
            [
                'name'     => 'hr_devices_view',
                'title'    => ['en' => 'View Fingerprint Devices', 'ar' => 'عرض أجهزة الحضور'],
                'category' => ['en' => 'human_resources', 'ar' => 'الموارد البشرية'],
            ],
            [
                'name'     => 'hr_shifts_view',
                'title'    => ['en' => 'View Shifts', 'ar' => 'عرض الورديات'],
                'category' => ['en' => 'human_resources', 'ar' => 'الموارد البشرية'],
            ],
            [
                'name'     => 'hr_employee_shifts_view',
                'title'    => ['en' => 'View Employee Shifts', 'ar' => 'عرض ورديات الموظفين'],
                'category' => ['en' => 'human_resources', 'ar' => 'الموارد البشرية'],
            ],
        ];



        foreach ($permissions as $permissionData) {
            if (!Permission::where('name', $permissionData['name'])->exists()) {
                Permission::create($permissionData);
            }
        }
    }
}
