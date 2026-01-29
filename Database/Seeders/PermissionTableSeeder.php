<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
class PermissionTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function deletePermissionsData()
    {
        // حذف جميع الأدوار والصلاحيات
        Role::truncate();
        Permission::truncate();

        // يمكنك أيضًا استخدام الأمر التالي إذا كنت تريد حذف الأدوار والصلاحيات مع حذف العلاقات الفرعية مثل الأذونات المعينة للأدوار.
        // Role::query()->delete();
        // Permission::query()->delete();

        return 'تم حذف البيانات من جداول الصلاحيات بنجاح.';
    }
    public function run()
    {
        $permissions = [
            // Programs
            [
                'name' => 'Dashboard',
                'title' => ['en' => 'Dashboard', 'ar' => 'الداش بور'],
                'category' => ['en' => 'Programs', 'ar' => 'البرامج'],
            ],
   
            [
                'name' => 'settings',
                'title' => ['en' => 'Settings', 'ar' => 'الاعدادات'],
                'category' => ['en' => 'Programs', 'ar' => 'البرامج'],
            ],
            [
                'name' => 'users',
                'title' => ['en' => 'User system', 'ar' => 'نظام المستخدمين'],
                'category' => ['en' => 'Programs', 'ar' => 'البرامج'],
            ],
        
            // permissions

            [
                'name' => 'role-list',
                'title' => ['en' => 'role list', 'ar' => 'قائمة الصلاحيات'],
                'category' => ['en' => 'permissions', 'ar' => 'الصلاحيات'],
            ],
            [
                'name' => 'role-create',
                'title' => ['en' => 'role create', 'ar' => 'انشاء صلاحيات'],
                'category' => ['en' => 'permissions', 'ar' => 'الصلاحيات'],
            ],
            [
                'name' => 'role-edit',
                'title' => ['en' => 'role edit', 'ar' => 'تعديل صلاحيات'],
                'category' => ['en' => 'permissions', 'ar' => 'الصلاحيات'],
            ],
            [
                'name' => 'role-delete',
                'title' => ['en' => 'role delete', 'ar' => 'حذف صلاحيات'],
                'category' => ['en' => 'permissions', 'ar' => 'الصلاحيات'],
            ],
            [
                'name' => 'role-show',
                'title' => ['en' => 'role show', 'ar' => 'عرض صلاحيات'],
                'category' => ['en' => 'permissions', 'ar' => 'الصلاحيات'],
            ],

            // Users

            [
                'name' => 'users-list',
                'title' => ['en' => 'users list', 'ar' => 'قائمة المستخدمين'],
                'category' => ['en' => 'Users', 'ar' => 'المستخدمين'],
            ],
            [
                'name' => 'users-create',
                'title' => ['en' => 'users create', 'ar' => 'انشاء المستخدمين'],
                'category' => ['en' => 'Users', 'ar' => 'المستخدمين'],
            ],
            [
                'name' => 'users-edit',
                'title' => ['en' => 'users edit', 'ar' => 'تعديل المستخدمين'],
                'category' => ['en' => 'Users', 'ar' => 'المستخدمين'],
            ],
            [
                'name' => 'users-delete',
                'title' => ['en' => 'users delete', 'ar' => 'حذف المستخدمين'],
                'category' => ['en' => 'Users', 'ar' => 'المستخدمين'],
            ],
            [
                'name' => 'users-show',
                'title' => ['en' => 'users show', 'ar' => 'عرض المستخدمين'],
                'category' => ['en' => 'Users', 'ar' => 'المستخدمين'],
            ],

             [
                'name' => 'users-list',
                'title' => ['en' => 'users list', 'ar' => 'قائمة المستخدمين'],
                'category' => ['en' => 'Users', 'ar' => 'المستخدمين'],
            ],
            
      
        ];

        foreach ($permissions as $permissionData) {
            $permission = Permission::where('name', $permissionData['name'])->first();

            if (!$permission) {
                Permission::create([
                    'name' => $permissionData['name'],
                    'title' => $permissionData['title'],
                    'category' => $permissionData['category'],
                ]);
            }
        }
    }
}