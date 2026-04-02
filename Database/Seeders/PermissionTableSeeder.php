<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Permission;

class PermissionTableSeeder extends Seeder
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

            // ==========================================
            // 🗂️ صلاحيات الموديولات — مستخرجة من الـ Sidebar
            // ==========================================

            [
                'name'     => 'dashboard',
                'title'    => ['en' => 'Dashboard',      'ar' => 'لوحة القيادة'],
                'category' => ['en' => 'Modules',        'ar' => 'الموديولات'],
            ],
            [
                'name'     => 'attendance',
                'title'    => ['en' => 'Attendance',     'ar' => 'الحضور والغياب'],
                'category' => ['en' => 'Modules',        'ar' => 'الموديولات'],
            ],
                        [
                'name'     => 'employees',
                'title'    => ['en' => 'Employees',     'ar' => 'الموظفين'],
                'category' => ['en' => 'Modules',        'ar' => 'الموديولات'],
            ],
            [
                'name'     => 'human_resources',
                'title'    => ['en' => 'Human Resources','ar' => 'الموارد البشرية'],
                'category' => ['en' => 'Modules',        'ar' => 'الموديولات'],
            ],
            [
                'name'     => 'members',
                'title'    => ['en' => 'Members',        'ar' => 'الأعضاء'],
                'category' => ['en' => 'Modules',        'ar' => 'الموديولات'],
            ],
            [
                'name'     => 'subscriptions',
                'title'    => ['en' => 'Subscriptions',  'ar' => 'الاشتراكات'],
                'category' => ['en' => 'Modules',        'ar' => 'الموديولات'],
            ],
            [
                'name'     => 'sales',
                'title'    => ['en' => 'Sales',          'ar' => 'المبيعات'],
                'category' => ['en' => 'Modules',        'ar' => 'الموديولات'],
            ],
            [
                'name'     => 'coupons',
                'title'    => ['en' => 'Coupons',        'ar' => 'الكوبونات'],
                'category' => ['en' => 'Modules',        'ar' => 'الموديولات'],
            ],
            [
                'name'     => 'offers',
                'title'    => ['en' => 'Offers',         'ar' => 'العروض'],
                'category' => ['en' => 'Modules',        'ar' => 'الموديولات'],
            ],
            [
                'name'     => 'accounting',
                'title'    => ['en' => 'Accounting',     'ar' => 'الحسابات'],
                'category' => ['en' => 'Modules',        'ar' => 'الموديولات'],
            ],
            // Treasury
            [
                'name'     => 'treasury.view',
                'title'    => ['en' => 'Treasury View',  'ar' => 'عرض الخزينة'],
                'category' => ['en' => 'Treasury',       'ar' => 'الخزينة'],
            ],
            [
                'name'     => 'treasury.open',
                'title'    => ['en' => 'Treasury Open',  'ar' => 'فتح فترة خزينة'],
                'category' => ['en' => 'Treasury',       'ar' => 'الخزينة'],
            ],
            [
                'name'     => 'treasury.close',
                'title'    => ['en' => 'Treasury Close', 'ar' => 'إغلاق فترة خزينة'],
                'category' => ['en' => 'Treasury',       'ar' => 'الخزينة'],
            ],
            [
                'name'     => 'treasury.manual',
                'title'    => ['en' => 'Treasury Manual','ar' => 'حركة يدوية خزينة'],
                'category' => ['en' => 'Treasury',       'ar' => 'الخزينة'],
            ],
            [
                'name'     => 'treasury.review',
                'title'    => ['en' => 'Treasury Review','ar' => 'مراجعة فترات الخزينة'],
                'category' => ['en' => 'Treasury',       'ar' => 'الخزينة'],
            ],
            [
                'name'     => 'crm',
                'title'    => ['en' => 'CRM',            'ar' => 'إدارة علاقات العملاء'],
                'category' => ['en' => 'Modules',        'ar' => 'الموديولات'],
            ],
            [
                'name'     => 'user_management',
                'title'    => ['en' => 'User Management','ar' => 'إدارة المستخدمين'],
                'category' => ['en' => 'Modules',        'ar' => 'الموديولات'],
            ],
            [
                'name'     => 'security_control',
                'title'    => ['en' => 'Security Control','ar' => 'السرية والتحكم'],
                'category' => ['en' => 'Modules',        'ar' => 'الموديولات'],
            ],
            [
                'name'     => 'reports',
                'title'    => ['en' => 'Reports',        'ar' => 'التقارير'],
                'category' => ['en' => 'Modules',        'ar' => 'الموديولات'],
            ],
            [
                'name'     => 'settings',
                'title'    => ['en' => 'Settings',       'ar' => 'الإعدادات'],
                'category' => ['en' => 'Modules',        'ar' => 'الموديولات'],
            ],

        ];

        $this->call([
            \Database\Seeders\permissions\EmployeesPermissionSeeder::class,
        ]);

        foreach ($permissions as $permissionData) {
            if (!Permission::where('name', $permissionData['name'])->exists()) {
                Permission::create($permissionData);
            }
        }
    }
}
