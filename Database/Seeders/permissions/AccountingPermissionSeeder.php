<?php

namespace Database\Seeders\permissions;

use Illuminate\Database\Seeder;
use App\Models\Permission;

class AccountingPermissionSeeder extends Seeder
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
                'name'     => 'expenses_view',
                'title'    => ['en' => 'Expenses', 'ar' => 'المصروفات'],
                'category' => ['en' => 'accounting', 'ar' => 'الحسابات'],
            ],
            [
                'name'     => 'income_view',
                'title'    => ['en' => 'Income', 'ar' => 'الإيرادات'],
                'category' => ['en' => 'accounting', 'ar' => 'الحسابات'],
            ],
            [
                'name'     => 'commissions_view',
                'title'    => ['en' => 'Commissions', 'ar' => 'العمولات'],
                'category' => ['en' => 'accounting', 'ar' => 'الحسابات'],
            ],
            [
                'name'     => 'commissions_create',
                'title'    => ['en' => 'Add Commission Settlement', 'ar' => 'إضافة تسوية عمولات'],
                'category' => ['en' => 'accounting', 'ar' => 'الحسابات'],
            ],
            [
                'name'     => 'commissions_pay',
                'title'    => ['en' => 'Pay Commission', 'ar' => 'صرف عمولات'],
                'category' => ['en' => 'accounting', 'ar' => 'الحسابات'],
            ],
            [
                'name'     => 'commissions_cancel',
                'title'    => ['en' => 'Cancel Commission Settlement', 'ar' => 'إلغاء تسوية عمولات'],
                'category' => ['en' => 'accounting', 'ar' => 'الحسابات'],
            ],
            [
                'name'     => 'commissions_delete',
                'title'    => ['en' => 'Delete Commission Settlement', 'ar' => 'حذف تسوية عمولات'],
                'category' => ['en' => 'accounting', 'ar' => 'الحسابات'],
            ],
            [
                'name'     => 'commissions_print',
                'title'    => ['en' => 'Print Commission Settlement', 'ar' => 'طباعة تسوية عمولات'],
                'category' => ['en' => 'accounting', 'ar' => 'الحسابات'],
            ],
            [
                'name'     => 'commissions_extract',
                'title'    => ['en' => 'Extract/Preview Commissions', 'ar' => 'استخراج/معاينة العمولات'],
                'category' => ['en' => 'accounting', 'ar' => 'الحسابات'],
            ],
            [
                'name'     => 'expenses_create',
                'title'    => ['en' => 'Add Expense', 'ar' => 'إضافة مصروف'],
                'category' => ['en' => 'accounting', 'ar' => 'الحسابات'],
            ],
            [
                'name'     => 'expenses_edit',
                'title'    => ['en' => 'Edit Expense', 'ar' => 'تعديل مصروف'],
                'category' => ['en' => 'accounting', 'ar' => 'الحسابات'],
            ],
            [
                'name'     => 'expenses_delete',
                'title'    => ['en' => 'Delete Expense', 'ar' => 'حذف مصروف'],
                'category' => ['en' => 'accounting', 'ar' => 'الحسابات'],
            ],
            [
                'name'     => 'income_create',
                'title'    => ['en' => 'Add Income', 'ar' => 'إضافة إيراد'],
                'category' => ['en' => 'accounting', 'ar' => 'الحسابات'],
            ],
            [
                'name'     => 'income_edit',
                'title'    => ['en' => 'Edit Income', 'ar' => 'تعديل إيراد'],
                'category' => ['en' => 'accounting', 'ar' => 'الحسابات'],
            ],
            [
                'name'     => 'income_delete',
                'title'    => ['en' => 'Delete Income', 'ar' => 'حذف إيراد'],
                'category' => ['en' => 'accounting', 'ar' => 'الحسابات'],
            ],
        ];



        foreach ($permissions as $permissionData) {
            if (!Permission::where('name', $permissionData['name'])->exists()) {
                Permission::create($permissionData);
            }
        }
    }
}
