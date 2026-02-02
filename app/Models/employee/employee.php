<?php

namespace App\Models\employee;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class employee extends Model
{
    use SoftDeletes;

    protected $table = 'employees';
    public $timestamps = true;

    protected $fillable = [
        'code',
        'first_name',
        'last_name',
        'job_id',
        'photo',
        'gender',
        'birth_date',
        'phone_1',
        'phone_2',
        'whatsapp',
        'email',
        'specialization',
        'years_experience',
        'bio',
        'compensation_type',
        'base_salary',
        'commission_percent',
        'commission_fixed',
        'salary_transfer_method',
        'salary_transfer_details',
        'status',
        'user_add',
        'is_coach'
    ];

    protected $dates = ['deleted_at'];

    public function job()
    {
        return $this->belongsTo(\App\Models\employee\Job::class, 'job_id');
    }

    public function branches()
    {
        return $this->belongsToMany(\App\Models\general\Branch::class, 'employee_branch', 'employee_id', 'branch_id')
            ->withPivot(['is_primary'])
            ->withTimestamps();
    }

    public function primaryBranch()
    {
        return $this->branches()->wherePivot('is_primary', 1);
    }

    public function getFullNameAttribute()
    {
        return trim(($this->first_name ?? '') . ' ' . ($this->last_name ?? ''));
    }
}
