<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class BranchAccessScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        // خارج الـ session أو في الـ console لا نطبق القيد
        if (!Auth::check()) {
            return;
        }

        $user = Auth::user();

        // لو المستخدم غير مربوط بموظف => يرى كل الفروع (مثلاً super admin)
        if (!$user->employee_id) {
            return;
        }

        // جلب الفروع المرتبطة بالموظف (primary + أخرى)
        $branchIds = DB::table('employee_branch')
            ->where('employee_id', $user->employee_id)
            ->pluck('branch_id')
            ->toArray();

        if (!empty($branchIds)) {
            $builder->whereIn($model->getTable() . '.id', $branchIds);
        } else {
            // لو الموظف غير مربوط بأي فرع => لا يرى شيئاً
            $builder->whereRaw('1 = 0');
        }
    }
}
