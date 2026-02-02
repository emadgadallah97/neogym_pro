<?php

namespace App\Models\subscriptions;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Translatable\HasTranslations;

class subscriptions_plan extends Model
{
    use HasFactory;
    use SoftDeletes;
    use HasTranslations;

    protected $table = 'subscriptions_plans';

    public $translatable = ['name'];

    protected $fillable = [
        'code',
        'subscriptions_type_id',
        'name',
        'sessions_period_type',
        'sessions_period_other_label',
        'sessions_count',
        'duration_days',
        'allowed_training_days',
        'allow_guest',
        'guest_people_count',
        'guest_times_count',
        'guest_allowed_days',
        'notify_before_end',
        'notify_days_before_end',
        'description',
        'notes',
        'status',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'name' => 'array',
        'allowed_training_days' => 'array',
        'guest_allowed_days' => 'array',
        'allow_guest' => 'boolean',
        'notify_before_end' => 'boolean',
        'status' => 'boolean',
    ];

    public function type()
    {
        return $this->belongsTo(subscriptions_type::class, 'subscriptions_type_id');
    }
}
