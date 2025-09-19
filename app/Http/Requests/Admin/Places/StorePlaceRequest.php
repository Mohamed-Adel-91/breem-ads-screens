<?php

namespace App\Http\Requests\Admin\Places;

use App\Enums\PlaceType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePlaceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $types = array_map(fn(PlaceType $type) => $type->value, PlaceType::cases());

        return [
            'name' => ['required', 'array'],
            'name.en' => ['required', 'string', 'max:255'],
            'name.ar' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'array'],
            'address.en' => ['nullable', 'string', 'max:500'],
            'address.ar' => ['nullable', 'string', 'max:500'],
            'type' => ['required', Rule::in($types)],
        ];
    }
}
