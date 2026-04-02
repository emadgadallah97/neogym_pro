<?php

namespace Database\Seeders\permissions;

use Illuminate\Database\Seeder;
use App\Models\Permission;

class AttendancesPermissionSeeder extends Seeder
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
                'name'     => 'attendance_cancel',
                'title'    => ['en' => 'Cancel Attendance',      'ar' => 'إلغاء الحضور'],
                'category' => ['en' => 'attendance',        'ar' => 'الحضور'],
            ],
            [
                'name'     => 'attendance_guest_add',
                'title'    => ['en' => 'Add Guest',      'ar' => 'إضافة ضيف'],
                'category' => ['en' => 'attendance',        'ar' => 'الحضور'],
            ],
            [
                'name'     => 'attendance_pt_cancel',
                'title'    => ['en' => 'Cancel PT Sessions',      'ar' => 'إلغاء حصص PT'],
                'category' => ['en' => 'attendance',        'ar' => 'الحضور'],
            ],
        ];



        foreach ($permissions as $permissionData) {
            if (!Permission::where('name', $permissionData['name'])->exists()) {
                Permission::create($permissionData);
            }
        }
    }
}
