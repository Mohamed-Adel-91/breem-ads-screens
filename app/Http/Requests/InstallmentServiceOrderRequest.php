<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InstallmentServiceOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $dealing = (int) $this->input('dealing_type');

        $base = [
            'dealing_type'   => ['required', Rule::in([1, 2])],
            'branch_id'      => ['required', 'integer', 'exists:service_centers,id'],
            'brand_id'       => ['required', 'integer', 'exists:brands,id'],
            'car_model_id'   => ['required', 'integer', 'exists:car_models,id'],
            'term_id'        => ['required', 'integer', 'exists:car_terms,id'],
            'program_id'     => ['required', 'integer', 'exists:installment_programs,id'],
            'tenor_months'   => ['required', 'integer', 'in:12,24,36,48,60'],
            'car_price'      => ['required', 'numeric', 'min:0'],
            'down_payment'   => ['nullable', 'numeric', 'min:0'],
            'down_payment_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'car_owned_by_other' => ['nullable', 'integer', 'in:1,2'],
            'agreed_terms'       => ['accepted'],
        ];

        if ($dealing == 1) {
            $personal = [
                'full_name'   => ['required', 'string', 'max:255'],
                'phone'       => ['required', 'string', 'max:30'],
                'email'       => ['required', 'email', 'max:255'],
                'national_id' => ['required', 'string', 'max:50', 'regex:/^\d{14}$/'],
                'front_national_id_image' => ['required', 'file', 'mimes:jpg,jpeg,png,gif', 'max:4096'],
                'back_national_id_image'  => ['required', 'file', 'mimes:jpg,jpeg,png,gif', 'max:4096'],
                'bank_statement'          => ['required', 'file', 'mimes:jpg,jpeg,png,gif,pdf', 'max:8192'],
                'hr_letter'               => ['required', 'file', 'mimes:jpg,jpeg,png,gif,pdf', 'max:8192'],
            ];
        } else if ($dealing == 2) {
            $company = [
                'company_name'                   => ['required', 'string', 'max:255'],
                'representative_phone'           => ['required', 'string', 'max:30'],
                'company_email'                  => ['required', 'email', 'max:255'],
                'commercial_registration_number' => ['required', 'string', 'max:100'],
                'commercial_registration_image'  => ['required', 'file', 'mimes:jpg,jpeg,png,gif', 'max:8192'],
                'tax_card_image'                 => ['required', 'file', 'mimes:jpg,jpeg,png,gif', 'max:8192'],
                'company_bank_statement'         => ['required', 'file', 'mimes:jpg,jpeg,png,gif,pdf', 'max:8192'],
            ];
        }
        return $dealing === 2 ? ($base + $company) : ($base + $personal);
    }

    public function attributes(): array
    {
        return [
            'dealing_type'   => 'نوع التعامل',
            'branch_id'      => 'الفرع',
            'brand_id'       => 'الماركة',
            'car_model_id'   => 'الموديل',
            'term_id'        => 'الفئة',
            'program_id'     => 'برنامج التقسيط',
            'tenor_months'   => 'مدة التقسيط (بالأشهر)',
            'car_price'      => 'سعر السيارة',
            'down_payment'   => 'المقدم',
            'down_payment_percent' => 'نسبة المقدم',
            'car_owned_by_other' => 'امتلاك السيارة من طرف آخر',
            'full_name'      => 'الاسم الكامل',
            'phone'          => 'رقم الهاتف',
            'email'          => 'البريد الإلكتروني',
            'national_id'    => 'الرقم القومي',
            'front_national_id_image' => 'صورة البطاقة (الوجه الأمامي)',
            'back_national_id_image'  => 'صورة البطاقة (الوجه الخلفي)',
            'bank_statement' => 'كشف الحساب البنكي',
            'hr_letter'      => 'خطاب الموارد البشرية',
            'company_name'   => 'اسم الشركة',
            'representative_phone' => 'رقم هاتف الممثل',
            'company_email'  => 'البريد الإلكتروني للشركة',
            'commercial_registration_number' => 'رقم السجل التجاري',
            'commercial_registration_image'  => 'صورة السجل التجاري',
            'tax_card_image' => 'صورة البطاقة الضريبية',
            'company_bank_statement' => 'كشف حساب الشركة البنكي',
            'agreed_terms'   => 'الشروط والأحكام',
        ];
    }

    public function messages(): array
    {
        return [
            'agreed_terms.accepted' => 'يجب الموافقة على الشروط والأحكام.',
            'dealing_type.in'       => 'نوع التعامل غير صحيح.',
            'required'              => 'حقل :attribute مطلوب.',
            'email'                 => 'يجب أن يكون :attribute بريدًا إلكترونيًا صالحًا.',
            'max'                   => 'قيمة :attribute يجب ألا تتجاوز :max.',
            'min'                   => 'قيمة :attribute يجب ألا تقل عن :min.',
            'numeric'               => 'يجب أن يكون :attribute رقمًا.',
            'integer'               => 'يجب أن يكون :attribute عددًا صحيحًا.',
            'file'                  => 'يجب أن يكون :attribute ملفًا صحيحًا.',
            'mimes'                 => 'يجب أن يكون :attribute من نوع: :values.',
            'in'                    => 'القيمة المختارة لـ :attribute غير صحيحة.',
            'national_id.regex'     => 'رقم الهوية غير صحيح (يجب أن يكون 14 رقماً).',
        ];
    }
}
