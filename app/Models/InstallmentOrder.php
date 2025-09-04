<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InstallmentOrder extends Model
{
    protected $table = 'installment_orders';

    protected $fillable = [
        'reference_number',
        'dealing_type',

        'branch_id',
        'branch_name',
        'brand_id',
        'brand_name',
        'car_model_id',
        'car_model_name',
        'term_id',
        'term_name',
        'program_id',
        'program_name',
        'bank_name',
        'program_interest_rate_per_year',

        'tenor_months',
        'car_price',
        'down_payment',
        'down_payment_percent',
        'monthly_payment_at_submission',
        'total_payable_at_submission',

        // Personal
        'full_name',
        'phone',
        'email',
        'national_id',
        'front_national_id_image',
        'back_national_id_image',
        'bank_statement',
        'hr_letter',

        // Company
        'company_name',
        'representative_phone',
        'company_email',
        'commercial_registration_number',
        'commercial_registration_image',
        'tax_card_image',
        'company_bank_statement',

        'car_owned_by_other',
        'agreed_terms',
        'status',
    ];

    protected $casts = [
        'agreed_terms' => 'boolean',
        'status' => 'integer',
        'car_price' => 'decimal:2',
        'down_payment' => 'decimal:2',
        'down_payment_percent' => 'decimal:2',
        'program_interest_rate_per_year' => 'decimal:2',
        'monthly_payment_at_submission' => 'decimal:2',
        'total_payable_at_submission' => 'decimal:2',
    ];

    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }
    public function carModel()
    {
        return $this->belongsTo(CarModel::class);
    }
    public function term()
    {
        return $this->belongsTo(CarTerm::class, 'term_id');
    }
    public function program()
    {
        return $this->belongsTo(InstallmentProgram::class);
    }
    public function branch()
    {
        return $this->belongsTo(ServiceCenter::class, 'branch_id');
    }
}
