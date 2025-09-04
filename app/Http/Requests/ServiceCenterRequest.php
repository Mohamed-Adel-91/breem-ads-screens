<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ServiceCenterRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'brand_id' => ['required','exists:brands,id'],
            'name' => ['required','string','max:255'],
            'location' => ['nullable','string'],
            'address' => ['required','string','max:255'],
            'phone' => ['required','string','max:255'],
            'city' => ['required','in:' . implode(',', \App\Enums\CityEnum::getValues())],
        ];
    }
}
