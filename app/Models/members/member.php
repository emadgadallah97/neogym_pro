<?php

namespace App\Models\members;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

class Member extends Model
{
    use SoftDeletes;

    protected $table = 'members';
    public $timestamps = true;

    protected $fillable = [
        'member_code',
        'branch_id',
        'first_name',
        'last_name',
        'gender',
        'birth_date',
        'phone',
        'phone2',
        'whatsapp',
        'email',
        'address',
        'id_government',
        'id_city',
        'id_area',
        'join_date',
        'status',
        'freeze_from',
        'freeze_to',
        'height',
        'weight',
        'medical_conditions',
        'allergies',
        'notes',
        'photo',
        'user_add',
        'user_update',
    ];

    protected $casts = [
        'birth_date' => 'date',
        'join_date' => 'date',
        'freeze_from' => 'date',
        'freeze_to' => 'date',
        'height' => 'decimal:2',
        'weight' => 'decimal:2',
    ];

    public function branch()
    {
        return $this->belongsTo(\App\Models\general\Branch::class, 'branch_id');
    }

    public function government()
    {
        return $this->belongsTo(\App\models\government::class, 'id_government');
    }

    public function city()
    {
        return $this->belongsTo(\App\models\City::class, 'id_city');
    }

    public function area()
    {
        return $this->belongsTo(\App\models\area::class, 'id_area');
    }

    public function getFullNameAttribute()
    {
        return trim(($this->first_name ?? '') . ' ' . ($this->last_name ?? ''));
    }

    public function getIsFrozenNowAttribute(): bool
    {
        if (($this->status ?? '') !== 'frozen') {
            return false;
        }

        if (empty($this->freeze_from) || empty($this->freeze_to)) {
            return false;
        }

        $today = Carbon::today();
        return $today->between($this->freeze_from, $this->freeze_to);
    }

    public function getPublicPhotoUrlAttribute()
    {
        return !empty($this->photo) ? url($this->photo) : null;
    }
}
