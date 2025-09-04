<?php

namespace App\Http\Requests;

use App\Helpers\ApiResponse;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator;

class RegisterRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'full_name' => ['required', 'string', 'max:255'],
            'nickname'  => ['nullable', 'string', 'max:255'],
            'mobile'    => ['required', 'string', 'max:255', 'unique:users,mobile'],
            'email'     => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'address'   => ['nullable', 'string', 'max:255'],
            'image'     => ['nullable', 'image', 'mimes:jpeg,jpg,png,gif,webp', 'max:5000'],
            'password'  => ['required', 'string', 'min:8', 'confirmed'],
        ];
    }

    public function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            ApiResponse::sendResponse(422, 'انشاء حساب غير صالح', null, $validator->errors()->toArray())
        );
    }
}
