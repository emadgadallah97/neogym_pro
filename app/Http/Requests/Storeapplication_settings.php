<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class Storeapplication_settings extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'name_ar' => 'required',
            'name_en' => 'required',
            'Id_settings_type' => 'required',
            
        ];
    }
    public function messages()
    {
        return [
            'name_ar.required' =>  trans('setting_type_trans.Name_ar_required') ,
            'name_en.required' =>  trans('setting_type_trans.Name_en_required') ,
            'description.required' =>  trans('setting_type_trans.email_required') ,  
        ];
    }
}
