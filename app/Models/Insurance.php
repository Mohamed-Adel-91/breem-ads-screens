<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Helpers\FeaturesFormatter;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Insurance extends Model
{
    use HasFactory, SoftDeletes;
    protected $table = 'insurance';

    protected $fillable = [
        'insurance_company',
        'program_name',
        'coverage_rate',
        'annual_price',
        'monthly_payment',
        'applicable_to',
        'company_logo',
        'description',
        'features',
    ];

    public const UPLOAD_FOLDER = 'upload/insurance/';

    public function getCompanyLogoPathAttribute()
    {
        return $this->company_logo ? asset(self::UPLOAD_FOLDER . $this->company_logo) : null;
    }

        protected function features(): Attribute
    {
        return Attribute::make(
            get: fn($value) => FeaturesFormatter::normalize($value, false),
            set: fn($value) => json_encode(FeaturesFormatter::sanitize($value)),
        );
    }

    public function installmentOrders()
    {
        return $this->hasMany(Order::class, 'insurance_id');
    }
    public function insuranceOrders()
    {
        return $this->hasMany(InsuranceOrder::class, 'insurance_id');
    }
}
