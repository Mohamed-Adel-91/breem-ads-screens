<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class InsuranceOrderRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'user_id'            => ['required', 'exists:users,id'],
            'brand_id'      => ['required', 'exists:brands,id'],
            'car_model_id'  => ['required', 'exists:car_models,id'],
            'car_term_id'   => ['required', 'exists:car_terms,id'],
            'insurance_id'  => ['required', 'exists:insurance,id'],
            'car_price'     => ['required', 'numeric', 'min:1'],
            'annual_price_at_submission' => ['required','numeric', 'min:0'],

            'full_name'               => ['required', 'string', 'max:255'],
            'phone_number'             => ['required', 'string'],
            'individual_email'              => ['required', 'email'],
            'national_id'          => ['required', 'string', 'regex:/^\d{14}$/'],
            'front_national_id_image' => ['required', 'file', 'mimes:jpg,jpeg,png,gif', 'max:4096'],
            'back_national_id_image'  => ['required', 'file', 'mimes:jpg,jpeg,png,gif', 'max:4096'],

            'other_ownership' => ['required', 'boolean'],
            'sale_blocked'       => ['required', 'boolean'],
            'car_license_image'       => ['required', 'file', 'mimes:jpg,jpeg,png,gif', 'max:4096'],
            'car_documentation_image' => ['required', 'file', 'mimes:jpg,jpeg,png,gif', 'max:4096'],

            'chassis_number'     => ['required', 'string'],
            'agreed_terms'             => ['accepted'],
        ];
    }

    public function messages(): array
    {
        return [
            'user_id.required'      => 'المستخدم مطلوب.',
            'user_id.exists'        => 'المستخدم غير موجود.',
            'car_term_id.required'  => 'فئة السيارة مطلوبة.',
            'car_term_id.exists'    => 'الفئة غير موجودة.',
            'insurance_id.exists'   => 'شركة التأمين غير صحيحة.',

            'car_price.required'  => 'الإجمالي مطلوب.',
            'car_price.numeric'   => 'الإجمالي يجب أن يكون رقم.',
            'car_price.min'       => 'الإجمالي لا يمكن أن يكون سالب.',

            'full_name.required'    => 'الاسم مطلوب.',
            'full_name.string'      => 'الاسم يجب أن يكون نصاً.',
            'full_name.max'         => 'الاسم يجب ألا يزيد عن :max حرفاً.',

            'phone_number.required' => 'رقم الموبايل مطلوب.',
            'phone_number.string'   => 'رقم الموبايل يجب أن يكون نصاً.',

            'individual_email.email' => 'البريد الإلكتروني غير صحيح.',

            'national_id.required'  => 'رقم الهوية مطلوب.',
            'national_id.string'    => 'رقم الهوية يجب أن يكون نصاً.',
            'national_id.regex'     => 'رقم الهوية غير صحيح (يجب أن يكون 14 رقماً).',

            'front_national_id_image.image' => 'صورة بطاقة الوجه يجب أن تكون صورة.',
            'front_national_id_image.max'   => 'حجم صورة بطاقة الوجه يجب ألا يتجاوز 4 ميجابايت.',
            'back_national_id_image.image'  => 'صورة بطاقة الظهر يجب أن تكون صورة.',
            'back_national_id_image.max'    => 'حجم صورة بطاقة الظهر يجب ألا يتجاوز 4 ميجابايت.',

            'other_ownership.required' => 'برجاء تحديد هل السيارة مملوكة لشخص آخر.',
            'other_ownership.boolean'  => 'قيمة ملكية السيارة غير صحيحة.',
            'sale_blocked.required'    => 'برجاء تحديد هل عليها حظر بيع.',
            'sale_blocked.boolean'     => 'قيمة حظر البيع غير صحيحة.',

            'car_license_image.image'       => 'صورة الرخصة يجب أن تكون صورة.',
            'car_license_image.max'         => 'حجم صورة الرخصة يجب ألا يتجاوز 4 ميجابايت.',
            'car_documentation_image.image' => 'صورة مستندات السيارة يجب أن تكون صورة.',
            'car_documentation_image.max'   => 'حجم صورة مستندات السيارة يجب ألا يتجاوز 4 ميجابايت.',

            'chassis_number.required' => 'رقم الشاسيه مطلوب.',
            'chassis_number.string'   => 'رقم الشاسيه يجب أن يكون نصاً.',

            'agreed_terms.accepted'   => 'يجب الموافقة على سياسة الخصوصية.',
        ];
    }

    public function attributes(): array
    {
        return [
            'user_id'                  => 'المستخدم',
            'car_term_id'              => 'فئة السيارة',
            'insurance_id'             => 'شركة التأمين',
            'car_price'              => 'الإجمالي',
            'full_name'                => 'الاسم',
            'phone_number'             => 'رقم الموبايل',
            'individual_email'         => 'البريد الإلكتروني',
            'national_id'              => 'رقم الهوية',
            'front_national_id_image'  => 'صورة بطاقة الوجه',
            'back_national_id_image'   => 'صورة بطاقة الظهر',
            'other_ownership'          => 'ملكية السيارة',
            'sale_blocked'             => 'حظر البيع',
            'car_license_image'        => 'صورة الرخصة',
            'car_documentation_image'  => 'صورة مستندات السيارة',
            'chassis_number'           => 'رقم الشاسيه',
            'agreed_terms'             => 'سياسة الخصوصية',
        ];
    }


    protected function prepareForValidation(): void
    {
        $userId = auth()->id();
        $phone = $this->phone_number ? preg_replace('/\s+/', '', $this->phone_number) : $this->phone_number;
        $this->merge([
            'user_id'         => $userId,
            'phone_number'    => $phone,
        ]);
        if (!$this->annual_price_at_submission && $this->insurance_id) {
            $price = \App\Models\Insurance::whereKey($this->insurance_id)->value('annual_price');
            if ($price !== null) {
                $this->merge(['annual_price_at_submission' => (int) $price]);
            }
        }
    }
}
