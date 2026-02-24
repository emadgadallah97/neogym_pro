<?php

namespace App\Models\hr;

use Illuminate\Database\Eloquent\Model;

class HrShift extends Model
{
    protected $table = 'hr_shifts';
    public $timestamps = true;

    protected $fillable = [
        'name',
        'start_time',
        'end_time',
        'grace_minutes',
        'min_half_hours',
        'min_full_hours',
        'sun','mon','tue','wed','thu','fri','sat',
        'status',
        'user_add',
    ];

    protected $casts = [
        'sun' => 'boolean','mon' => 'boolean','tue' => 'boolean','wed' => 'boolean',
        'thu' => 'boolean','fri' => 'boolean','sat' => 'boolean',
        'status' => 'boolean',
    ];
}
