<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class PermissionTableSeeder extends Seeder
{
    public function deletePermissionsData()
    {
        \Spatie\Permission\Models\Role::truncate();
        Permission::truncate();

        return 'تم حذف البيانات من جداول الصلاحيات بنجاح.';
    }

    public function run()
    {
        $permissions = [
            // 🧭 لوحة القيادة
            [
                'name' => 'dashboard',
                'title' => ['en' => 'Dashboard', 'ar' => 'لوحة القيادة'],
                'category' => ['en' => 'module', 'ar' => 'نظام'],
            ],




        ];
        $this->call([
            // \Database\Seeders\permissions\OutpatientPermissionSeeder::class,




        ]);
        foreach ($permissions as $permissionData) {
            if (!Permission::where('name', $permissionData['name'])->exists()) {
                Permission::create($permissionData);
            }
        }
    }
}
