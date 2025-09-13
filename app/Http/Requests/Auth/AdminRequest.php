<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AdminRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $isUpdate = $this->method() === 'PUT' || $this->method() === 'PATCH';
        $id = $this->route('admin');
        return [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name'  => ['required', 'string', 'max:255'],
            'email'      => ['required', 'email', 'max:255', 'unique:admins,email' . ($isUpdate ? ',' . $id : '')],
            'password'   => [$isUpdate ? 'nullable' : 'required', 'string', 'min:8', 'confirmed'],
            'mobile'     => ['nullable', 'string', 'max:255'],
            'profile_picture' => ['nullable', 'image', 'mimes:jpg,jpeg,png,gif,webp', 'max:5000'],
            'roles'      => ['nullable', 'array'],
            'roles.*'    => ['integer', Rule::exists('roles', 'id')->where('guard_name', 'admin')],
            'permissions'   => ['nullable', 'array'],
            'permissions.*' => ['integer', Rule::exists('permissions', 'id')->where('guard_name', 'admin')],
        ];
    }
}
