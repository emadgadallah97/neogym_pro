<?php

namespace App\Http\Requests\attendances;

use Illuminate\Foundation\Http\FormRequest;

class AttendanceManualRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'member_code' => ['required', 'string', 'max:100'],
            'deduct_pt' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
