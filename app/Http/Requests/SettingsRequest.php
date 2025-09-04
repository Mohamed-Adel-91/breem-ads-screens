<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SettingsRequest extends FormRequest
{
   public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'email' => 'nullable|email',
            'hr_mail' => 'nullable|email',
            'customer_service_mail' => 'nullable|email',
            'phone' => 'nullable|string|max:255',
            'hotline' => 'nullable|string|max:255',
            'address.en' => 'nullable|string|max:255',
            'address.ar' => 'nullable|string|max:255',
            'slogan.en' => 'nullable|string|max:255',
            'slogan.ar' => 'nullable|string|max:255',
            'facebook' => 'nullable|url',
            'youtube' => 'nullable|url',
            'instagram' => 'nullable|url',
            'linkedin' => 'nullable|url',
            'location' => 'nullable|url',
        ];
    }
}
