<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreOrderRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'car_term_id'     => ['required', 'exists:car_terms,id'],

            'first_color_id'  => [
                'required',
                Rule::exists('car_term_color', 'color_id')
                    ->where('car_term_id', $this->input('car_term_id')),
            ],

            'second_color_id' => [
                'required',
                Rule::exists('car_term_color', 'color_id')
                    ->where('car_term_id', $this->input('car_term_id')),
            ],

            'type'            => ['required', 'in:0,1'], // 0: cash, 1: installment
            'cash_method'     => ['required_if:type,0', 'in:0,1,2'], // 0: visa, 1: fawry, 2: wallet
            'account_number' => ['required_if:type,0', 'string', 'max:255'],
            'bank_id'         => ['required_if:type,1', 'exists:banks,id'],

        ];
    }
}
