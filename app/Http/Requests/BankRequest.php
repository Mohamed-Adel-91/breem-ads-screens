<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BankRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $id = $this->route('bank');
        $unique = 'unique:banks,name' . ($id ? ',' . $id : '');
        return [
            'name' => ['required', 'string', 'max:255', $unique],
        ];
    }
}
