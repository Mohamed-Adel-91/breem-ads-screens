<?php

namespace App\Http\Requests\Admin\Ads;

use App\Support\CreativeMedia;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Editing an advertisement's content.
 *
 * `status` and `approved_by` are absent for the same reason as in StoreAdRequest:
 * this request edits content, and content editing is not approval authority. An
 * edit that changes what a screen would actually play sends a reviewed ad back to
 * `pending` — see AdController::update().
 */
class UpdateAdRequest extends FormRequest
{
    use ValidatesCreativeUpload;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['nullable', 'array'],
            'title.en' => ['nullable', 'string', 'max:255'],
            'title.ar' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'array'],
            'description.en' => ['nullable', 'string'],
            'description.ar' => ['nullable', 'string'],
            'creative' => [
                'nullable',
                'file',
                'mimetypes:'.CreativeMedia::allowedMimeTypeList(),
                'max:'.CreativeMedia::absoluteMaxKilobytes(),
            ],
            'duration_seconds' => ['nullable', 'integer', 'min:0'],
            'created_by' => ['required', 'exists:users,id'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'screens' => ['nullable', 'array'],
            'screens.*' => ['integer', 'exists:screens,id'],
            'play_order' => ['nullable', 'array'],
            'play_order.*' => ['nullable', 'integer', 'min:0'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $this->validateCreativeSize($validator);
    }
}
