<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FeatureRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $id = $this->route('feature');
        $unique = 'unique:features,name' . ($id ? ',' . $id : '');
        return [
            'feature_category_id' => ['required','exists:feature_categories,id'],
            'name' => ['required','string','max:255',$unique],
            'value' => ['nullable','string'],
            'status' => ['nullable','boolean'],
        ];
    }
}
