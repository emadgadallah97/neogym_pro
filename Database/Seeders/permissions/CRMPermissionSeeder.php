<?php

namespace Database\Seeders\permissions;

use Illuminate\Database\Seeder;
use App\Models\Permission;

class CRMPermissionSeeder extends Seeder
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
            // ── Dashboard ─────────────────────────
            [
                'name'     => 'crm_dashboard_view',
                'title'    => ['en' => 'CRM - Dashboard', 'ar' => 'عرض لوحة تحكم CRM'],
                'category' => ['en' => 'crm', 'ar' => 'سي آر إم'],
            ],
            // ── Prospects ─────────────────────────
            [
                'name'     => 'crm_prospects_view',
                'title'    => ['en' => 'CRM - View Prospects', 'ar' => 'عرض العملاء المحتملين'],
                'category' => ['en' => 'crm', 'ar' => 'سي آر إم'],
            ],
            [
                'name'     => 'crm_prospects_create',
                'title'    => ['en' => 'CRM - Create/Import Prospects', 'ar' => 'إضافة/استيراد عملاء محتملين'],
                'category' => ['en' => 'crm', 'ar' => 'سي آر إم'],
            ],
            [
                'name'     => 'crm_prospects_edit',
                'title'    => ['en' => 'CRM - Edit Prospects', 'ar' => 'تعديل عملاء محتملين'],
                'category' => ['en' => 'crm', 'ar' => 'سي آر إم'],
            ],
            [
                'name'     => 'crm_prospects_delete',
                'title'    => ['en' => 'CRM - Delete Prospects', 'ar' => 'حذف عملاء محتملين'],
                'category' => ['en' => 'crm', 'ar' => 'سي آر إم'],
            ],
            [
                'name'     => 'crm_prospects_convert',
                'title'    => ['en' => 'CRM - Convert Prospect to Member', 'ar' => 'تحويل العميل إلى عضو'],
                'category' => ['en' => 'crm', 'ar' => 'سي آر إم'],
            ],
            [
                'name'     => 'crm_prospects_disqualify',
                'title'    => ['en' => 'CRM - Disqualify Prospect', 'ar' => 'استبعاد عميل محتمل'],
                'category' => ['en' => 'crm', 'ar' => 'سي آر إم'],
            ],
            // ── Members (CRM) ─────────────────────
            [
                'name'     => 'crm_members_view',
                'title'    => ['en' => 'CRM - View Member Segments', 'ar' => 'عرض شرائح الأعضاء (CRM)'],
                'category' => ['en' => 'crm', 'ar' => 'سي آر إم'],
            ],
            // ── Follow-ups ────────────────────────
            [
                'name'     => 'crm_followups_view',
                'title'    => ['en' => 'CRM - View Follow-ups', 'ar' => 'عرض المتابعات'],
                'category' => ['en' => 'crm', 'ar' => 'سي آر إم'],
            ],
            [
                'name'     => 'crm_followups_create',
                'title'    => ['en' => 'CRM - Create Follow-up', 'ar' => 'إضافة متابعة'],
                'category' => ['en' => 'crm', 'ar' => 'سي آر إم'],
            ],
            [
                'name'     => 'crm_followups_edit',
                'title'    => ['en' => 'CRM - Edit Follow-up', 'ar' => 'تعديل متابعة'],
                'category' => ['en' => 'crm', 'ar' => 'سي آر إم'],
            ],
            [
                'name'     => 'crm_followups_delete',
                'title'    => ['en' => 'CRM - Delete Follow-up', 'ar' => 'حذف متابعة'],
                'category' => ['en' => 'crm', 'ar' => 'سي آر إم'],
            ],
            [
                'name'     => 'crm_followups_mark_done',
                'title'    => ['en' => 'CRM - Mark Follow-up Done', 'ar' => 'إتمام المتابعة'],
                'category' => ['en' => 'crm', 'ar' => 'سي آر إم'],
            ],
            // ── Interactions ──────────────────────
            [
                'name'     => 'crm_interactions_create',
                'title'    => ['en' => 'CRM - Create Interaction', 'ar' => 'إضافة تفاعل'],
                'category' => ['en' => 'crm', 'ar' => 'سي آر إم'],
            ],
            [
                'name'     => 'crm_interactions_delete',
                'title'    => ['en' => 'CRM - Delete Interaction', 'ar' => 'حذف تفاعل'],
                'category' => ['en' => 'crm', 'ar' => 'سي آر إم'],
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
