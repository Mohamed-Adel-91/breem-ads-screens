<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Enums\ServicesOrderStatusEnum;

class InsuranceOrder extends Model
{
    use HasFactory;

    protected $table = 'insurance_orders';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'user_id',
        'brand_id',
        'brand_name',
        'car_model_id',
        'car_model_name',
        'car_term_id',
        'car_term_name',
        'insurance_id',
        'insurance_program_name',
        'insurance_company_name',
        'car_price',
        'annual_price_at_submission',
        'full_name',
        'phone_number',
        'individual_email',
        'national_id',
        'front_national_id_image',
        'back_national_id_image',
        'company_name',
        'legal_representative_phone_number',
        'company_email',
        'commercial_registration_number',
        'commercial_registration_image',
        'tax_card_number',
        'tax_card_image',
        'agreed_terms',
        'other_ownership',
        'sale_blocked',
        'chassis_number',
        'car_license_image',
        'car_documentation_image',
        'status',
        'reference_number',

    ];

    /**
     * The attributes that should be cast to native types.
     */
    protected $casts = [
        'car_price'                  => 'decimal:0',
        'annual_price_at_submission' => 'decimal:0',
        'agreed_terms'               => 'boolean',
        'other_ownership'            => 'boolean',
        'sale_blocked'               => 'boolean',
        'status'                     => 'integer',
    ];

    /**
     * Default values for attributes.
     */
    protected $attributes = [
        'status' => ServicesOrderStatusEnum::PENDING,
    ];

    /**
     * Relations
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function carTerm()
    {
        return $this->belongsTo(CarTerm::class, 'car_term_id');
    }

    public function insurance()
    {
        return $this->belongsTo(Insurance::class);
    }
}
