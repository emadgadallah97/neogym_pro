<?php

namespace App;

use App\Models\employee\employee;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use Notifiable;
    use HasRoles;

    protected $fillable = [
        'name',
        'email',
        'password',
        'roles_name',
        'Status',
        'branch_id',
        'employee_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'roles_name' => 'array',
        'branch_id' => 'integer',
    ];

    public function branch()
    {
        return $this->belongsTo(\App\Models\general\Branch::class, 'branch_id');
    }

    /**
     * ✅ العلاقة مع الموظف
     */
    public function employee()
    {
        return $this->belongsTo(employee::class, 'employee_id');
    }

    /**
     * ✅ جلب الفروع المتاحة للمستخدم (للاستخدام البرمجي إذا احتجت)
     */
    public function accessibleBranchIds(): array
    {
        if (!$this->employee_id) {
            return []; // سيُفسَّر على أنه "كل الفروع" في الـ Scope
        }

        return DB::table('employee_branch')
            ->where('employee_id', $this->employee_id)
            ->pluck('branch_id')
            ->toArray();
    }
}
