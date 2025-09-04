<?php

namespace App\Http\Requests;

use App\Models\KnowMore;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class KnowMoreRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->video)) {
            $this->merge(['video' => trim($this->video)]);
        }
    }

    public function rules()
    {
        $id = $this->route('know_more');
        $isFile = $this->hasFile('video');

        $videoRules = ['nullable'];
        if ($isFile) {
            $videoRules = array_merge($videoRules, [
                'file',
                'mimetypes:video/mp4,video/mpeg,video/avi',
                'max:51200',
            ]);
        } else {
            $videoRules = array_merge($videoRules, [
                'string',
                'max:2048',
                'url',
                'regex:/^(https?:\/\/)?(www\.)?(youtube\.com|youtu\.be)\/.+$/i',
            ]);
        }

        return [
            'car_model_id' => ['required', 'exists:car_models,id'],
            'video_source' => ['nullable', Rule::in(['upload', 'youtube'])],
            'video' => $videoRules,
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,gif,webp', 'max:5000'],
            'title' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'hero_section' => ['nullable', 'boolean', function ($attribute, $value, $fail) use ($id) {
                if ($value) {
                    $exists = KnowMore::where('car_model_id', $this->car_model_id)
                        ->where('hero_section', true)
                        ->when($id, fn($q) => $q->where('id', '!=', $id))
                        ->exists();
                    if ($exists) {
                        $fail(__('admin.messages.hero_fail'));
                    }
                }
            }],
        ];
    }
}
