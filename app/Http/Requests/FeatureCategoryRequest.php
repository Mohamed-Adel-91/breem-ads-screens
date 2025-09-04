<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FeatureCategoryRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $id = $this->route('feature_category');
        $unique = 'unique:feature_categories,name' . ($id ? ',' . $id : '');
        return [
            'name' => ['required','string','max:255',$unique],
        ];
    }
}
