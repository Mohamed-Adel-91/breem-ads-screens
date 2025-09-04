<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class VideoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title'        => 'required|string|max:255',
            'short_desc'   => 'required|string',
            'added_date'   => 'required|date',
            'link'         => 'required|url',
            'thumb_image'  => 'nullable|image|mimes:jpg,jpeg,png,gif,webp|max:5120',
            'home'         => 'sometimes|boolean',
            'hidden'       => 'sometimes|boolean',
            'ord'          => 'required|integer',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'home'   => filter_var($this->home, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE),
            'hidden' => filter_var($this->hidden, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE),
        ]);
    }

    public function attributes(): array
    {
        return [
            'title'      => 'العنوان',
            'short_desc' => 'الوصف المختصر',
            'added_date' => 'تاريخ الإضافة',
            'link'       => 'الرابط',
            'thumb_image'  => 'الصورة المصغرة',
            'home'       => 'إظهار في الرئيسية',
            'hidden'     => 'حالة الظهور',
            'ord'        => 'الترتيب',
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'حقل العنوان مطلوب.',
            'title.max'      => 'حقل العنوان يجب ألا يزيد عن 255 حرفًا.',
            'link.url'       => 'صيغة الرابط غير صحيحة.',
            'added_date.date' => 'صيغة تاريخ الإضافة غير صحيحة.',
            'thumb_image.image'    => 'ملف الصورة غير صالح.',
            'thumb_image.mimes'    => 'الصورة يجب أن تكون من الأنواع: jpg, jpeg, png, gif, webp.',
        ];
    }
}
