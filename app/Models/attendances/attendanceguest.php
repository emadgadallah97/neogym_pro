<?php

namespace App\Models\attendances;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class attendanceguest extends Model
{
    use SoftDeletes;

    protected $table = 'attendance_guests';

    protected $fillable = [
        'attendance_id',
        'guest_name',
        'guest_phone',
        'notes',
        'user_add',
    ];

    public function attendance()
    {
        return $this->belongsTo(attendance::class, 'attendance_id');
    }
}
