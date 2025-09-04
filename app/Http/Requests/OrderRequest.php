<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class OrderRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'user_id' => ['required','exists:users,id'],
            'car_term_id' => ['required','exists:car_terms,id'],
            'first_color_id' => ['required','exists:colors,id'],
            'second_color_id' => ['required','exists:colors,id'],
            'type' => ['nullable','integer'],
            'total_price' => ['required','numeric'],
        ];
    }
}
