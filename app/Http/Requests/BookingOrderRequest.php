<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use BenSampo\Enum\Rules\EnumValue;
use App\Enums\PaymentTypeEnum;
use App\Enums\ApplicableToEnum;

class BookingOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $cleanInt = function ($v) {
            if ($v === null) return null;
            return (int)preg_replace('/[^\d]/', '', (string)$v);
        };

        $this->merge([
            'price'              => $cleanInt($this->input('price')),
            'reservation_amount' => $cleanInt($this->input('reservation_amount')),
            'agreed_terms'       => (bool)$this->boolean('agreed_terms'),
        ]);
    }

    public function rules(): array
    {
        $paymentType  = (int)$this->input('payment_type');   // 1 cash, 2 installment
        $customerType = (int)$this->input('customer_type');  // 1 individual, 2 company

        $rules = [
            'booking_car_clone_id' => ['required', 'integer', 'exists:booking_car_clones,id'],
            'car_term_id'          => ['required', 'integer', 'exists:car_terms,id'],

            'first_color_id'  => ['required', 'integer', 'exists:colors,id'],
            'second_color_id' => ['required', 'integer', 'exists:colors,id', 'different:first_color_id'],

            'branch_name'          => ['required', 'string', 'max:255'],

            'price'                => ['required', 'integer', 'min:0'],
            'reservation_amount'   => ['required', 'integer', 'min:0'],

            'payment_type'         => ['required', new EnumValue(PaymentTypeEnum::class, false)],
            'agreed_terms'         => ['accepted'],

            'down_payment_amount'  => ['nullable', 'integer', 'min:0'],
            'down_payment_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ];

        // CASH
        if ($paymentType === PaymentTypeEnum::CASH) {
            $rules += [
                'cash_full_name'              => ['required', 'string', 'max:255'],
                'cash_phone_number'           => ['required', 'string', 'max:30'],
                'cash_individual_email'       => ['required', 'email'],
                'cash_national_id'            => ['required', 'string', 'max:50', 'regex:/^\d{14}$/',],
                'cash_front_national_id_image' => ['required', 'image', 'mimes:jpeg,png,jpg', 'max:2048'],
                'cash_back_national_id_image' => ['required', 'image', 'mimes:jpeg,png,jpg', 'max:2048'],
            ];
        }

        // INSTALLMENT
        if ($paymentType === PaymentTypeEnum::INSTALLMENT) {
            $rules += [
                'customer_type'        => ['required', new EnumValue(ApplicableToEnum::class, false)],
                'bank_id'              => ['required', 'integer', 'not_in:0'],
                'installment_duration' => ['required', 'integer', Rule::in([12, 24, 36, 48, 60])],
            ];

            if ($customerType === ApplicableToEnum::INDIVIDUAL) {
                $rules += [
                    'installment_full_name'        => ['required', 'string', 'max:255'],
                    'installment_phone_number'     => ['required', 'string', 'max:30'],
                    'installment_individual_email' => ['required', 'email'],
                    'installment_national_id'      => ['required', 'string', 'max:50', 'regex:/^\d{14}$/'],
                    'installment_front_national_id_image' => ['required', 'image', 'mimes:jpeg,png,jpg', 'max:4096'],
                    'installment_back_national_id_image'  => ['required', 'image', 'mimes:jpeg,png,jpg', 'max:4096'],
                    'installment_bank_statement'          => ['required', 'file', 'max:8192'],
                    'installment_hr_letter'               => ['required', 'file', 'max:8192'],
                ];
            }

            if ($customerType === ApplicableToEnum::COMPANY) {
                $rules += [
                    'installment_company_name'                       => ['required', 'string', 'max:255'],
                    'installment_legal_representative_phone_number'  => ['required', 'string', 'max:30'],
                    'installment_company_email'                      => ['required', 'email'],
                    'installment_commercial_registration_number'     => ['required', 'string', 'max:100'],
                    'installment_tax_card_number'                    => ['required', 'string', 'max:100'],
                    'installment_commercial_registration_image'      => ['required', 'image', 'mimes:jpeg,png,jpg', 'max:4096'],
                    'installment_tax_card_image'                     => ['required', 'image', 'mimes:jpeg,png,jpg', 'max:4096'],
                    'installment_company_bank_statement'             => ['required', 'file', 'max:8192'],
                ];
            }
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'required' => 'الحقل :attribute مطلوب.',
            'required_if' => 'الحقل :attribute مطلوب.',
            'exists' => 'القيمة المحددة في :attribute غير صالحة.',
            'in' => 'القيمة المحددة في :attribute غير صالحة.',
            'accepted' => 'يجب الموافقة على :attribute.',
            'numeric' => 'الحقل :attribute يجب أن يكون رقمًا.',
            'image' => 'الملف :attribute يجب أن يكون صورة.',
            'max' => 'الملف :attribute يتجاوز الحجم المسموح.',
            'email'    => 'حقل :attribute يجب أن يكون بريدًا إلكترونيًا صالحًا.',
            'mimes'    => 'الامتدادات المسموح بها لـ :attribute هي: :values.',
            'max.file' => 'أقصى حجم لـ :attribute هو :max كيلوبايت.',
            'max.string' => 'أقصى طول لـ :attribute هو :max حروف.',
            'boolean'  => 'حقل :attribute يجب أن يكون صحيح/خطأ.',
            'second_color_id.different' => 'اللون الثاني يجب أن يختلف عن اللون الأول.',
            'installment_national_id.regex'     => 'رقم الهوية غير صحيح (يجب أن يكون 14 رقماً).',
            'cash_national_id.regex'     => 'رقم الهوية غير صحيح (يجب أن يكون 14 رقماً).',
        ];
    }

    public function attributes(): array
    {
        return [
            'booking_car_clone_id' => 'حجز السيارة',
            'car_term_id' => 'فئة السيارة',
            'first_color_id' => 'اللون المفضل الأول',
            'second_color_id' => 'اللون المفضل الثاني',

            'branch_name' => 'الفرع',
            'branch_location' => 'موقع الفرع',

            'payment_type' => 'نظام الدفع',
            'customer_type' => 'نوع العميل',

            'cash_full_name' => 'الاسم بالكامل',
            'cash_phone_number' => 'رقم الموبايل',
            'cash_individual_email' => 'البريد الإلكتروني',
            'cash_national_id' => 'رقم البطاقة',
            'cash_front_national_id_image' => 'صورة وجه البطاقة',
            'cash_back_national_id_image' => 'صورة ظهر البطاقة',

            'bank_id' => 'البنك',
            'installment_duration' => 'مدة التقسيط',
            'down_payment_amount' => 'قيمة المقدم',
            'down_payment_percent' => 'نسبة المقدم',

            'installment_full_name' => 'الاسم بالكامل',
            'installment_phone_number' => 'رقم الموبايل',
            'installment_individual_email' => 'البريد الإلكتروني',
            'installment_national_id' => 'رقم البطاقة',
            'installment_front_national_id_image' => 'صورة وجه البطاقة (تقسيط)',
            'installment_back_national_id_image' => 'صورة ظهر البطاقة (تقسيط)',
            'installment_bank_statement' => 'كشف حساب بنكي',
            'installment_hr_letter' => 'خطاب الموارد البشرية',

            'installment_company_name' => 'اسم الشركة',
            'installment_legal_representative_phone_number' => 'هاتف الممثل القانوني',
            'installment_company_email' => 'بريد الشركة',
            'installment_commercial_registration_number' => 'رقم السجل التجاري',
            'installment_commercial_registration_image' => 'صورة السجل التجاري',
            'installment_tax_card_number' => 'رقم البطاقة الضريبية',
            'installment_tax_card_image' => 'صورة البطاقة الضريبية',
            'installment_company_bank_statement' => 'كشف حساب بنكي للشركة',

            'price' => 'سعر السيارة',
            'reservation_amount' => 'مبلغ الحجز',
            'agreed_terms' => 'الموافقة على الشروط والأحكام',
        ];
    }
}
