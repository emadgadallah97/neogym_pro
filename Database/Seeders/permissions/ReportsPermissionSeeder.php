<?php

namespace Database\Seeders\permissions;

use Illuminate\Database\Seeder;
use App\Models\Permission;

class ReportsPermissionSeeder extends Seeder
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
            // ── Universal Actions ──────────────────
            [
                'name'     => 'reports_print',
                'title'    => ['en' => 'Reports - Print', 'ar' => 'طباعة التقارير'],
                'category' => ['en' => 'reports', 'ar' => 'التقارير'],
            ],
            [
                'name'     => 'reports_export',
                'title'    => ['en' => 'Reports - Export Excel', 'ar' => 'تصدير التقارير إكسيل'],
                'category' => ['en' => 'reports', 'ar' => 'التقارير'],
            ],
            // ── Specific Views ─────────────────────
            [
                'name'     => 'reports_attendances_view',
                'title'    => ['en' => 'Reports - Attendances', 'ar' => 'تقرير الحضور'],
                'category' => ['en' => 'reports', 'ar' => 'التقارير'],
            ],
            [
                'name'     => 'reports_commissions_view',
                'title'    => ['en' => 'Reports - Commissions', 'ar' => 'تقرير العمولات'],
                'category' => ['en' => 'reports', 'ar' => 'التقارير'],
            ],
            [
                'name'     => 'reports_employees_view',
                'title'    => ['en' => 'Reports - Employees', 'ar' => 'تقرير الموظفين'],
                'category' => ['en' => 'reports', 'ar' => 'التقارير'],
            ],
            [
                'name'     => 'reports_members_view',
                'title'    => ['en' => 'Reports - Members', 'ar' => 'تقرير الأعضاء'],
                'category' => ['en' => 'reports', 'ar' => 'التقارير'],
            ],
            [
                'name'     => 'reports_payments_view',
                'title'    => ['en' => 'Reports - Payments', 'ar' => 'تقرير المدفوعات'],
                'category' => ['en' => 'reports', 'ar' => 'التقارير'],
            ],
            [
                'name'     => 'reports_pt_addons_view',
                'title'    => ['en' => 'Reports - PT Sessions', 'ar' => 'تقرير الاشتراكات الخاصة'],
                'category' => ['en' => 'reports', 'ar' => 'التقارير'],
            ],
            [
                'name'     => 'reports_sales_view',
                'title'    => ['en' => 'Reports - Sales', 'ar' => 'تقرير المبيعات'],
                'category' => ['en' => 'reports', 'ar' => 'التقارير'],
            ],
            [
                'name'     => 'reports_subscriptions_view',
                'title'    => ['en' => 'Reports - Subscriptions', 'ar' => 'تقرير الخطط والاشتراكات'],
                'category' => ['en' => 'reports', 'ar' => 'التقارير'],
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
